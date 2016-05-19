<?php 

class sego extends controller{
    
    /*
        FACEBOOK LOADER WILL LOAD OR REPLACE NEW TOKENS.
    */
    
    public function facebook_loader($sfid = 0){
        //IS CALLBACK
        if(isset($_GET['code'])){
            //GET FACEBOOK CONNECTION
            $fb= $this->fez->facebook->get_connection();
            //GET HELPER
            $helper = $fb->getRedirectLoginHelper();
            //GRAB ACCESS TOKEN
            $accessToken = $helper->getAccessToken();
            //GET EXPIRATION DATE
            $exp = $accessToken->getExpiresAt();
            //CONVERT TO TIMESTAMP
            $expiration = strtotime( $exp->format('M-d-y') );
            //CHECK IF EXISTS
            $exists = $this->fez->db->select('*')
                ->from('token')
                ->where('network = "FACEBOOK" AND sfid="'.$_SESSION['fb_sfid'].'"')
                ->row();
            //REPLACING 
            if($exists){
                //REPLACE
                $t = new token;
                $t->load($exists['id']);
                //SET NEW INFORMATION
                $t->set('tokens',(string)$accessToken);
                $t->set('expiration',$expiration);
                //SAVE
                $t->save();
            }else{
                //NEW
                $data = array(
                    'sfid'=> $_SESSION['fb_sfid'],
                    'network'=>'FACEBOOK',
                    'tokens'=>(string) $accessToken,
                    'expiration'=>$expiration
                );
                //INSERT
                $this->fez->db->insert($data)
                    ->into('token')
                    ->go();
            }
            return;
        }
        //NOT CALLBACK
        $_SESSION['fb_sfid'] = $sfid;
        //GET SIGNING
        $signin = $this->fez->facebook->signin();
        
        $this->fez->load->view('header');
        $this->fez->load->view('sego/facebook',array('signin'=>$signin));
        $this->fez->load->view('footer');
    }
    
    /*
        FACEBOOK POST 
    */
    
    public function facebook_post(){
        $token = $this->fez->db->select('tokens')
                ->from('token')
                ->where('sfid="001G000001i2KpoIAE" AND network="FACEBOOK"')
                ->row();
        $token = $token['tokens'];
        
        $linkData = [
          'link' => 'http://www.example.com',
          'message' => 'New Post',
          ];
        
        $fb= $this->fez->facebook->get_connection();
        
        $response = $fb->post('/me/feed', $linkData, $token);
        
        echo '<pre>';
        var_dump($token);
    
    }

    public function check_token(){
        
        $token = $this->fez->db->select('tokens')
                ->from('token')
                ->where('sfid="001G000001i2KpoIAE" AND network="FACEBOOK"')
                ->row();
        $token = $token['tokens'];
        
        $test = $this->fez->facebook->check_token($token);
        
        echo '<pre>';
        var_dump($test);
    }
    
    /*
        TWITTER
    */
    
    public function twitter($sfid){
        $token = $this->fez->db->select('*')
            ->from('token')
            ->where('sfid="'.$sfid.'" AND network="TWITTER"')
            ->row();
        
        if(!$token){
            $ret['signin'] = $this->fez->twitter->signin($sfid);
            $ret['auth'] = false;
        }else{
            $ret['auth'] = true;
        }
        echo json_encode($ret);
    }
    
    
    public function twittercallback(){
        
        $x = $this->fez->twitter->callback($_REQUEST['oauth_token'],$_REQUEST['oauth_verifier']);
        if(isset($x['oauth_token'])){
            $data= array(
                'sfid'   =>  $_SESSION['tw_sfid'],
                'network'=>  'TWITTER',
                'tokens' =>  json_encode($x)
            );
            $this->fez->db->insert($data)
                ->into('token')
                ->go();
            header('location: /index.php/board/#'.$_SESSION["tw_sfid"]);
        }
        echo 'Something went wrong';
    }
    
    public function get_timeline(){
        $token = $this->fez->db->select('tokens')
                ->from('token')
                ->where('sfid="001G000001i2KpoIAE " AND network="TWITTER"')
                ->row();
        $token = json_decode($token['tokens']);
        $connection = $this->fez->twitter->get_connection($token);
        $statuses = $connection->get("statuses/user_timeline", ["screen_name" => "BrightClaim"]);
        echo '<pre>';
        var_dump($statuses);
    }
    
    
    public function tweet(){
        //GRAB TOKENS
        $token = $this->fez->db->select('tokens')
                ->from('token')
                ->where('sfid="'.$_POST['sfid'].'" AND network="TWITTER"')
                ->row();
        //PARSE
        $token = json_decode($token['tokens']);
        
        //GET API CONNECTION
        $connection = $this->fez->twitter->get_connection($token);
        //TWEET
        $statues = $connection->post("statuses/update", ["status" => $_POST['post']]);
        //RETURN 
        if(isset($status->created_at)){
            echo '{"success":true}';
        }else{
            echo '{"success":false}';
        }
        
    }
}