<?php

class database {
    /*DATABASE LIBRARY
    THIS GETS LOADED INTO THE FEZ SUPER OBJECT
    */
    
    /*DATABASE CONNECTION*/
    private $con;
    public $config;
    public $sql;
    
    //CONSTRUCTOR FUNCTION
    public function __construct($config){
        $this->config = $config;
        $this->connect();
        $this->sql = '';
    }

    
/*
*==============================================
*CONNECT TO DATABASE AND STORE THE CONNECTION THROUGHOUT THE LOAD
*==============================================
*/
    
    private function connect(){
        //CONNECT TO THE DATABASE
        $this->con = mysqli_connect($this->config['HOST_NAME'], $this->config['USER_NAME'], $this->config['PASSWORD'], $this->config['DATABASE']);
        
        //CONNECTION ISSUE?
        if(!$this->con){
            echo 'DB CONNECTION FAILURE';
        } 
    }
    
/*
*==============================================
*STRAIGHT QUERY
*==============================================
*/
    public function straight_query($query,$raw = false){
        $res = $this->con->query($query);
        if($raw){
            return $res;
        }
        return $this->format($res);
    }
    //FORMAT
    private function format($res){
        $ret = array();
        $x = new stdClass;
        if($res->num_rows == 0){
            $x->success = false;
            return $x;
        }
        while ($obj = mysqli_fetch_assoc($res)){
            $ret[] = $obj;
        }
        $x->success = true;
        $x->records = $ret;
        return $x;
    }
/*
*==============================================
*QUERY RELAY
*==============================================
*/    
    //SELECT
    public function select($str){
        $this->sql = 'SELECT '.$str.' ';
        return $this;
    }
    //FROM
    public function from($table){
        $this->sql .= 'FROM '.$table.' ';
        return $this;
    }
    //WHERE
    public function where($str){
        $this->sql .= 'WHERE '.$str;
        return $this;
    }
    //OEDER BY
    public function orderby($str){
        $this->sql .= ' ORDER BY '.$str;
        return $this;
    }
    //INSER CLOSER
    //TAKES ASSOC ARRAY
    public function insert($data){
        $this->sql = '(';
        $fields = array_keys($data);
        $this->sql .= implode(",", $fields).') VALUES (';
        foreach($data as $key => $value){
            if(is_string($value)){
                $vals[] = "'".$this->con->real_escape_string($value)."'";
            }else if(is_null($value)){
                $vals[] = 'NULL';
            }else{
                $vals[] = $this->con->real_escape_string($value);
            }
        }
        $this->sql .= implode(",",$vals).')';
        return $this;
    }
     //INSERT
    public function into($str){
        $this->sql = 'INSERT INTO '.$str.' '.$this->sql;
        return $this;
    }
    //UPDATE
    public function update($table){
        $this->sql = 'UPDATE '.$table.' ';
        return $this;
    }
    //SET
    public function set($str){
        if(is_array($str)){
            foreach($str as $key => $value){
                $c = $key.'='; 
                if(is_string($value)){
                    $c .= "'".$this->con->real_escape_string($value)."'";
                }else if(is_null($value)){
                    $c .= 'null';
                }else{
                    $c .= $this->con->real_escape_string($value);
                }
                $changes[] = $c;
            }
            $this->sql .= 'SET '.implode(",",$changes).' ';
        }else{
            $this->sql .= 'SET '.$str.' ';
        }
        return $this;
    }   
    //DELETE
    public function delete($table){
        $this->sql = 'DELETE FROM '.$table.' ';
        return $this;
    }
    public function go($ret_id = false){
        $result = $this->con->query($this->sql);
        if(!$this->error_check($result)){
            return false;
        }
        if($ret_id){
            return mysqli_insert_id($this->con);
        }
        return $result;
    }
    //GET SINGLE ROW
    public function row($type = 'array'){
        $result = $this->con->query($this->sql);
        $row = mysqli_fetch_assoc($result);
        if(!$this->error_check($result)){
            return false;
        }
        if(is_null($row)){
            return false;
        }
        if($type == 'object'){
            return (object)$row;
        }
        return $row;
    }
    //RETURN MULTIPLE RESULTS
    public function result($type = 'array'){
        $result = $this->con->query($this->sql);
        if(!$this->error_check($result)){
            return false;
        }
        $res = array();
        while($row = mysqli_fetch_assoc($result)){
            if($type == 'object'){
                $res[] = (object)$row;
            }else{
                $res[] = $row;
            }
        }
        return $res;
    }
    public function check(){
            echo '<pre>';
            echo '<h1>SQL CALL</h1>'.$this->sql;
            echo '</pre>';
    }

    public function escape($str){
        return $this->con->real_escape_string($str);
    }
    /***************
    ERROR HANDLEING
    ***************/
    public function error_check($res){
        if(!$res){
            echo '<pre>';
            echo '<h1>DATABASE ERROR</h1>'.$this->sql;
            echo '</pre>';
            return false;
        }
        return true;
    }
}