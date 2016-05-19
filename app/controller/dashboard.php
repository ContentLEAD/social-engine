<?php

class dashboard extends controller{


    public function index($admin = false){
        //GET USER DATA
        $u = $this->fez->session->get_data();
        //CHECK ADMIN
        $ad = $admin;
        if($u['role_id']->val != 1 && $admin){
            header( 'Location: /' ) ;
        }
        //CHECK TO MAKE SURE THAT ALL ASSIGNED ITEMS ARE CORRECT
        if(!$admin){
            $this->correct_assignments($u);
        }
        //GET CLIENTS
        if(!$admin){
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$u['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
        }else{
            $team = $this->fez->db->select('sfid')
                ->from('user')
                ->where('team = "'.$u['team']->val.'"')
                ->result();
        
            foreach($team as $k => $v){
                $team_ids[] = $v['sfid'];
            }
            $admin = 9600*count($team_ids);
            $team_ids = "(Social_Media_Exec__c ='".implode("' OR Social_Media_Exec__c ='",$team_ids)."')";
            $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE ".$team_ids." AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
            
        }
        $res = $res->records;
        
        //CATALOG
        foreach($res as $k){
            $clients[] = array('sfid'=>$k->Id);
        }
        
        //DASHBOARD ITEMS
        $data = array(
            'completion'=>  $this->completion($clients),
            'breakdown'=> $this->break_down($clients),
            'time_per'=> $this->time_alloted($clients,$admin),
            'daily_line'=> $this->daily_activity($clients),
            'completion_global'=> $this->completion_global()
           // 'to_date_chart' =>$this->to_date_chart($clients)
        );

        $this->fez->load->view('header',array('is_admin'=>$ad));
        $this->fez->load->view('dash/new_dash',$data);
        $this->fez->load->view('footer');
    }

    
    
    private function time_alloted($clients,$admin){
        $ret = $this->fez->se
            ->client($clients)
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->math_by('status')
            ->go();
        if($admin){
            return round(($ret['LIVE']['total_time_owed']/$admin)*100*100)/100;
        }
        return round(($ret['LIVE']['total_time_owed']/9600)*100*100)/100;
    
    }
    
    
    private function break_down($clients){
                
        $res = $this->fez->se
            ->client($clients)
            ->status('LIVE')
            ->math_by('network')
            ->period(date('M-y',time()))
            ->go();
        foreach($res as $net => $math){
            $chart[] = array('name'=>$net,'y'=>$math['total_owed']);
        }
        return json_encode($chart);
    }
    
    
    private function daily_activity($clients){
        
        $res = $this->fez->se
            ->start_date(strtotime('midnight first day of this month'))
            ->end_date(time())
            ->date_group('m-d-y')
            ->sort_by(array('date'=>1))
            ->go();
        
        foreach($res as $k => $v){
            $cats[] = $k; 
            $acts[] = $v; 
        }
        return array('cats'=>json_encode($cats),'acts'=>json_encode($acts));
        
    }
    
    
    
    private function completion($clients){
        
        $res = $this->fez->se
            ->client($clients)
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->math_by('status')
            ->go();
        
        return $res['LIVE']['item_time_percent'];
    }
    
    
    private function completion_global(){
        
        $res = $this->fez->se
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->math_by('user_id')
            ->go();
        
        foreach($res as $u_id => $math){
            $per_comp = $math['item_time_percent'];
            
            $name = $this->fez->db->select('CONCAT(first_name," ",last_name) as name')
                ->from('user')
                ->where('id='.$u_id)
                ->row();
            $name = $name['name'];
            
            $sorted[] = array($name,$per_comp);
        }        
        $chart =$this->fix_order($sorted,'desc',1);
        return json_encode($chart);
    }
    
    
//    public function correct($id){
//        $u = $this->fez->db->select('sfid')
//            ->from('user')
//            ->where('id='.$id)
//            ->row();
//        $sfid = $u['sfid'];
//        
//        $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$sfid."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
//        $assigned = $res->records;
//        
//        foreach($assigned as $k){   
//            //RECORDS
//            $this->fez->mongo->set(array('user_id'=>$id))
//                ->in('records')
//                ->where(array('sfid'=>$k->Id,'user_id'=>array('$ne'=>$id)))
//                ->options(array('multiple'=>true))
//                ->go();
//            //ITEMS
//            $this->fez->mongo->set(array('user_id'=>$id))
//                ->in('items')
//                ->where(array('sfid'=>$k->Id,'user_id'=>array('$ne'=>$id)))
//                ->options(array('multiple'=>true))
//                ->go();
//            //COMMITS
//            $this->fez->mongo->set(array('user_id'=>$id))
//                ->in('commits')
//                ->where(array('sfid'=>$k->Id,'user_id'=>array('$ne'=>$id)))
//                ->options(array('multiple'=>true))
//                ->go();
//        }
//    
//    }
    
    private function correct_assignments($user){
        
        //CHECK FOR CLIENTS INCORECTLY ASSIGNED
        $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$user['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
        $assigned = $res->records;
        
        foreach($assigned as $key){
            //RECORDS
            $this->fez->mongo->set(array('user_id'=>$user['id']->val))
                ->in('records')
                ->where(array('sfid'=>$key->Id,'user_id'=>array('$ne'=>$user['id']->val)))
                ->options(array('multiple'=>true))
                ->go();
            //ITEMS
            $this->fez->mongo->set(array('user_id'=>$user['id']->val))
                ->in('items')
                ->where(array('sfid'=>$key->Id,'user_id'=>array('$ne'=>$user['id']->val)))
                ->options(array('multiple'=>true))
                ->go();
            //COMMITS
            $this->fez->mongo->set(array('user_id'=>$user['id']->val))
                ->in('commits')
                ->where(array('sfid'=>$key->Id,'user_id'=>array('$ne'=>$user['id']->val)))
                ->options(array('multiple'=>true))
                ->go();
            
        }
    }
        
    private function fix_order($array,$dir,$place = 0){
        $res = array();
        
        foreach($array as $key => $val){
            $num[] = (int)$val[$place];
        }
        if($dir == 'asc'){
            asort($num);
        }else{
            arsort($num);
        }
        foreach($num as $key => $val){
            $res[] = $array[$key];
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
}

