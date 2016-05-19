<?php

class model{
    private $con;
    public $fez;
    
    //SUPER CONSTRUCTOR
    public function __construct(){
        $this->fez =$GLOBALS['fez'];
    }
    //CRUD
    
    //SAVE
    public function save(){
        if( !$this->indb( $this->fields[$this->pkey]['val'] ) ){
            $this->create();
        }else{
            $this->update();
        }
    }
    private function indb($key_val){
        
        if(is_string($key_val)){
            $key_val = "'".$key_val."'";
        }
        
        $res = $this->fez->db->select($this->pkey)
            ->from(get_class($this))
            ->where( $this->pkey.'='.$key_val )
            ->row();
        
        return $res;
    }
    
    //UPDATE RECORD
    private function update(){
        $pkey = $this->fields[$this->pkey]['val'];
        if(is_string($pkey)){
            $pkey = "'".$pkey."'";
        }
        foreach($this->fields as $key => $value){
            if(!isset($value['locked'])){
                $data[$key] = $value['val'];
            }
        }
        //DB CALL
        $w = $this->fez->db->update(get_class($this))
                ->set($data)
                ->where($this->pkey.' = '.$pkey)
                ->go();
    }
    //CREATE RECORD
    private function create(){        
        foreach($this->fields as $key => $value){
            $data[$key] = $value['val'];
        }
        
        $id = $this->fez->db->insert($data)
                ->into(get_class($this))
                ->go(true);
        
        //AUTO LOAD THE OBJECT
        $this->load($id);
    }
    
    //POPULATE
    public function populate($post){
        foreach($this->fields as $key => $val){
            if(isset( $post[$key] ) && !isset($val['locked'])){
                $this->fields[$key]['val'] = $post[$key];    
            }
        }
    }
    
    //CHEATER FUNCTION
    public function set($field, $value){
        if(!isset($this->fields[$field]['locked'])){
            $this->fields[$field]['val'] = $value;
        }
    }
    
    //POPULATES A SINGLE RECORD USING THE RECORDS ID
    public function load($id){
        //GIVEN POST DATA, DO NOT RETRIEV
        $item = $this->fez->db->select('*')
            ->from(get_class($this))
            ->where($this->pkey.'='.$id)
            ->row();
        if($item){
            foreach($item as $key => $val){
                $this->fields[$key]['val'] = $val;
            }
        }
    }
    
    public function get($field){
        return $this->fields[$field]['val'];
    }
    
    //CORE DATABASE CREATIONS
    //
    //CREATES A NEW TABLE FROM A MODEL FILE
    public function create_me(){
        $sql = 'CREATE TABLE '.get_class($this).'( ';

        foreach($this->fields as $key => $val){
            $sql .=$key.' ';
            if(isset($val['options'])){
               $sql .= $val['options'];
            }else{
                $sql .= 'varchar(255)';
            }
            $sql .=',';
        }
        $sql .= 'PRIMARY KEY('.$this->pkey.') )';
        
        $res = $this->fez->db->straight_query($sql);
        if(!$res){
            //ERROR OUT IF THER IS A PROBLEM
            $this->error_out($res->error);
        }
    }
}