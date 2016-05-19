<?php

class builder extends controller{

    public $period;
    public $last_period;
    public $items;
    public $backlog;
    public $report;

    
    public function index($key=false){
        if(!$key){
            die('no key');
        }
        if($key != 'braftongo'){
            die('key_invalid');
        }
        //GET PREV
        $previous_period = date('M-y',strtotime('midnight first day of last month'));
        //$previous_period = 'Jan-16';
        //LOAD THE SE LIBRARY
        $res = $this->fez->se
                ->status('LIVE')
                ->period($previous_period)
                ->math_by('item_id')
                ->include_fields(array('sfid','type'))
                ->go();
        
        //LOOP AND BUILD ARRAYS THAT WILL HELP MAKE THE UPDATES.
        foreach($res as $item_id => $value){
            //BUILD AN ARRAY WITH THE BACKLOG OF OWED TIME
            $remaining_time = $value['total_time_owed']- $value['total_time_delivered'];
            if($value['type'] == 'ROLLING'){
                $backlog[$value['sfid']] += $remaining_time;
            }
            
            //BUILD ARRAY OF ITEMS TO REGEN NEXT MONTH
            if($value['type'] == 'ROLLING'){
                $regen_items[$item_id] = $value; 
            //FIXED AND BACKLOG ITEMS THAT CAN BE CLOSED OUT
            }else if($remaining_time == 0){
                //CLOSE CLIENT ITEM
                $this->fez->mongo->set(array('items.$.status'=>'CLOSED','items.$.end_date'=>time()))
                    ->where(array('items.item_id'=>$item_id))
                    ->in('records')
                    ->go();
                
                //CLOSE CORESPONDING COMMITS
                $this->fez->mongo->set(array('status'=>'CLOSED'))
                    ->in('commits')
                    ->where(array('item_id'=>$item_id))
                    ->options(array('multiple'=>true))
                    ->go();
                
                //CLOSE ITEMS
                $this->fez->mongo->set(array('status'=>'CLOSED','delivered'=>$value['delivered']))
                    ->in('items')
                    ->where(array('item_id'=>$item_id))
                    ->options(array('multiple'=>true))
                    ->go();
            }else{
                //IS FIXED OR BACKLOG, NEED REGEN
                $regen_items[$item_id] = $value; 
            }
            
           //UPDATE ITEM
            $this->fez->mongo->set(array('delivered'=>$value['delivered']))
                ->in('items')
                ->where(array('period'=>$previous_period,'item_id'=>$item_id))
                ->go();
        }
        
        //CREATE NEW ITEMS
        foreach($regen_items as $item_id => $value){
            
            //GRAB THE MOST RECENT ITEM
            $item = $this->fez->mongo->findOne()
                ->in('items')
                ->where(array('item_id'=>$item_id,'period'=>$previous_period))
                ->go();
            
            //UNSET THE _ID
            unset($item['_id']);
                      
            //CHANGE CURRENT OWED TO START AMOUNT
            $item['current_owed'] = $item['start_amount'];
            
            if($item['type'] != 'ROLLING' ){
                $item['current_owed'] = $item['start_amount']-$item['delivered'];
            }
            
            //CHANGE PERIOD TO CURRENT PERIOD
            $item['period'] = date('M-y',time());
            
            //INSERT NEW ITEM
            $this->fez->mongo->insert($item)
                ->into('items')
                ->go();   
        }
        //INCREMENT BACKLOG
        foreach($backlog as $sfid => $amount){
            $res = $this->fez->mongo->findOne()
                ->in('records')
                ->where(array('sfid'=>$sfid))
                ->go();
            //CALC NEW AMOUNT
            $new_amount = $res['unassigned_backlog']+$amount;
            
            $this->fez->mongo->set(array('unassigned_backlog'=>$new_amount))
                ->in('records')
                ->where(array('sfid'=>$sfid))
                ->go();
        }
        
    }

    public function start(){
        //SET THIS PERIOD
        $this->period = date('M-y',time());
        //SET LAST PERIOD OR THE PERIOD WE JUST FINISHED
        $this->last_period = date('M-y',strtotime('midnight first day of last month'));
        //CHECK TO MAKE SURE ITS THE FIRST OF THE MONTH
        if(date('d',time()) != 1){
            die('Not the First');
        }
        //CALL STACK
        //GET THE ITEMS
        $this->items = $this->get_items();
        //CALCULATE THE BACKLOG
        $this->calculate_backlog();
        //BUILD
        $this->build();
    }
    
    private function get_items(){
        //ARGUMENT ARRAY
        $agr = array(
            array('$match'=>array('client_status'=>true)),
            array('$project'=>array('items'=>true,'sfid'=>true)),
            array('$unwind'=>'$items'),
            array('$match'=>array('items.status'=>'LIVE')),
            array('$unwind'=>'$items.period'),
            array('$match'=>array('items.period.name'=>$this->last_period)),
            array('$unwind'=>'$items.activity'),
            array('$match'=>array('$or'=>array(
                array('$and'=>array(
                    array('items.activity.date' => array('$gte'=>strtotime('midnight first day of last month'))),
                    array('items.activity.date' => array('$lt'=>strtotime('midnight first day of this month')))
                    )),
                array('items.activity.date'=>0)
            ))),
            //IF THIS SPITS OUT AN ARRAY COUNT OF 0 PIPE ENDS
            array('$group'=>array(
                '_id'=>array('item_id'=>'$items.item_id','network'=>'$items.network','action'=>'$items.action'),
                'owed'=>array('$max'=>'$items.period.owed'),
                'start_amount'=>array('$max'=>'$items.start_amount'),
                'delivered'=>array('$sum'=>'$items.activity.v'),
                'type'=>array('$first'=>'$items.type'),
                'sfid'=>array('$first'=>'$sfid'),
                'last_activity'=>array('$max'=>'$items.activity.date')
                )
            )
        );  
            
        $live_items = $this->fez->mongo->agg($agr)
        ->in('records')
        ->go();
        
        return $live_items['result'];
    }
    
    private function calculate_backlog(){
        foreach($this->items as $i){   
            switch($i['type']){
                case('FIXED'):
                    //ITEM IS STILL LIVE
                    if($i['owed'] - $i['delivered'] > 0){
                        $x = $i;
                        $x['start_amount'] = $i['owed'] - $i['delivered'];
                    }
                    //ITEM IS NOT FINISHED, CLOSE THE ITEM AND REMOVE FROM ITEMS ARRAY
                    else{
                        $this->fez->mongo->set(array('items.$.status'=>'CLOSED','items.$.end_date'=>$i['last_activity']))
                            ->in('records')
                            ->where(array('items.item_id'=>$i['_id']['item_id']))
                            ->go();
                    }       
                break;
                case('BACKLOG'):
                    //ITEM IS STILL LIVE
                    if($i['owed'] - $i['delivered'] > 0){
                        $x = $i;
                        $x['start_amount'] = $i['owed'] - $i['delivered'];
                    }
                    //ITEM IS NOT FINISHED, CLOSE THE ITEM AND REMOVE FROM ITEMS ARRAY
                    else{
                        $this->fez->mongo->set(array('items.$.status'=>'CLOSED','items.$.end_date'=>$i['last_activity']))
                            ->in('records')
                            ->where(array('items.item_id'=>$i['_id']['item_id']))
                            ->go();
                    }
                break;


                default:
                    //ADD ROLLING ITEM TO ARRAY
                    $x = $i;
                    //CHECK AND ADD BACKLOG IF EXISTS
                    if( $i['owed'] - $i['delivered'] > 0 ){
                        $x['backlog'] = $i['owed'] - $i['delivered'];
                        $minutes = $this->get_minutes(array('network'=>$i['_id']['network'],'action'=>$i['_id']['action'],'backlog'=>$x['backlog']));
                        $this->backlog[$i['sfid']] = $this->backlog[$i['sfid']] + $minutes;
                     }
                break;
            }
            $t[] = $x;
         }
        $this->items = $t;
    }
    
    
    private function get_minutes($data){
       $item =  $this->fez->db->select('time')
            ->from('catalog')
            ->where('network = "'.$data['network'].'" AND action="'.$data['action'].'"')
            ->row();
      
      return $data['backlog']*(int)$item['time'];    
    }
    
    private function build(){
        
        foreach($this->items as $i){    
            //CHECK IF BACKLOG
            $p_data = $this->build_period_data($i);
            //CHECK TO SEE IF P_DATA IS AN ARRAY AND SEND
            if($p_data){
                //INSERT PERIOD DATA INTO ITEMS
                $res = $this->fez->mongo->set(array('items.$.period'=>$p_data))
                    ->into('records')
                    ->where(array('items.item_id'=>$i['_id']['item_id']))
                    ->go();
                
                //HANDLE THE BACKLOG
                
                
            }
        }
        //INCREMENT THE MINUTES OF BACKLOG
        //$this->add_to_backlog();
    }

    private function add_to_backlog(){
        foreach($this->backlog as $sfid => $min){
            $this->fez->mongo->inc(array('unassigned_backlog'=>(int)$min))
                ->in('records')
                ->where(array('sfid'=>$sfid))
                ->go();
        }
    }
    
    
    private function build_period_data($item){
        
        //GET THE PERIOD DATA
       $data =  $this->fez->mongo->find(array('items.$.period'=>true))
            ->where(array('items.item_id'=>$item['_id']['item_id']))
            ->in('records')
            ->go();
        //CORRECT DUMB MONGO DATA
        $data = current($data);
        $data = current($data['items']);
        $data = $data['period'];
        
        //IS THERE BACKLOG?
        //ADD THE UNFINISHED ITEMS TO LAST PERIOD
        foreach($data as $k){
            $h = $k;
            //CHECK FOR PERIODS ALREADY ENTERED
            if($k['name'] == $this->period){
                return false;
            }
            //CHECK FOR BACKLOG
            if(isset($item['backlog'])){
                if($k['name'] == $this->last_period){
                    $h['undelivered'] = (int)$item['backlog'];
                }
            }
            $new_period_data[] = $h;
        }
        //ADD THIS PERIOD INFORMATION
        $new_period_data[] = array(
            'name'  =>  $this->period,
            'owed'  =>  (int)$item['start_amount']
        );
        
        return $new_period_data;
    }
    
    
    
    private function dump($res = false){
        if(!$res){
            echo '<pre>';
            var_dump($this->items);
        }else{
            echo '<pre>';
            var_dump($res);
        }
    }
    
}