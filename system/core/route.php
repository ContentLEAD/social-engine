<?php
class route{
    
    public $route;
    public $fez;
    public $controllers;
    
    public function __construct(){
        
        $this->fez = $GLOBALS['fez'];
        //GET CONTROLLERS
        $controllers = scandir('./app/controller');
        //LOOP AND CHECK FOLDERS
        for( $i = 2; $i<count($controllers); $i++){
            $c = preg_replace('/\\.[^.\\s]{3,4}$/', '', $controllers[$i]);
            $this->controllers[$c] = true;
        }
        
        //GET THE SERVERPATH
        $_SERVER['REQUEST_URI_PATH'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->route = explode('/', $_SERVER['REQUEST_URI_PATH']);
        $temp_array = array();
        //FORMAT ROUTE
        //REMOVE EXTRA SEGS AND BLANKS
        foreach($this->route as $key => $value){
            if($value != '' && !in_array($value,$this->fez->subs) && $value != 'index.php')
            {
                $temp_array[] = $value;
            }        
        }
        
        $this->route = $temp_array;
                      
        if(!isset($this->route[0])){
            $this->load_default();    
            return;
        }
        
        //LOAD THE ROUTE;
        $this->load_route();
    }

    private function load_route(){
        //CHECK FOR CONTROLLER
        //OTHER WISE 404
        
        if(!isset($this->controllers[$this->route[0]])){
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            die( 'not found 404');
        }
        //
        $this->fez->load->controller($this->route[0]);
        $x = new $this->route[0];

        //RUN THE DEFINED METHOD
        if(isset($this->route[1])){
            //METHOD WITH PARAMS
            if(isset($this->route[3])){
                $x->{$this->route[1]}($this->route[2],$this->route[3]);
            }else if(isset($this->route[2])){
                $x->{$this->route[1]}($this->route[2]);
            }else{
                //METHOD NO PARAMS
                $x->{$this->route[1]}();
            }
        }
        //LOAD CONTROLLER INDEX
        else{
            $x->index();
        }
    }
    
    private function load_default(){
        $this->fez->load->controller($this->fez->config['route']['controller']);
        $x = new $this->fez->config['route']['controller'];
        $x->{$this->fez->config['route']['method']}();
    }
}