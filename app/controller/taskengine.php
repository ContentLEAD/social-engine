<?php

class taskengine extends controller{
    
    public function index(){}
    
    public function create(){
        $u = $this->fez->session->get_data();
        $t = new task;
        if( isset( $_POST['note'] ) ){
            
            if($_POST['client'] != 'null'){
                $t->set('client',$_POST['client']);
            }
            
            $t->set('created_by',$u['id']->val);   
            $t->set('created_date',time());   
            if($_POST['due_date'] != ''){
                $t->set('due_date',strtotime($_POST['due_date']));   
            }else{
                $t->set('due_date',null);   
            }
            if($_POST['owner'] != 'null'){
                $t->set('owner',$_POST['owner']);
            }else{
                $t->set('owner',$u['id']->val);
            }
            
            $t->set('note',$_POST['note']);
            $t->save();
            echo 'note <pre>';
            var_dump($_POST);
            return;
        }
        $res = $this->fez->sforce->brafton->query("SELECT Name, Id FROM Account WHERE Social_Media_Exec__c ='".$u['sfid']->val."' AND Type LIKE 'Customer%' AND Type != 'Customer - Terminated'");
        $clients = $res->records;
        $teammates = $this->fez->db->select('id,first_name,last_name')
            ->from('user')
            ->where('role_id != 0 AND id !='.$u['id']->val)
            ->orderby('last_name')
            ->result();
        
        $data = array(
            'user_id'=> $u['id']->val,
            'user_name'=> $u['first_name']->val.' '.$u['last_name']->val,
            'clients'=>$clients,
            'teammates'=>$teammates
        );

        $this->fez->load->view('header');
        $this->fez->load->view('taskengine/create',$data);
        $this->fez->load->view('footer');
    }
    
    public function delete(){}
    
    public function close(){}
    
}