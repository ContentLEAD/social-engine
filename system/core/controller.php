<?php

class controller{
    public $fez;
    
    public function __construct(){
        $this->fez = $GLOBALS['fez'];
        //GATED CONTENT?
        if($this->fez->config['session']['enabled']){
            //CLASS IS OMITED FROM GATING
            if(is_bool(array_search(get_class($this),$this->fez->config['session']['non_gated_controllers']))){
                //CHECK THE SESSION REDIRECT TO DEFALUT;
                if(!$this->fez->session->check()){
                    header('Location: '.$this->fez->sub_string.'/index.php/'.$this->fez->config['session']['login_controller']);
                }
            }
        }
    
    }
    
}