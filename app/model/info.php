<?php
class info extends model{
    public $fields = array(
        'client'=> array(
            'val'=>null
            ),
        
        'info'=>array(
            'val'=>null,
            'options'=> 'VARCHAR(2000)'
        )
    );
    public $pkey = 'client';
}