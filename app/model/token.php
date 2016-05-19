<?php
class token extends model{
    public $fields = array(
        'id'=> array(
            'val'=>null,
            'options'=> 'INT AUTO_INCREMENT',
            'locked' =>true
            ),
        
        'sfid'=>array(
            'val'=>null
        ),
        'network'=>array(
            'val'=>null
        ),
        'tokens'=>array(
            'val'=>null,
            'options'=> 'VARCHAR(2000)'
        ),
        'expiration'=>array(
            'val'=>null
        )
    );
    public $pkey = 'id';
}