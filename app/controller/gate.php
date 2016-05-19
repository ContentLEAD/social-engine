<?php
class gate extends controller{

    public function override(){
        $data = array();
        //LOOK FOR POST
        if(isset($_POST['email'])){
            $res = $this->auth($_POST);
            if(!$res['success']){
                $data['error'] = $res['error'];
            }
        }
        //LOAD LOGIN SCREEN
        $this->fez->load->view('header');
        $this->fez->load->view('gate/login',$data);
        $this->fez->load->view('footer');
    }
    
    public function index(){
        $data = array();
        //LOOK FOR POST
        if(isset($_POST['email'])){
            $res = $this->auth($_POST);
            if(!$res['success']){
                $data['error'] = $res['error'];
            }
        }
        
        //LOAD LOGIN SCREEN
        $this->fez->load->view('header');
        $this->fez->load->view('gate/sf',$data);
        $this->fez->load->view('footer');       
    
    }
    
    public function add_user(){
        $data = array();
        if(isset($_POST['email'])){
            $result = $this->add($_POST);
            if($result['success']){
                //RELOAD
                header('Location: /index.php');
            }else{
                $data['error'] = $result['error'];
            }
        }
        
        $this->fez->load->view('header');
        $this->fez->load->view('gate/add_user',$data);
        $this->fez->load->view('footer');
        
    }
    
    public function oauth(){
        
        $token_uri = 'https://login.salesforce.com/services/oauth2/token';
        $code = $_GET['code'];

        if (!isset($code) || $code == "") {
            die("Error - code parameter missing from request!");
        }

        $params = "code=" . $code
            . "&grant_type=authorization_code"
            . "&client_id=" . '3MVG9yZ.WNe6byQCMmYGSGT6ArxOx.Tle4pncwhPae4TRqDgP6iTQHF9BchqKXaWonUohsBjEUG2CdoVXmR0Q'
            . "&client_secret=" . '2315589764122786040'
            . "&redirect_uri=" . 'https://tech.brafton.com/socialengine/index.php/gate/oauth/';

        $curl = curl_init($token_uri);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);


        $json_response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ( $status != 200 ) {
            die("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        }

        curl_close($curl);

        $response = json_decode($json_response, true);

        $access_token = $response['access_token'];
        $instance_url = $response['instance_url'];


        if (!isset($access_token) || $access_token == "") {
            die("Error - access token missing from response!");
        }

        if (!isset($instance_url) || $instance_url == "") {
            die("Error - instance URL missing from response!");
        }

        echo '<pre>';
        $id_array = explode('/', $response['id']);
        $sfid = $id_array[5];
        $user = $this->is_user($sfid);
        if($user){
            $u = new user;
            $u->load($user['id']);
            $this->set($u);
        }else{
            $this->create_user($sfid);
        }
        
    
    }


    public function logout(){
        $this->fez->session->destroy();
        header('Location: http://social.brafton.com/index.php/');
    }
    
    
    private function create_user($sfid){
        $q = "SELECT FirstName,LastName,Email,CompanyName FROM User WHERE Id ='".$sfid."'";
        $res = $this->fez->sforce->brafton->query($q);
        $res = $res->records;
        $companyname = strtoupper($res[0]->fields->CompanyName);
        $companyname = str_replace(' ', '', $companyname);
        $u = new user;
        $u->set('sfid',$sfid);
        $u->set('first_name',$res[0]->fields->FirstName);
        $u->set('last_name',$res[0]->fields->LastName);
        $u->set('email',$res[0]->fields->email);
        $u->set('team',$companyname);
        $u->save();
        //GET THAT USER
        $user = $this->is_user($sfid);
        //load user into object
        $u = new user;
        $u->load($user['id']);
        //SET THE SESSION
        $this->set($u);
    }
    
    public function reset_session($ses){
        $ses = urldecode($ses);
        $check = $this->fez->db->select('*')
            ->from('sess')
            ->where('session_id= "'.$ses.'"')
            ->row();
        
        if($check){
            $_SESSION['f_sess'] = $ses;
            $u = $this->fez->session->get_data();
            $this->correct_assignments($u);
        }
        header('Location: http://social.brafton.com');
    }
    
    //CORRECT ASSIGNMENTS
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
    //SET THE SESSION
    private function set($u){
        $ses = $this->fez->session->set($u->fields);
        header('Location: http://social.brafton.com/index.php/gate/reset_session/'.urlencode($ses));
    }
    
    
    private function is_user($sfid){
        $res = $this->fez->db->select('*')
            ->from('user')
            ->where('sfid = "'.$sfid.'"')
            ->row();
        return $res;
    }
    
    private function add($post){
        //CHECK FOR CURRENT USER
        $current_user = $this->fez->db->select('id')
            ->from('user')
            ->where('email ="'.$post['email'].'"')
            ->row();
        if($current_user){
            return array('success'=>false,'error'=>'User Already Exists');
        }
        //LOAD NEW USER
        $u = new user;
        $u->populate($post);
        //SALESFORCE
        $res = $this->fez->sforce->brafton->query("SELECT Id FROM user WHERE IsActive = true AND (email ='".$post['email']."' OR username='".$post['email']."')");
        $res = $res->records;
        
        if(empty($res)){
            return array('success'=>false,'error'=>'User Not Active in Salesforce');
        }
        //SET SALESFORCE DIRECTLY
        $u->fields['sfid']['val'] = $res[0]->Id;
        //HASH PASSWORD
        //PASSWORD IS LOCKED DOWN SO NEED TO BE DIRECTLY SET
        $u->fields['password']['val'] = $this->fez->security->hash($_POST['password']);
        $u->save();
        return $result['success'] = true;
    }
    
    
    private function auth($post){
        //LOAD NEW USER
        $u = new user;
        //HASH PASSWORD
        $hashed_password = $this->fez->security->hash($post['password']);
        //
        $res = $this->fez->db->select('*')
            ->from('user')
            ->where("email ='".$post['email']."'")
            ->row();
        //
        if(!$res){
            $data['success']= false;
            $data['error'] = 'No Such User'; 
            return $data;
        }
        //LOAD THIS USER
        $u->load($res['id']);
        //CHECK HASHED PASSWORDS
        if($hashed_password == $u->get('password')){
            $u->load($res['id']);
            $this->fez->session->set($u->fields);
            header('Location: /index.php');
        }else{
            $data['success']= false;
            $data['error'] = 'Password not Correct';
            return $data;
        }
    }
}