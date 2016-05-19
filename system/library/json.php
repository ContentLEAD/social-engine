<?php
class json { 
    public function encode_safe($array){
        $json = json_encode($array);
        return addslashes($json);
    }
    public function decode($string){
        $json = json_decode($string);
        return $json;
    }
}