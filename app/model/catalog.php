<?php

class catalog extends model{
    public $pkey = 'id';
    public $fields = array(
        'id'=> array(
            'val'=>null,
            'options'=>'INT(10) AUTO_INCREMENT'
        ),
        'dept'=>array(
            'val'=>null
        ),
        'status'=>array(
            'val'=>null
        ),
        'network'=>array(
            'val'=>null
        ),
        'action'=>array(
            'val'=>null
        ),
        'time'=>array(
            'val'=>null
        )
    );

}