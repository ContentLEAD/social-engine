<?php 

class se {
    
    public function __construct($catalog){
        $this->m = new MongoClient('127.0.0.1');
        $this->db = $this->m->selectDB('se');
        $this->cat = $catalog;
        //QUERY TYPE
        $this->item = true;
        $this->commit = true;
    }
    
    public function client($sfid){
        if(is_array($sfid)){
            $this->criteria['$or'] = $sfid;
            return $this;
        }
        $this->criteria['sfid'] =$sfid;
        return $this;
    }
    
    public function item($item_id){
        $this->criteria['item_id'] = $item_id;
        return $this;
    }
    
    public function status($status){
        $this->criteria['status'] = $status;
        return $this;
    }
    public function start_date($time){
        $this->item = false;
        $this->criteria['date']['$gte'] = $time;                        
        return $this;
    }
    public function end_date($time){
        $this->item = false;
        $this->criteria['date']['$lte'] = $time;                               
        return $this;
    }
    public function period($period){
        $this->criteria['period'] = $period;                        
        return $this;
    }
    
    public function group_by($elem){
        $this->group_by = $elem;
        return $this;
    }
    
    public function math_by($elem){
        $this->math_by = $elem;
        return $this;
    }
    
    public function include_fields($fields){
        $this->include_fields = $fields;
        return $this;
    }
    public function sort_by($array){
        $this->sort_by = $array;
        return $this;
    }
    public function date_group($format){
        $this->date_group_format = $format;
        return $this;
    }
    
    public function backlog(){
        $c = $this->db->selectCollection('records');
        $record = $c->find($this->criteria);
        $ret = iterator_to_array($record);
        $ret = current($ret);
        $this->reset_class();
        return $ret['unassigned_backlog'];
    }
    
    public function nyl(){
        $this->criteria['status'] = 'NYL';
        $c = $this->db->selectCollection('items');
        $record = $c->find($this->criteria);
        $ret = iterator_to_array($record);
        
        $this->reset_class();
        return $ret;
    }
    
    //RUNS THE CRITERIA AGAINST THE COMMITS OR ITEMS OR BOTH
    public function go(){
        
        //GRAB RELEVENT ITEMS IF ITEM TYPE QUERY
        if($this->item){
            $c = $this->db->selectCollection('items');
            $record = $c->find($this->criteria,array('_id'=>0));
            if($this->sort_by){
                $record = $record->sort($this->sort_by); 
            }
            $items = iterator_to_array($record);
        }
        //CHECK FOR NO ITEMS
        if(empty($items) && $this->item){
            return array();
        }
        //
        if($this->commit){
            //GRAB RELEVENT COMMITS
            $c = $this->db->selectCollection('commits');
            $record = $c->find($this->criteria,array('_id'=>0));
            if($this->sort_by){
                $record = $record->sort($this->sort_by); 
            }
            $commits = iterator_to_array($record);

        }
        //GROUP BY?
        if($this->group_by){
            if($this->math_by){
                return array('ERROR'=>'Can not use both group_by and math_by in the same call.');
            }
            $ret = $this->sort_return($items);
        }
        //MATH BY?
        if($this->math_by){
            if($this->group_by){
                return array('ERROR'=>'Can not use both group_by and math_by in the same call.');
            }
            $ret = $this->math_items($items,$commits);
            //RESET CLASS
        }
        
        //DATE GROUP BY?
        if($this->date_group_format){
            if($this->group_by){
                return array('ERROR'=>'Can not use both group_by and math_by in the same call.');
            }
            $ret = $this->date_group_commits($commits);
            //RESET CLASS
        }
        
        $this->reset_class();
        return $ret;
    }
    
    
    private function date_group_commits($res){
        foreach($res as $_id => $fields){    
            $sorted[date($this->date_group_format,$fields['date'])] += 1;
        }
        return $sorted;
    }
    
    private function reset_class(){
        unset($this->criteria,$this->math_by,$this->group_by,$this->include_fields,$this->date_group_format);
        $this->item = true;
        $this->commit = true;
    }
    
    private function math_items($items,$commits){
        //SET GROUP_BY
        $this->group_by = $this->math_by;
        //SORT THE RETUN
        $items = $this->sort_return($items);
        $commits = $this->sort_return($commits);

        //CALC DAYS
        $days = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),1);
        //HOW MANY DAYS ARE LEFT IN THE MONTH
        $days_left = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),date('d',time()));
        //LOOP
        foreach($items as $key => $item_array){
            $trans = $item_array;
            
            $ret[$key]['total_owed'] = 0;
            $ret[$key]['delivered'] = count($commits[$key]);
            
            //TOTALS
            foreach($item_array as $n => $item){
                $ret[$key]['total_owed'] += $item['current_owed'];
                $ret[$key]['total_time_owed'] += $item['current_owed'] * $this->grab_time($item['network'],$item['action']);
                //IF GROUP BY ITEM
                if($this->group_by == 'item_id'){
                    $ret[$key]['percent_inc'] = $this->grab_time($item['network'],$item['action']);
                }
            }
            if(is_array($commits[$key])){
                foreach($commits[$key] as $n => $com){
                    $ret[$key]['total_time_delivered'] += $this->grab_time($com['network'],$com['action']);
                }
            }else{
                $ret[$key]['total_time_delivered'] = 0;
            }
            if($this->include_fields){
                foreach($this->include_fields as $k => $field){
                    $ret[$key][$field] = $items[$key][0][$field];
                }
            }
            $ret[$key]['item_time_percent'] = round(($ret[$key]['total_time_delivered']/$ret[$key]['total_time_owed'])*100*100)/100;
            $ret[$key]['owed_to_date'] = ($days-$days_left)*($ret[$key]['total_owed']/$days);
            $ret[$key]['class'] = $this->get_class($ret[$key]);
            $ret[$key]['remaining'] = $ret[$key]['total_owed']-$ret[$key]['delivered'];
            
        }
        return $ret;
    }
    
    
    private function grab_time($network,$action){
        foreach($this->cat as $k =>$v){

            if($v['network'] == $network && $v['action'] == $action){

                return (int)$v['time'];
            }
        }
    }
    
    private function get_class($division){
        if($division['delivered'] >= $division['total_owed']){
            return 'done';
        }
        if($division['delivered'] < $division['owed_to_date']){
            return 'under';
        }
        if($division['delivered'] >= $division['owed_to_date']){
            return 'over';
        }
    }
    
    private function sort_return($res){
        
        foreach($res as $_id => $fields){
            if(is_array($this->group_by)){
                $s_key = array();
                //ARRAY OF ELEMENTS TO GROUP BY
                foreach($this->group_by as $k =>$v){
                    $s_key[] = $fields[$v];
                }
                $sorted[implode(' ',$s_key)][] = $fields;
            }else{
                $sorted[$fields[$this->group_by]][] = $fields;
            }
        }
        return $sorted;
    }
    
    
    private function fix_order($array,$dir,$type){
        $res = array();
        
        foreach($array as $key => $val){
            if($type == 'date'){
                $num[] = strtotime($key);
            }else{
                $num[] = (int)$key;
            }
        }
        var_dump($num);
        die();
        if($dir == 'asc'){
            asort($num);
        }else{
            arsort($num);
        }
        foreach($num as $key => $val){
            if($type == 'date'){
                $key = date($this->date_group_format,$key);
                $res[$key] = $array[$key];
            }else{
            
            }
        }
        return $res;
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
    
    private function dump($o){
        echo '<pre>';
        var_dump($o);
        echo '</pre>';
        die();
    }
    
    //------
    //public static functions
    //------
    //------
    //------
    public static function add_commit($item_id,$user_id){
        $m = new MongoClient();
        $db = $m->selectDB('se');
        //PLACE COMMIT IN NEW LOCATION
        $c = $db->selectCollection('items');
        //GRAB INFORMATION ON ITEM
        $rec = $c->find(array('item_id' => $item_id,'period'=>date('M-y',time())));
        
        $rec = iterator_to_array($rec);
        $rec = current($rec);
        
        $commits['sfid'] = $rec['sfid'];
        $commits['user_id'] = $rec['user_id'];
        $commits['item_id'] =$item_id;
        $commits['type'] = $rec['type'];
        $commits['network'] = $rec['network'];
        $commits['action'] = $rec['action'];
        $commits['date'] = time();
        $commits['period'] = date('M-y',time());
        $commits['owed'] = $rec['current_owed'];
        $commits['created_by'] = $user_id;
        $commits['status'] = 'LIVE';
        //SEND TO THE DATABASE
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
        $c = $db->selectCollection('commits');
        $res = $c->find(array('item_id'=>$item_id));
        $res = $res->sort(array('date'=>-1));  
        $res = iterator_to_array($res);
        $res = current($res);
        $c->remove(array('_id'=>$res['_id']));
    }
    
}