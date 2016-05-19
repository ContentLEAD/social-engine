<?php

class test extends controller{

    public function index(){
        
       $num = $this->fez->dis->orient('WEEK')
            ->month('FEB 2016')
            ->amount(4)
            ->go();
        
        echo $num;
    }

}