<?php
require_once ('./system/clients/twitter/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

class twitter {
    
    //---
    //APPLICATION DETAILS
    //---
    
    private $c_key = 'XYRvHAndh9qS6pg91S9THEFBB';
    private $c_sec = 'inpVCC01P5XYn4a0VlAi9PPoauP9xIOaXsdjAm1HGokNjYpF1H';
    private $oauth_callback = 'http://socialengine.dev/index.php/sego/twittercallback/';
    //---
    //SEGO FUNCTIONS
    //---
    
    public function signin($sfid){
        $_SESSION['tw_sfid'] = $sfid;
        //CREATE INITIAL CONNECTION
        $connection = new TwitterOAuth($this->c_key, $this->c_sec);
        //REQUEST TOKEN
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $this->oauth_callback));
        //STORE AS SESSION DATA
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        return '<a href="'.$url.'">Signin</a>';
    }

    public function callback($returned_token, $return_verifier){
        //GRAB SESSION DATA
        $request_token['oauth_token'] = $_SESSION['oauth_token'];
        $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];
        //CHECK FOR ERRORS
        if (isset($returned_token) && $request_token['oauth_token'] !== $returned_token) {
            // ABORT
            return 'Error: Bad Tokens';
        }
        //GENERATE NEW CONNECTION AND GRAB ACCESS TOKENS
        $connection = new TwitterOAuth($this->c_key, $this->c_sec, $request_token['oauth_token'], $request_token['oauth_token_secret']);
        $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $return_verifier]);
        return $access_token;
    }
    
    public function get_connection($tokens){
        return new TwitterOAuth($this->c_key, $this->c_sec, $tokens->oauth_token, $tokens->oauth_token_secret);
    }
    
}