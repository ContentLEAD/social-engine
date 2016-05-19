<?php

class session{
    //BECAUSE THE SESSION LIBRARY NEEDS TO INTERACT WITH THE DATABASE
    //IT NEEDS ITS OWN CONNECTION
    private $db;
    private $config;
    
    public function __construct($db,$config){
        //SET CONFIG
        $this->config = $config;
        //SET DATABASE
        $this->db = $db;
        //CHECK FOR TABLE
        $res = $this->db->straight_query('SHOW TABLES LIKE "'.$this->config['database_session_table_name'].'"',true);
        if($res->num_rows == 0){
            $this->create_session_table();
        }else{
            //REMOVE OLD SESSIONS
            $exp = time() - $this->config['max_session_hours']*60*60;
            $this->db->delete($this->config['database_session_table_name'])
                ->where('timestamp <'.$exp)
                ->go();
        }
        session_start();
    }
    
    /*=======
    CREATE THE SESSION
    @PARAMS ARRAY OF DATA TO BE STORED AS A JSON
    SETS SESSION ON SERVER AND IN DATABASE TABLE
    -->NEEDS TO BE CONFIGURED;
    =========*/
    
    public function set($data = NULL){
        //GENERATE THE UNIQUE ID
        $session_id = $this->generate();
        //CREATE JSON;
        if(!is_null($data)){
            $data = json_encode($data);
        }
        //GET IP ADDRESS
        $ip = $this->get_ip();
        //THIS SET IN DATABASE
        $this->db->insert(array(
            'session_id' => $session_id,
            'ip_address' => $ip,
            'timestamp' => time(),
            'data' => $data
        ))
            ->into($this->config['database_session_table_name'])
            ->go();
        //SET THE SERVER SESSION
        $_SESSION[$this->config['session_name']] = $session_id;
        return $session_id;

    }
    
    /*=======
    SIMPLE DESTROY CALL
    @PARAMS NONE
    DESTROYS SESSION ON SERVER AND IN DATABASE TABLE
    =========*/
    
    public function destroy(){
        $session_id = $_SESSION[$this->config['session_name']];
        //REMOVE FROM THE DB
        $this->db->delete($this->config['database_session_table_name'])
            ->where('session_id = "'.$session_id.'"')
            ->go();
        //DESTROY SESSION
        session_unset($_SESSION[$this->config['session_name']]);
        session_destroy();
    }

    /*=======
    CHECK TO SEE IF SESSION EXISTS AND IF VALID IN DATABASE
    @PARAMS NONE
    RETURNS BOOL
    =========*/
    
    public function check(){
        //NO SESSION IS SET
        if(!isset($_SESSION[$this->config['session_name']])){
            return false;
        }
        //GET SESSION FROM DATABASE
        $session_id = $_SESSION[$this->config['session_name']];
        $res = $this->db->straight_query("SELECT * FROM ".$this->config['database_session_table_name']." WHERE Session_id ='".$session_id."'");
        //NOT IN DATABASE
        if(!$res->success){
            return false;
        }
        //SET GLOBALS
        $json = $this->db->select('data')
            ->from($this->config['database_session_table_name'])
            ->where('session_id = "'.$session_id.'"')
            ->row();
        $json = (array)json_decode($json['data']);
        foreach($json as $key =>$val){
            $user['gbl_'.$key] = $val->val;
        }
        unset($user['password']);
        $GLOBALS['user'] = $user;
        
        //ELSE PASS
        return true;
    }
    
    //GET THE DATA FROM THE SESSION
    public function get_data(){
        //GET SESSION ID
        $session_id = $_SESSION[$this->config['session_name']];
        $data = $this->db->select('data')
            ->from($this->config['database_session_table_name'])
            ->where('session_id = "'.$session_id.'"')
            ->row();
        if($data){
            return (array)json_decode($data['data']);
        }

    }
    
//-----------------PRIVATE FUNCTIONS --------------------//
    
    private function create_session_table(){
       $sql =" CREATE TABLE ".$this->config['database_session_table_name']." (
            session_id VARCHAR(255) PRIMARY KEY,
            ip_address VARCHAR(255),
            timestamp VARCHAR(255),
            data VARCHAR(2000)
            )";
        $this->db->straight_query($sql);
    }
    
    private function generate(){
        $chars = '1234567890abcdefghijklmnopqrstuvwxyz!@#$%^&*()_+?|}{][<>.,';

        for ($p = 0; $p < 40; $p++)
        {
            $result .= ($p%2) ? $chars[mt_rand(19, 40)] : $chars[mt_rand(0, 18)];
        }

        return $result;

    }
    
    private function get_ip(){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}