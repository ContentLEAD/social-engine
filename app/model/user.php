<?php

class user extends model{
    public $fields = array(
        'id'=> array(
            'val'=>null,
            'options'=> 'INT AUTO_INCREMENT',
            'locked' =>true
            ),
        'first_name'=>array(
            'val'=>null
        ),
        'last_name'=>array(
            'val'=>null
        ),
        'email'=>array(
            'val'=>null,
        ),
        'password'=>array(
            'val'=>null,
            'locked'=>true
        ),
        'role_id'=>array(
            'val'=>null,
            'options'=>'INT(10)'
        ),
        'sfid'=>array(
            'val'=>null,
        ),
        'team'=>array(
            'val'=>null,
        )
    );
    public $pkey = 'id';
}
