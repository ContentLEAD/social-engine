<?php
//SITE INFORMATION
$config = array();
//GET SITE INFORMATION

//BASE URL
define('BASE_URL', 'socialengine.dev');
//VERSION OF APP
define('VERSION', '0.0.1');
//DESCRIPTION
define('DESCRIPTION' , 'Friendly neighborhood process wizard');

//SET DATABASE INFORAMTION
$config['database']=array(
    'HOST_NAME' =>  'localhost',
    'USER_NAME' =>  '',
    'PASSWORD'  =>  '',
    'DATABASE'  =>  ''
);

//SET DEFAULT ROUTE
$config['route'] = array(
    //DEFAULT CONTROLLER
    'controller' => 'board',
    //DEFAULT METHOD
    'method' =>'index'
);

//SET USER LOGIN SESSION INFORMATION

$config['session']=array(
    //WILL THIS APPLICATION BE GATED
    'enabled' => true,
    //THE SESSION NAME YOU WOULD LIKE TO SAVE SESSIONS UNDER
    'session_name'=> 'f_sess',
    //THE DATABASE TABLE WHERE THE SESSIONS WILL BE SAVED
    //THIS GETS CREATED AUTOMAGICALLY
    'database_session_table_name'=>'sess',
    //THE FALLBACK CONTROLLER A USER GOES TO WHEN THERE IS NOT A SESSION
    'login_controller' => 'gate',
    //EXEMPT CONTROLLERS FROM THE GATING 
    'non_gated_controllers' => array(
        'gate',
        'analytics',
        'builder'
    ),
    'max_session_hours' => 5
);

//MONGODB
$config['mongo']=array(
    'enabled' => true,
    'database' => 'se',
    'address' => 'localhost'
);

//SECURITY SETTINGS
$config['security']= array(
    'hash_type' => 'sha256'
);


//SALESFORCE
$config['salesforce'] = array(
    'enabled' => true,
    'path_to_wsdl' => 'E:\tech-server\socialengine\socialengine/system/clients/salesforce/wsdl.xml',
    'brafton'   => array(
        'username'  => '',
        'password'  => '',
        'security_token'    => ''
        ),
    'castleford'    => array(
        'username'  => '',
        'password'  => '',
        'security_token'    => ''
        )
    
);
//socialenginetest

//SET DEFAULT TIMEZONE
date_default_timezone_set ("America/New_York");