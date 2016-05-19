<?php

class se{
    //*********
    // CONSTRUCTOR
    //*********
    public function __construct(){
        
        $this->m = new MongoClient('127.0.0.1');
        $this->db = $this->m->selectDB('se');
        
    }
    
    //*********
    // LOAD
    //*********
    
    public function load($id){
        $c = $this->db->selectCollection('records');
        $record = $c->findOne(array('sfid'=>$id));
        //BUILD THE RECORD
        if(is_array($record)){
            foreach($record as $key => $val){
                $this->{$key} = $val;
            }
        }
        //SEPERATE LIVE AND NYL ITEMS
        if(!empty($this->items)){
            foreach($this->items as $key => $val){
                $this->{$val['status']}[] = $val;

                //get not closed
                if($val['status'] != 'CLOSED'){
                    $this->NOTCLOSED[] = $val;
                }
            }
        }
    }
    
    //*********
    // SELECTOR FUNCTIONS
    //*********
    
    public function live_items(){
        $this->selector = 'LIVE';
        return $this;
    }
    
    public function nyl_items(){
        $this->selector = 'NYL';
        return $this;
    }
    
    public function not_closed_items(){
        $this->selector = 'NOTCLOSED';
        return $this;
    }
    
    public function get_items(){
        return $this->items;
    }
    
    //*******************
    //***STATIC FUNCTIONS****
    //*******************
    
    
    //----
    //GET THE INFORMATION ON A SINGLE ITEM
    //---
    public static function item_info($item_id){
        $m = new MongoClient();
        $db = $m->selectDB('se');
        $c = $db->selectCollection('records');
        $res = $c->find(array('items.item_id'=>$item_id),array('items.$'=>1));
        $res = iterator_to_array($res);
        if(is_array($res)){
            $res = current($res);
        }else{
            return false;
        }   
        unset($res['items'][0]['activity'],$res['items'][0]['period']);
        return $res['items'][0];
    }
    
    //---
    //MAKE A COMMIT
    //---
    
    public static function add_commit($item_id,$user_id){
        $m = new MongoClient();
        $db = $m->selectDB('se');
        $commit = array('v'=>1,'date'=>time());
        //COMMIT
        $c = $db->selectCollection('records');
        //UPDATE THE ITEM
        $c->update(array('items.item_id'=>$item_id),array('$push'=>array('items.$.activity'=>$commit)));
        //PLACE COMMIT IN NEW LOCATION
        
        $rec = $c->find(array('items.item_id' => $item_id),array(
            'sfid'=>1,
            'user_id'=>1,
            'items.$.items_id'=>1
        ));
        $rec = iterator_to_array($rec);
        $rec = current($rec);
        //GET OWED
        foreach($rec['items'][0]['period'] as  $k => $v){
            if($v['name'] == date('M-y',time())){
                $owed = $v['owed'];
            }
        }
        
        $commits['sfid'] = $rec['sfid'];
        $commits['user_id'] = $rec['user_id'];
        $commits['item_id'] =$item_id;
        $commits['type'] = $rec['items'][0]['type'];
        $commits['network'] = $rec['items'][0]['network'];
        $commits['action'] = $rec['items'][0]['action'];
        $commits['date'] = time();
        $commits['period'] = date('M-y',time());
        $commits['owed'] = $owed;
        $commits['created_by'] = $user_id;
        $commits['status'] = 'LIVE';
        $c = $db->selectCollection('commits');
        $res = $c->insert($commits);
        
    }
    
    //---
    //SUBTRACT A COMMIT
    //---
    
    public static function sub_commit($item_id){
        $m = new MongoClient();
        $db = $m->selectDB('se');
        //COMMIT
        $c = $db->selectCollection('records');
        $c->update(array('items.item_id'=>$item_id),array('$pop'=>array('items.$.activity'=>1)));
        //UPDATE THE ITEM
        $c = $db->selectCollection('commits');
        $res = $c->find(array('item_id'=>$item_id));
        $res = $res->sort(array('date'=>-1));  
        $res = iterator_to_array($res);
        $res = current($res);
        $c->remove(array('_id'=>$res['_id']));
    }
    
    //---
    //GET THE ACTIVITY FROM A START DATE
    //---
    public static function activity($type,$start_date = false){
        if(!$start_date){
            $start_date = strtotime('midnight first day of this month');
        }
        $m = new MongoClient();
        $db = $m->selectDB('se');
        $agr = array(
            array('$match'=>array('client_status'=>true)),
            array('$project'=>array('items'=>true)),
            array('$unwind'=>'$items'),
            array('$match'=>array('items.status'=>$type)),
            array('$unwind'=>'$items.activity'),
            array('$match'=>array('items.activity.date'=>array('$gt'=>$start_date))),
            array('$sort'=>array('items.activity.date'=>-1)),
            //IF THIS SPITS OUT AN ARRAY COUNT OF 0 PIPE ENDS
            array('$group'=>array(
                    '_id'=>'$items.activity.date',
                    'posts'=>array('$sum'=>'$items.activity.v')
                )
            )
        );
        
        $c = $db->selectCollection('records');
        $res = $c->aggregate($agr);
        return $res['result'];
        
    }   
    
    public static function item_breakdown($type,$start_date = false){
        //GET START DATE
        if(!$start_date){
            $start_date = strtotime('midnight first day of this month');
        }
        //get current period
        $period = date('M-y',time());
        $agr = array(
            array('$unwind'=>'$items'),
            array('$match'=>array('items.status'=>$type)),
            array('$unwind'=>'$items.period'),
            array('$match'=>array('items.period.name'=>$period)),
            array('$unwind'=>'$items.activity'),
            array('$match'=>array('$or'=>array(
                array('items.activity.date'=>array('$gt'=>$start_date)),
                array('items.activity.date'=>0)
            ))),
            array('$group'=>array(
                '_id'=>array('network'=>'$items.network','action'=>'$items.action'),
                'act'=>array('$sum'=>'$items.activity.v'),
                'period'=>array('$addToSet'=>array('owed'=>'$items.period.owed','backlog'=>'$items.period.backlog'))
                )
            ),
        ); 
        $m = new MongoClient();
        $db = $m->selectDB('se');
        $c = $db->selectCollection('records');
        $res = $c->aggregate($agr);
        return $res['result'];
    }
    
    //*********
    //GET THE CLIENT INFO
    //*********

    public function client_info($field){
        return $this->{$field};
    }
    
    //*********
    // GET THE MATH OF ITEMS
    //*********
    public function math_items($catalog){

        $c = new stdclass;
        
        
        foreach($catalog as $k){
            if(!isset( $c->{$k['network']})){
                $c->{$k['network']} = new stdclass;
            }
            $c->{$k['network']}->{$k['action']} = (int)$k['time'];   
        }
        
        if( empty($this->{$this->selector})){
            return array();
        }
        
        foreach($this->{$this->selector} as $k => $v){

            //NUMBERS INFORMATION
            //GET TOTAL DAYS FOR THE MONTH
            $days = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),1);
            //HOW MANY DAYS ARE LEFT IN THE MONTH
            $days_left = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),date('d',time()));
            $numbers = new stdclass;
            //GET THE DELIVERED NUMBERS
            $numbers->delivered = $this->get_commits($v,'THIS_MONTH');
            //GET THE TOTAL DUE FOR THE MONTH
            $numbers->total_due = (int)$this->get_total_due($v,'THIS_MONTH');
            //GET THE START AMOUNT WHICH IS THE NORMAL ROLLING AMOUNT
            $numbers->start_amount = $v['start_amount'];
            //WHAT ITEMS ARE LEFT TO COMPLETE
            $numbers->owed =  $numbers->total_due - $numbers->delivered;
            //HOW MANY SHOULD BE DONE PER DAY
            $numbers->per_day = ($numbers->total_due/$days);
            //WHAT DO WE OWE TODATE
            $numbers->owed_to_date = (($numbers->per_day*($days-$days_left)));
            //positive number means we are behind, negative means agead
            $numbers->balance_to_date = $numbers->owed_to_date - $numbers->delivered;
            //GET OWED TIME OR TIME REMAINING
            $numbers->owed_time = $numbers->owed*$c->{$v['network']}->{$v['action']};
            //GET TIME CALC FOR COMPLETED ITEMS
            $numbers->completed_time = $numbers->delivered*$c->{$v['network']}->{$v['action']};
            //CALC PERCENTAGES
            if($numbers->owed_time+$numbers->completed_time != 0){
                $numbers->percentage_complete = round(($numbers->completed_time/($numbers->owed_time+$numbers->completed_time))*100);
                $numbers->percentage_inc = $c->{$v['network']}->{$v['action']};
            }else{
                $numbers->percentage_complete = 0;
            }
//            $total_done = $total_done + $numbers->completed_time;
//            $total_due = $total_due + $numbers->completed_time;
            
            //ITEM INFORMATION
            $i = new stdclass;
            $i->item_id = $v['item_id'];
            $i->action = $v['action'];
            $i->type = $v['type'];
            $i->status = $v['status'];
            $i->sfid = $v['sfid'];
            
            if($v['status'] == 'NYL'){
                $i->classes = ' nyl ';
                $numbers->owed = 'NYL';
            }else{
                $i->classes = $this->get_classes($numbers);
            }
            
            $i->numbers = $numbers;
            $item[$v['network']][] = $i;
        }
        //REORDER
        return $item;
    }
    
    //*********
    // PRIVATE FUNCTIONS
    //*********
    private function get_classes($n){
        $cl = '';
        //CALC TO DATE
        if($n->delivered >= $n->total_due){
            $cl .=' done ';
        }else
        if($n->balance_to_date > 0 ){
            $cl .= ' under ';
        }else
        if($n->balance_to_date  <= 0 ){
            $cl .= ' over ';
        }
        return $cl;
    }
    
    
    private function get_total_due($item,$per = 'THIS_MONTH'){
        switch ($per){
            case 'LAST_MONTH':
                $period = $this->get_period($item,date('M-y',strtotime('midnight first day of last month')));
            break;
            case 'THIS_MONTH':
                $period = $this->get_period($item,date('M-y',time()));
            break;    
            default:
                $period = $this->get_period($item,date('M-y',time()));
            break;
        }
        return $period['owed'];
    }
    
    private function get_commits($item,$per = 'THIS_MONTH'){
        $end = strtotime('midnight first day of next month');
        switch ($per){
            case 'THIS_MONTH':
            $start = strtotime('midnight first day of this month');
            break;
            case 'LAST_MONTH':
            $start = strtotime('midnight first day of last month');
            $end = strtotime('midnight first day of this month');
            break;
            default:
            $start = strtotime('midnight first day of this month');
            break;
        }
        $count = 0;
        $start = strtotime('midnight first day of this month');
        foreach($item['activity'] as $key => $v){
            if($v['date'] >= $start && $v['date'] < $end ){
                $count++;
            }
        }
        return $count;
    }
    
    public function out($ob){
        echo '<pre>';
        var_dump($ob);
    }
    
    
    private function get_period($item,$p){
        foreach($item['period'] as $k){
            if($k['name'] == $p){
                return $k;
            }
        }
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