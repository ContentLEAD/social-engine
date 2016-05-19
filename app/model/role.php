<?php

class role extends model{
    public $fields = array(
        'id'=> array(
            'val'=>null,
            'options'=> 'INT AUTO_INCREMENT',
            'locked' =>true
            ),
        'name'=>array(
            'val'=>null
        )
    );
    public $pkey = 'id';
}
