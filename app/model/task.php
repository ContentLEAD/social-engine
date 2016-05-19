<?php

class task extends model{
    public $fields = array(
        'id'=> array(
            'val'=>null,
            'options'=> 'INT AUTO_INCREMENT',
            'locked' =>true
            ),
        
        'note'=>array(
            'val'=>null
        ),
        
        'owner'=>array(
            'val'=>null,
            'options'=>'INT(10)'
        ),
        'status'=>array(
            'val'=>null
        ),
        'subject'=>array(
            'val'=>null
        ),
        'client'=>array(
            'val'=>null
        ),
        'client_name'=>array(
            'val'=>null
        ),
        'created_by'=>array(
            'options'=>'INT(10)'
        ),
        'created_date'=>array(
            'options'=>'INT(20)'
        ),
        'due_date'=>array(
            'options'=>'INT(20)'
        ),
        'closed_date'=>array(
            'options'=>'INT(20)'
        )
    );
    public $pkey = 'id';
}
