<?php
require_once './system/clients/facebook/autoload.php';

class fb{
    
    //---
    //APP INFORMATION
    //---
    private $app_id = '961696190573808';
    private $app_sec = 'fa72afea6bc37bd1cd76a57e4a4edd98';
    private $default_graph_version = 'v2.5';
    
    //----
    //GET THE SIGNIN INFORMATION
    //----
    public function signin(){
        //GET CONNECTION 
        $fb = $this->get_connection();
        //GET HELPER FUNCTION
        $helper = $fb->getRedirectLoginHelper();
        //SET PERMISSIONS FOR THIS APP
        $permissions = ['publish_actions','manage_pages']; // Optional permissions
        $loginUrl = $helper->getLoginUrl('http://socialengine.dev/index.php/sego/facebook_loader', $permissions);
        //RETURN SIGNIN LINK
        return '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
    }
    
    //------
    //CHECK IF ACCESS TOKEN IS STILL ACTIVE
    //------
    public function check_token($token){
       
    }
    
    //----
    //GET THE FB CONNECTIONS
    //----
    public function get_connection(){
        
        return $fb = new Facebook\Facebook([
          'app_id' => $this->app_id,
          'app_secret' => $this->app_sec,
          'default_graph_version' => $this->default_graph_version
        ]);
    }

}