<?php

class load{
    
    public function model($model){
        include('./app/model/'.$model.'.php');
    }
    
    public function controller($controller){
        include('./app/controller/'.$controller.'.php');
    }

    public function view($view,$data = array()){
        extract($data);
        extract($GLOBALS['system']);
        if(isset($GLOBALS['user'])){
            extract($GLOBALS['user']);
        }
        include('./app/view/'.$view.'.php');
    }
}