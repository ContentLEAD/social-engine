<?php

class board extends controller{
    
    public function index($admin = false){
        //GET USER DATA        
        $user = $this->fez->session->get_data(); 
        //SALEFORCE QUERIES
        if($user['role_id']->val != 1 && $admin){
            header( 'Location: /' ) ;
        }
        //IS ADMIN
        if($admin){
            $team = $this->fez->db->select('sfid')
                ->from('user')
                ->where('team = "'.$user['team']->val.'"')
                ->result();
        
            foreach($team as $k => $v){
                $team_ids[] = $v['sfid'];
            }

            $team_ids = "(Social_Media_Exec__c ='".implode("' OR Social_Media_Exec__c ='",$team_ids)."')";
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE ".$team_ids." AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $res = $res->records;
            
            $tasks = $this->fez->db->select('*')
                ->from('task')
                ->where('status=1')
                ->orderby('due_date')
                ->result();
        }else{
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $res = $res->records;
        
            $tasks = $this->fez->db->select('*')
                ->from('task')
                ->where('owner = "'.$user['id']->val.'" AND status=1')
                ->orderby('due_date')
                ->result();
        
            //GET TERMINATED
            $term = $this->get_unclosed();

        }
        //PULL THE CATALOG

        //CYCLE THROUGH CLIENTS AND ASSEMBLE THE ITEMS
        foreach($res as $key){
            //GET CLIENT NAME
            $data[$key->Id]['name'] = $key->fields->Name;
            //GET CLIENT TASKS
            $data[$key->Id]['tasks'] = $this->fez->db->select('*')->from('task')->where('client = "'.$key->Id.'" AND status=1')->result();
            //INITIATE TOTAL DONE
            $data[$key->Id]['client_time_delivered'] = 0;
            //INITIATE TOTAL OWED 
            $data[$key->Id]['client_time_owed'] = 0;
            //GET THE ITEMS
            $items = $this->fez->se
                ->client($key->Id)
                ->status('LIVE')
                ->period(date('M-y',time()))
                ->math_by('item_id')
                ->include_fields(array('network','action','type'))
                ->go();
            
            
            foreach($items as $x =>  $v){
                if($v['class'] == 'under'){
                    $temp = $v;
                    $temp['name'] = $key->fields->Name;
                    $temp['id'] = $key->Id;
                    $overdue[] = $temp;
                }
            }
            
            
            $data[$key->Id]['items'] = $items;
            //CYCLE THROUGH THE ITEMS
            foreach($items as $id => $elms){
                
                //ADD TO CLIENT GLOBAL
                $data[$key->Id]['client_time_owed'] += $elms['total_time_owed'];
                $data[$key->Id]['client_time_delivered'] += $elms['total_time_delivered'];
            }
            //GET BACKLOG
             $data[$key->Id]['backlog'] = $this->fez->se->client($key->Id)->backlog();
            //GET NYL ITEMS
            $nyl = $this->fez->se
                ->client($key->Id)
                ->nyl();
            //ADD IF EXIST
            if($nyl){
                foreach($nyl as $nyl_item){
                    $nyl_item_trans = $nyl_item;
                    $nyl_item_trans['type'] = 'NYL';
                    $data[$key->Id]['items'][$nyl_item['item_id']] = $nyl_item_trans; 
                }
            }
//            
            //ADD TIME PERCENT AND TIME REMAINING
            if(!empty($items)){
                $data[$key->Id]['client_time_percent'] = round($data[$key->Id]['client_time_delivered']/$data[$key->Id]['client_time_owed']*100*100)/100;
                $data[$key->Id]['client_time_remaining'] = $data[$key->Id]['client_time_owed']-$data[$key->Id]['client_time_delivered'];
            }else{
                $data[$key->Id]['client_time_percent'] = 0;
                $data[$key->Id]['client_time_remaining'] = 0;
            }
        }
        
        //LOAD VIEWS
        $this->fez->load->view('header',array('is_admin'=>$admin));
        //HAS TERMINATED CLIENTS
        if(!empty($term)){
            $this->fez->load->view('board/stopgap',array('term'=>$term));
        }
        
        $this->fez->load->view('board/board',array('is_admin'=>$admin,'overdue'=>$overdue,'tasks'=>$tasks,'client'=>$data,'chart'=>$perc));
        $this->fez->load->view('footer');
    }
    
    
    public function old_board($o=false){
        //GET USER DATA        
        $user = $this->fez->session->get_data(); 
        //INSTANCIATE SE
        $s = new se;
        
        if($o && $user['role_id']->val == 1){
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$o."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $term = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$o."' AND Type = 'Customer - Terminated'");
        }else{
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $term = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type = 'Customer - Terminated'");
        }
        $res = $res->records;
        $term = $this->get_unclosed();
        //PULL THE CATALOG
        $catalog = $this->fez->db->select('*')
            ->from('catalog')
            ->result();
        
        $tasks = $this->fez->db->select('*')
            ->from('task')
            ->where('owner = "'.$user['id']->val.'" AND status=1')
            ->orderby('due_date')
            ->result();

        foreach($res as $key){
            $s = new se;
            $s->load($key->Id);
            //LOAD BASIC DATA
            $data[$key->Id]['name'] = $key->fields->Name;
            //LOAD NOTES
            $data[$key->Id]['tasks'] = $this->fez->db->select('*')->from('task')->where('client = "'.$key->Id.'" AND status=1')->result();
            $total_done = 0;
            $total_due = 0;
            
            if(isset($s->items)){
                $data[$key->Id]['items'] = $s->not_closed_items()->math_items($catalog);
                foreach($data[$key->Id]['items'] as $network => $k){
                    foreach($k as $c){
                        $total_done = $total_done+$c->numbers->completed_time;
                        $total_due = $total_due+$c->numbers->owed_time;
                        
                        if($c->classes == ' under '){
                            $data_hold['network'] = $network;
                            $data_hold['action'] = $c->action;
                            $data_hold['name'] = $key->fields->Name;
                            $data_hold['id'] = $key->Id;
                            $overdue[] =$data_hold;
                        }
                    }
                }
                if($total_done+$total_due != 0){
                    $data[$key->Id]['per'] = round($total_done/($total_done+$total_due)*100);
                }
                $data[$key->Id]['total_done']= $total_done;
                $data[$key->Id]['total_due'] = $total_due;
                //FOR THE CHART
                $finished = $finished + $total_done;
                $owed = $owed + $total_done + $total_due;
                
                $data[$key->Id]['balance'] = $total_due;
                $data[$key->Id]['backlog'] = $s->client_info('unassigned_backlog');
            }else{
                $data[$key->Id]['per'] = 0;
                $data[$key->Id]['total_time'] = 0;
                $data[$key->Id]['balance'] = 0;
                $data[$key->Id]['backlog'] =0;
            }
            
        } 
        if($owed){
            $perc = $finished/$owed;
        }else{
            $perc = 0;
        }
        //LOAD VIEWS
        $this->fez->load->view('header');
        if(!empty($term)){
            $this->fez->load->view('board/stopgap',array('term'=>$term));
        }
        $this->fez->load->view('board/old_board',array('overdue'=>$overdue,'tasks'=>$tasks,'client'=>$data,'chart'=>$perc));
        $this->fez->load->view('footer');
    
    }
    
    
    
    public function network_view($admin = false){
        //GET USER DATA        
        $user = $this->fez->session->get_data(); 
        //CHECK ADMIN
        if($user['role_id']->val != 1 && $admin){
            header( 'Location: /' ) ;
        }
        //IS ADMIN
        if($admin){
            $team = $this->fez->db->select('sfid')
            ->from('user')
            ->where('team = "'.$user['team']->val.'"')
            ->result();
        
            foreach($team as $k => $v){
                $team_ids[] = $v['sfid'];
            }

            $team_ids = "(Social_Media_Exec__c ='".implode("' OR Social_Media_Exec__c ='",$team_ids)."')";
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE ".$team_ids." AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $res = $res->records;
            
            $tasks = $this->fez->db->select('*')
                ->from('task')
                ->where('status=1')
                ->orderby('due_date')
                ->result();
            
        }else{
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            $res = $res->records;
        
            $tasks = $this->fez->db->select('*')
                ->from('task')
                ->where('owner = "'.$user['id']->val.'" AND status=1')
                ->orderby('due_date')
                ->result();
        }
        
         foreach($res as $key){
            //GET CLIENT NAME
            $data[$key->Id]['name'] = $key->fields->Name;
            //GET CLIENT TASKS
            $data[$key->Id]['tasks'] = $this->fez->db->select('*')->from('task')->where('client = "'.$key->Id.'" AND status=1')->result();
            //INITIATE TOTAL DONE
            $data[$key->Id]['client_time_delivered'] = 0;
            //INITIATE TOTAL OWED 
            $data[$key->Id]['client_time_owed'] = 0;
            //GET THE ITEMS
            $items = $this->fez->se
                ->client($key->Id)
                ->status('LIVE')
                ->period(date('M-y'),time())
                ->math_by('item_id')
                ->include_fields(array('network','action','type'))
                ->go();
            
            $data[$key->Id]['items'] = $items;
            //CYCLE THROUGH THE ITEMS
            foreach($items as $id => $elms){
                
                //ADD TO CLIENT GLOBAL
                $data[$key->Id]['client_time_owed'] += $elms['total_time_owed'];
                $data[$key->Id]['client_time_delivered'] += $elms['total_time_delivered'];
            }
            //GET BACKLOG
             $data[$key->Id]['backlog'] = $this->fez->se->client($key->Id)->backlog();
            //GET NYL ITEMS
            $nyl = $this->fez->se
                ->client($key->Id)
                ->nyl();
            //ADD IF EXIST
            if($nyl){
                foreach($nyl as $nyl_item){
                    $nyl_item_trans = $nyl_item;
                    $nyl_item_trans['type'] = 'NYL';
                    $data[$key->Id]['items'][$nyl_item['item_id']] = $nyl_item_trans; 
                }
            }
//            
            //ADD TIME PERCENT AND TIME REMAINING
            if(!empty($items)){
                $data[$key->Id]['client_time_percent'] = round($data[$key->Id]['client_time_delivered']/$data[$key->Id]['client_time_owed']*100*100)/100;
                $data[$key->Id]['client_time_remaining'] = $data[$key->Id]['client_time_owed']-$data[$key->Id]['client_time_delivered'];
            }else{
                $data[$key->Id]['client_time_percent'] = 0;
                $data[$key->Id]['client_time_remaining'] = 0;
            }
        }
        //LOAD VIEWS
        $this->fez->load->view('header',array('is_admin'=>$admin));
        if(!empty($term)){
            $this->fez->load->view('board/stopgap',array('term'=>$term));
        }
        $this->fez->load->view('board/networkview',array('is_admin'=>$admin,'overdue'=>$overdue,'tasks'=>$tasks,'client'=>$data,'chart'=>$perc));
        $this->fez->load->view('footer');
    
    }
    
  
    public function create_item(){
        
        $clients = $this->fez->mongo->find(array('client_name'=>true))
            ->in('records')
            ->where(array('user_id'=>2))
            ->go();
        
        if(isset($_POST['go'])){
            $this->add_item($_POST);
        }
        $this->fez->load->view('header');
        $this->fez->load->view('home/add_item',array('clients'=>$clients));
        $this->fez->load->view('footer'); 
    }
    
    private function add_item($post){
        //CREATE THE ITEM IN MONGO
        $id = $post['client_name'];
        unset($post['client_name']);
        $item = $post;
        $item['item_id'] = uniqid();
        $item['created_date'] = time();
        //ADD KICKERS TO ACTIVITY ARRAY SO WILL ALWAYS UNWIND
        $item['activity'] = array(array(
            'date'=>0,
            'v'=>0
        ));
        //FREQ MANTH
        $item['period'] = array();
        $item['status'] = 'NYL';
        
        $this->fez->mongo->push(array('items'=>$item))
            ->in('records')
            ->where(array('_id'=>new MongoId($id)))
            ->go();
                                                    
        //insert
    }
    
    private function get_unclosed(){
        $user = $this->fez->session->get_data();

        //CHECK FOR TERMINATED CLIENTS
        $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type = 'Customer - Terminated'");
        //$res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='00516000006SFe7' AND Type = 'Customer - Terminated'");
        $term = $res->records;
        
        foreach($term as $key){
            $x = $this->fez->mongo->find(array('sfid'=>1,'client_name'=>1))
                ->in('records')
                ->where(array('sfid'=>$key->Id,'client_status'=>true))
                ->go();
            
            if(!empty($x)){
                $unclosed_client[] = $x;
            }
        }

        if(!empty($unclosed_client)){
            return $unclosed_client;
        }else{
            return array();
        }
        //REASSIGN INCORRECT
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
    
    
    private function get_items($user_id,$clients){
        $catalog = $this->fez->db->select('*')
            ->from('catalog')
            ->result();
        
        $tasks = $this->fez->db->select('*')
            ->from('task')
            ->where('owner = "'.$user_id.'" AND status=1')
            ->orderby('due_date')
            ->result();

        foreach($clients as $key){
            $s = new se;
            $s->load($key->Id);
            //LOAD BASIC DATA
            $data[$key->Id]['name'] = $key->fields->Name;
            //LOAD NOTES
            $data[$key->Id]['tasks'] = $this->fez->db->select('*')->from('task')->where('client = "'.$key->Id.'" AND status=1')->result();
            $total_done = 0;
            $total_due = 0;
            
            if(isset($s->items)){
                $data[$key->Id]['items'] = $s->not_closed_items()->math_items($catalog);
                foreach($data[$key->Id]['items'] as $network => $k){
                    foreach($k as $c){
                        $total_done = $total_done+$c->numbers->completed_time;
                        $total_due = $total_due+$c->numbers->owed_time;
                        
                        if($c->classes == ' under '){
                            $data_hold['network'] = $network;
                            $data_hold['action'] = $c->action;
                            $data_hold['name'] = $key->fields->Name;
                            $data_hold['id'] = $key->Id;
                            $overdue[] =$data_hold;
                        }
                    }
                }
                if($total_done+$total_due != 0){
                    $data[$key->Id]['per'] = round($total_done/($total_done+$total_due)*100);
                }
                $data[$key->Id]['total_done']= $total_done;
                $data[$key->Id]['total_due'] = $total_due;
                //FOR THE CHART
                $finished = $finished + $total_done;
                $owed = $owed + $total_done + $total_due;
                
                $data[$key->Id]['balance'] = $total_due;
                $data[$key->Id]['backlog'] = $s->client_info('unassigned_backlog');
            }else{
                $data[$key->Id]['per'] = 0;
                $data[$key->Id]['total_time'] = 0;
                $data[$key->Id]['balance'] = 0;
                $data[$key->Id]['backlog'] =0;
            }
            
        } 
        if($owed){
            $perc = $finished/$owed;
        }else{
            $perc = 0;
        }
    
        return $data;
    }
}
    
//    $new_data = array('$push'=>array('loc'=>$lead->loc));
