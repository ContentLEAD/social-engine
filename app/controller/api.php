<?php

class api extends controller{

    public function index(){
        
        
       $res = $this->fez->se
            ->start_date(strtotime('midnight first day of last month'))
            ->end_date(time())
            ->date_group('m-d-y')
            ->sort_by(array('date'=>1))
            ->go();

        $this->dump($res);        
    }
    
    public function load(){
        $all = $this->fez->mongo->find()
            ->in('records')
            ->where(array('client_status'=>true))
            ->go();
        
        //$this->dump($all);
        foreach($all as $client => $client_array){
            foreach($client_array['items'] as $items => $items_array){
                foreach($items_array['activity'] as $coms =>$c){
                    if($c['date']){
                        $commits['sfid'] = $client_array['sfid'];
                        $commits['user_id'] = $client_array['user_id'];
                        $commits['item_id'] =$items_array['item_id'];
                        $commits['type'] = $items_array['type'];
                        $commits['network'] = $items_array['network'];
                        $commits['action'] = $items_array['action'];
                        $commits['date'] = $c['date'];
                        $commits['period'] = date('M-y',$c['date']);
                        $commits['status'] = $items_array['status'];
                        $commits['created_by'] = $client_array['user_id'];
                        $commits['owed'] = $this->get_owed(date('M-y',$c['date']),$items_array['period']);
                        $coms_packgage[] = $commits;
                    }
                }
                foreach($items_array['period'] as $p =>$per){
                    //BUILD ITEM
                    $i['sfid'] = $client_array['sfid'];
                    $i['user_id'] = $client_array['user_id'];
                    $i['item_id'] =$items_array['item_id'];
                    $i['type'] = $items_array['type'];
                    $i['network'] = $items_array['network'];
                    $i['action'] = $items_array['action'];
                    $i['period'] = $per['name'];
                    $i['start_amount'] = $items_array['start_amount'];
                    $i['status'] = $items_array['status'];
                    $i['current_owed'] = $per['owed'];
                    $i_pack[] = $i;
                }
                if(empty($items_array['period'])){
                    //NYL
                    $i['sfid'] = $client_array['sfid'];
                    $i['user_id'] = $client_array['user_id'];
                    $i['item_id'] =$items_array['item_id'];
                    $i['type'] = $items_array['type'];
                    $i['network'] = $items_array['network'];
                    $i['action'] = $items_array['action'];
                    $i['period'] ='';
                    $i['start_amount'] = $items_array['start_amount'];
                    $i['status'] = $items_array['status'];
                    $i['current_owed'] = $per['owed'];
                    $i_pack[] = $i;
                }
            }
        }
        //ARM OR UNARM 
//        foreach($coms_packgage as $k => $com){
//            $this->fez->mongo->insert($com)
//                ->into('commits')
//                ->go();
//        } 
//        foreach($i_pack as $k => $com){
//            $this->fez->mongo->insert($com)
//                ->into('items')
//                ->go();
//        }    
    }
    
    private function get_owed($name,$per){
        foreach($per as  $k => $v){
            if($v['name'] == $name){
                return $v['owed'];
            }
        }
        return 0;
    }
    public function dump($o){
        echo '<pre>';
        var_dump($o);
        echo '</pre>';
        
    }
}