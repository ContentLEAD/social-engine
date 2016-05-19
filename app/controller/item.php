<?php

class item extends controller{

    public function index(){}
    
    //---------
    //ADD AN ITEM
    //-----------
    
    public function add_item($client_id){
        
        $network = $this->fez->db->select('DISTINCT(network)')
            ->from('catalog')
            ->where('status=1')
            ->result('object');
        
        //LOOK UP CLIENT ID
        $data = array('client_id'=>$client_id,
                     'network'=>$network);
        
        
        //LOAD VIEW
        $this->fez->load->view('item/add_item',$data);
    }
    
    
    //---------
    //ASSIGN BACKLOG
    //-----------
    public function backlogger($sfid){
        
        //PULL UPDATED BACKLOG
        $res = $this->fez->mongo->find(array('unassigned_backlog'=>true,'client_name'=>true))
            ->in('records')
            ->where(array('sfid'=>$sfid))
            ->go();
        
        //HANDLE RESULTS
        $res = current($res);
        $ub = $res['unassigned_backlog'];
        $client_name = $res['client_name'];
        
        //GET NETWORKS AND ACTIONS
        $items = $this->fez->db->select('*')
            ->from('catalog')
            ->orderby('network')
            ->result();
        
        //LOAD THE VIEW
        $this->fez->load->view('item/item_backlog',
            array(
                'ub'=>$ub,
                'client_name'=>$client_name,
                'items' =>$items,
                'sfid'=>$sfid
                )
        );
    
    }
    //---------
    //INFO FOR AN ITEM
    //-----------
    
    public function item_info($item_id){
        
        $res = $this->fez->mongo->find(array('items.$'=>1,'client_name'=>1,'user_id'=>1))
            ->in('records')
            ->where(array('items.item_id'=>$item_id))
            ->go();
        
        //SORT ITEM RETURNED
        foreach($res as $key => $v){
            $re = $v;
        }
        
        //GET COMPLETION SUMMARY
        //MAKE DATES
        $m = date('M',time());
        $y = date('y',time());
        
        //get the days left
        $days_left = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),date('d',time()));
        //GET TIME VARS
        $start_time = strtotime('1-'.$m.'-'.$y);
        $cur_period = date('M-y',time());
        
        
        //LOAD USER FRIN USER ID
        $u = new user;
        $u->load($re['user_id']);
        $re['user_id'] = $u->get('first_name').' '.$u->get('last_name');
        
         $agr = array(
                array('$match'=>array('items.item_id'=>$item_id)),
                array('$project'=>array('items'=>true)),
                array('$unwind'=>'$items'),
                array('$match'=>array('items.item_id'=>$item_id)),
                array('$unwind'=>'$items.period'),
                array('$match'=>array('items.period.name'=>$cur_period)),
                array('$unwind'=>'$items.activity'),
                array('$match'=>array('$or'=>array(
                    array('items.activity.date'=>array('$gt'=>$start_time)),
                    array('items.activity.date'=>0)
                ))),
                //IF THIS SPITS OUT AN ARRAY COUNT OF 0 PIPE ENDS
                array('$group'=>array(
                    '_id'=>array('network'=>'$items.network','action'=>'$items.action','item_id'=>'$items.item_id'),
                    'owed'=>array('$max'=>'$items.period.owed'),
                    'backlog' =>array('$max'=>'$items.period.backlog'),
                    'delivered'=>array('$sum'=>'$items.activity.v')
                    )
                )
            ); 
            
        $data = $this->fez->mongo->agg($agr)
        ->in('records')
        ->go();
        
        foreach($data['result'] as $key){
            $d['owed'] = $key['owed'];
            $d['delivered'] = $key['delivered'];
            $d['backlog'] = $key['backlog'];
            $d['backlog_td'] = $key['backlog']/$days_left;
            $d['owed_td']= $key['owed']/$days_left;
        }
            
        $month = $this->get_data($item_id);
        
        //LOAD VIEW
        $this->fez->load->view('item/item_info',array('client'=>$re,'item'=>$re['items'][0],'chart'=>$month,'sum_data'=>$d));
    }
    
    private function get_data($item_id){
        
        
        $agr = array(
//                array('$match'=>array('items.item_id'=>$item_id)),
//                array('$project'=>array('items'=>true)),
                array('$unwind'=>'$items'),
                array('$match'=>array('items.item_id'=>$item_id)),
                array('$unwind'=>'$items.period'),
//                array('$match'=>array('items.period.name'=>$cur_period)),
                array('$unwind'=>'$items.activity'),
//                array('$match'=>array('$or'=>array(
//                    array('items.activity.date'=>array('$gt'=>$start_time)),
//                    array('items.activity.date'=>0)
//                ))),
                //IF THIS SPITS OUT AN ARRAY COUNT OF 0 PIPE ENDS
                array('$group'=>array(
                    '_id'=>array('item_id'=>'$items.item_id'),
                    'act'=>array('$addToSet'=>'$items.activity.date'),
                    'per'=>array('$addToSet'=>array('name'=>'$items.period.name','owed'=>'$items.period.owed','backlog'=>'$items.period.backlog'))
                    )
                ),
            ); 
        
        
        $data = $this->fez->mongo->agg($agr)
        ->in('records')
        ->go();
        asort($data['result'][0]['act']); 
        foreach($data['result'][0]['act'] as $i => $v){
            if($v){
                $month[date('M',$v)]['del']++;
            }
        }
        foreach($data['result'][0]['per'] as $i => $v){
            $month[date('M',strtotime($v['name']))]['owed'] = (int)$v['owed'];
            if($v['backlog']){
            $month[date('M',strtotime($v['name']))]['backlog'] = (int)$v['backlog'];
            }
        }
        return $month;
    }
    
    
    private function countDays($year, $month, $ignore, $start) {
        $count = 0;
        $counter = mktime(0, 0, 0, $month, $start, $year);
        while (date("n", $counter) == $month) {
            if (in_array(date("w", $counter), $ignore) == false) {
                $count++;
            }
            $counter = strtotime("+1 day", $counter);
        }
        return $count;
    }
    
    
}