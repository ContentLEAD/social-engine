<?php

class autoloader extends controller{
    
    public $field_key = array(
        'Twitter_Posts__c' =>array(
            'network'=>'TWITTER',
            'action'=>'POST'
        ),
        'Twitter_Follower_Push__c' =>array(
            'network'=>'TWITTER',
            'action'=>'PUSH'
        ),
        'TWSL__c' =>array(
            'network'=>'TWITTER',
            'action'=>'LISTENING'
        ),
        'TW_A_c__c' =>array(
            'network'=>'TWITTER',
            'action'=>'AD'
        ),
        'TW_A_c__c' =>array(
            'network'=>'TWITTER',
            'action'=>'AD'
        ),
        //FACEBOOK
        'Facebook_Posts__c' =>array(
            'network'=>'FACEBOOK',
            'action'=>'POST'
        ),
        'Facebook_Ads__c' =>array(
            'network'=>'FACEBOOK',
            'action'=>'AD'
        ),
        'FBSL__c' =>array(
            'network'=>'FACEBOOK',
            'action'=>'LISTENING'
        ),
        //GOOGLE
        'GPSL__c' =>array(
            'network'=>'GOOGLE',
            'action'=>'LISTENING'
        ),
        'Google_Posts__c' =>array(
            'network'=>'GOOGLE',
            'action'=>'POST'
        ),
        //LINKEDIN
        'Linkedin_Discussion__c' =>array(
            'network'=>'LINKEDIN',
            'action'=>'DISCUSSION'
        ),
        'Linkedin_Ads__c' =>array(
            'network'=>'LINKEDIN',
            'action'=>'AD'
        ),
        'Linkedin_Posts__c' =>array(
            'network'=>'LINKEDIN',
            'action'=>'POST'
        ),
        //MEETING
        'Client_Meeting_30_Min__c' =>array(
            'network'=>'MEETING',
            'action'=>'30'
        ),
        'Client_Meeting_60_Min__c' =>array(
            'network'=>'MEETING',
            'action'=>'60'
        ),
        //PINTEREST
        'Pinterest_Pins__c' =>array(
            'network'=>'PINTEREST',
            'action'=>'PIN'
        ),
        'Pinterest_Interactions__c' =>array(
            'network'=>'PINTEREST',
            'action'=>'INTERACTION'
        ),
        //REPORT
        'Pinterest_Interactions__c' =>array(
            'network'=>'REPORT',
            'action'=>'SINGLE'
        ),
        'Pinterest_Interactions__c' =>array(
            'network'=>'REPORT',
            'action'=>'MULTI'
        )
    );
    
    public function index(){
        $user = $this->fez->session->get_data(); 
        $res = $this->fez->sforce->brafton->describeSObject('SMO__c');
         
        
        foreach($res->fields as $k){
            $fields_array[$k->label] = $k->name;
        
        }
        $fields = implode(",",$fields_array);
        $smo = $this->fez->sforce->brafton->query("SELECT ".$fields." FROM SMO__c WHERE Account__r.Social_Media_exec__c ='".$user['sfid']->val."' AND Account__r.Type LIKE 'Customer%' AND Account__r.Type != 'Customer - Terminated'");
        $smo = $smo->records;
        foreach($smo as $key){
            foreach($fields_array as $k => $v){
                if($key->fields->{$v} > 0){
                    $x[$key->fields->Account__c][$v] = $key->fields->{$v};
                    $network = $this->field_key[$v]['network'];
                    $action = $this->field_key[$v]['action'];
                    if(isset($this->field_key[$v]['network']) && isset($this->field_key[$v]['action'])){
                        $network = $this->field_key[$v]['network'];
                        $action = $this->field_key[$v]['action'];
                        if($action == 'LISTENING'){
                            $smoPackage[$key->fields->Account__c][$network][$action] = 1;
                        }else{
                            $smoPackage[$key->fields->Account__c][$network][$action] = (int)$key->fields->{$v};
                        }
                    }
                }   
            }
        }
        
        foreach($smoPackage as $client => $network ){
            //CHECK IF IN DB
            $res = $this->fez->mongo->find(array('client_name'=>true))
                ->in('records')
                ->where(array('sfid'=>$client))
                ->go();
            if(empty($res)){
                 $this->create_client($client,$user['id']->val);
                 $this->add_items($client,$network);
            }
        }
    }

    public function items($uid){
        $all = $this->fez->mongo->find()
            ->in('records')
            ->where(array('client_status'=>true,'user_id'=>$uid))
            ->go();

        echo '<pre>';
        foreach($all as $client => $client_array){
            foreach($client_array['items'] as $items => $items_array){
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
            }
        }
//        var_dump($i_pack);
//        die();
//        foreach($i_pack as $k => $com){
//            $res[] = $this->fez->mongo->insert($com)
//                ->into('items')
//                ->go();
//        }
        
        var_dump($res);
    }
    
    private function add_items($client,$items){
        $i = array();
        foreach($items as $net => $action){
            foreach($action as $act => $v){
                $x['start_amount'] = (int)$v;
                $x['type'] = 'ROLLING';
                $x['network'] = $net;
                $x['action'] = $act;
                $x['item_id'] = uniqid();
                $x['created_date'] = time();
                $x['live_date'] = time();
                //ADD KICKERS TO ACTIVITY ARRAY SO WILL ALWAYS UNWIND
                $x['activity'] = array(array(
                    'date'=>0,
                    'v'=>0
                ));
                //FREQ MANTH
                $x['period'] = array(array(
                    'name'=>'Feb-16',
                    'owed'=>0
                ));
                $x['status'] = 'LIVE';
                //ADD TO PACKAGE ARRAY
                $i[] = $x;
            }
        }
        if(!empty($i)){
            
            $res = $this->fez->mongo->set(array('items'=>$i))
            ->in('records')
            ->where(array('sfid'=>$client))
            ->go();
            
        }
    }

    private function create_client($sfid,$user_id){
        $res = $this->fez->sforce->brafton->query("SELECT Name FROM Account WHERE Id='".$sfid."'");
        $res = $res->records;
        $client_name = $res[0]->fields->Name;
        
        $client=array(
                'client_name'=> $client_name,
                'sfid'=>$sfid,
                'client_status'=>true,
                'start_date'=>time(),
                'user_id' =>$user_id,
                'prev_users'=>array(),
                'items'=>array(),
                'unassigned_backlog'=>0
            );
        
        $res = $this->fez->mongo->insert($client)
            ->in('records')
            ->go();
        $this->dump($res);
    }
    
    private function dump($ob){
        echo '<pre>';
        var_dump($ob);
        echo '</pre>';
    }
}