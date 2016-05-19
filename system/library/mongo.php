<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class mongo_db{
    
    private $m;
    private $db;
    private $data;
    private $collection;
    private $call;
    
    public function __construct($config){
        //CONNECT TO MONGO DB
        $this->m = new MongoClient($config['address']);
        $this->db = $this->m->selectDB($config['database']);
    }
        
    public function insert($data){
        $this->call = 'insert';
        $this->data = $data;
        return $this;
    }
    
    public function agg($query){
        $this->call = 'aggregate';
        $this->data = $query;
        return $this;
    }
    public function into($string){
        $this->collection = $string;
        return $this;
    }
    public function save($data){
        $this->data = $data;
        return $this;
    }
    public function to($c){
        $c = $this->db->selectCollection($c);
        $x = $c->save($this->data);
    }
    public function set($data){
        $this->call = 'update';
        $this->protocall = '$set';
        $this->data = $data;
        return $this;
    }
    public function options($data){
        $this->options = $data;
        return $this;
    }
    public function addtoset($data){
        $this->call = 'update';
        $this->protocall = '$addtoset';
        $this->data = $data;
        return $this;
    }
    public function push($data){
        $this->call = 'update';
        $this->protocall = '$push';
        $this->data = $data;
        return $this;
    }
    public function inc($data){
        $this->call = 'update';
        $this->protocall = '$inc';
        $this->data = $data;
        return $this;
    }
    public function remove($data){
        $this->call = 'update';
        $this->protocall = '$unset';
        $this->data = $data;
        return $this;
    }
    public function in($string){
        $this->collection = $string;
        return $this;
    }
    public function where($array){
        $this->where = $array;
        return $this;
    }
    public function findOne($fields = false){
        $this->call = 'findOne';
        if($fields){
            $this->fields = $fields;
        }
        return $this;
    }
    public function find($fields = false){
        $this->call = 'find';
        if($fields){
            $this->fields = $fields;
        }
        return $this;
    }
    public function map($s){
        $this->call= 'map';
        $this->map = $s;
        return $this;
    }
    public function reduce($s){
        $this->reduce = $s;
        return $this;
    }
    public function query($q){
        $this->query = $q;
        return $this;
    }
    public function out($o){
        $this->out = $o;
        return $this;
    }
    public function go(){
        switch($this->call){
            case 'insert':
                //set collections
                $c = $this->db->selectCollection($this->collection);
                $c->insert($this->data);
            break;
            case 'update':
                $c = $this->db->selectCollection($this->collection);
                if($this->options){
                    return $c->update($this->where,array($this->protocall=>$this->data),$this->options);
                }
                return $c->update($this->where,array($this->protocall=>$this->data));
            break;
            case 'aggregate':
                $c = $this->db->selectCollection($this->collection);
                return $c->aggregate($this->data);
            break;
            case 'findOne':
                $c = $this->db->selectCollection($this->collection);
                if(isset($this->fields)){
                    return $c->findOne($this->where,$this->fields);
                }
                return $c->findOne($this->where);
            break;
            case 'find':
                $c = $this->db->selectCollection($this->collection);
                
                if(isset($this->fields)){
                    $x = $c->find($this->where,$this->fields);
                    return iterator_to_array($x);
                }
                $x =  $c->find($this->where);
                return iterator_to_array($x);
            break;
            case 'map':
                $c = $this->db->selectCollection($this->collection);
                $com = array(
                    'map'=> $this->map,
                    'reduce'=>$this->reduce,
                    'out'=>$this->out
                );
                if(isset($this->query)){
                    $com['query']= $this->query;
                }
                return $c->command($com);
            break;
            default:
                
            break;
        
        }
    
    }
}