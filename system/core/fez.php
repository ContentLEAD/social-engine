<?php
/*
SUPER OBJECT FEZ THE MIGHTY
*/
//LIBRARY FILES
$libs = array(
        './config/config.php',
        './system/library/database.php',
        './system/library/load.php',
        './system/library/mongo.php',
        './system/library/security.php',
        './system/library/se.php',
        './system/library/twitter.php',
        './system/library/fb.php',
        './system/library/dis.php'
    );
//CORE FILES
$core = array(
    './system/core/route.php',
    './system/core/model.php',
    './system/core/controller.php',
    './system/core/view.php'
);


//LOAD LIBRARIES
foreach($libs as $key => $value){
    include($value);
} 
//LOAD OPTIONAL LIBRARIES
if($config['session']['enabled']){
    include('./system/library/session.php');
}
if($config['salesforce']['enabled']){
    include('./system/library/salesforce.php');
}

class fez {
    //PUBLIC VARS
    public $db;
    public $load;
    public $config;
    public $session;
    public $security;
    public $sforce;
    public $subs;
    public $mongo;
    public $sub_string;

    //FUNCTIONS
    public function run($config){
        $this->config = $config;
        //INCLUDE FILES
        $this->load_stack();
        //GET SUBS FOR ROUTING
        //GET BASE URL INTO ARRAY
        $base = explode('/', BASE_URL);
        //REMOVE URI
        array_shift($base);
        //FIND EXTRA URL SEGMENTS
        $this->subs = array();
        foreach($base as $key =>$value){
            if($value != ''){
                $this->subs[] = $value;
            }
        }
        //CREATE SUBSTRING IF NEEDED FOR SUB DIRECTORY
        if(count($this->subs) >0){
            $this->sub_string = '/'.implode('/',$this->subs);
        }
        //SET SYSTEM GLOBALS
        $GLOBALS['system'] = array(
            'gbl_uri' => $this->sub_string
        );
    }
    //RUNNERS
    private function load_stack(){
        $this->load = new load;
        $this->security = new security($this->config['security']);
        $this->db = new database($this->config['database']);
        //GET CATALOG FOR SE2
        $cat = $this->db->select('*')->from('catalog')->result();
        $this->se = new se($cat);
        //TWITTER
        $this->twitter = new twitter;
        $this->facebook = new fb;
        $this->dis = new dis;
        //LOAD OPTIONAL LIBRARIES
        if($this->config['session']['enabled']){
            $this->session = new session($this->db,$this->config['session']);        
        }
        if($this->config['salesforce']['enabled']){
            $this->sforce = new salesforce($this->config['salesforce']);        
        }
        if($this->config['mongo']['enabled']){
            $this->mongo = new mongo_db($this->config['mongo']);        
        }
    }
    
}
//SET GLOBAL;
$GLOBALS['fez'] = new fez;
$GLOBALS['fez']->run($config);


//INCLUDE CORE
foreach($core as $key => $value){
    include($value);
}

//CHECK FOR NEW DATABASES
$models = scandir('./app/model');
$models = remove_bad_files($models);

if(count($models) > 0){
    loop_models($models,$config);
}

function loop_models($models,$config){
    for( $i = 0; $i<count($models); $i++){
         //TURNS MODELS TO TABLES
        $table = preg_replace('/\\.[^.\\s]{3,4}$/', '', $models[$i]);
        //CHECKS IF TABLE IS STILL THERE
        $res = $GLOBALS['fez']->db->straight_query('SHOW TABLES LIKE "'.$table.'"',true);
        //LOAD THE TABLE
        $GLOBALS['fez']->load->model($table);
        $n = new $table;
        //CHECKS IF NUM ROWS EXIST
        if($res->num_rows == 0){
            echo '<pre>Creating Table '.$table.'</pre>';
            //CREATES THE TABLE
            $n->create_me();
        }else{
            foreach($n->fields as $key => $val){
                $field_sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".$config['database']['DATABASE']."' AND TABLE_NAME = '".$table."' AND COLUMN_NAME = '".$key."'";
                
                $schema = $GLOBALS['fez']->db->straight_query($field_sql,true);
                
                if($schema->num_rows == 0){
                    $sql = 'ALTER TABLE '. $table .' ADD '.$key;
                    if(isset($val['options'])){
                        $sql .=' '.$val['options'];
                    }else{
                        $sql .=' VARCHAR(255)';
                    }
                    $res = $GLOBALS['fez']->db->straight_query($sql,true);   
                    echo '<pre>'.$field_sql.'<br />Creating Field '.$key.' for table '.$table.' - '.$res.'</pre>';
                }
            }
        }
    }
}

function remove_bad_files($f){
    $ret = array();
    foreach($f as $key => $v){
        if(strpos($v,'.php') !== false){
            $ret[] = $v;
        }
    }
    return $ret;
}

//START ROUTING
$r = new route;
