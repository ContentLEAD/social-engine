<?php

class security{
    public $config;
    
    public function __construct($config){
        $this->config = $config;
    }
    
    public function hash($string){
        return hash($this->config['hash_type'],$string);
    }

    public function sanitize($elem){
        if(is_array($elem)){
            foreach($elem as $key=>$value){
                $ret[$key]= strip_tags($val);    
            }
            return $ret;
        }
        return strip_tags($elem);        
    }
}