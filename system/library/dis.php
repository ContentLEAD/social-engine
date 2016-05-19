<?php 
class dis {
    
    public function orient($dis){
        $this->dis = $dis;
        return $this;
    }
    
    public function amount($num){
        $this->amount = $num;
        return $this;
    }
    
    public function month($month){
        $this->month = $month;
        return $this;
    }
    
    public function go(){
        //CHART THE MONTH
        $this->chart();
        //CALC THE AMOUNT
        $result = $this->calc();
        $this->clear();
        return $result;
    }
    
    
    private function calc(){
        if($this->dis == 'MONTH'){
            return $this->amount;
        }
        if($this->dis == 'WEEK'){
            return $this->amount * $this->weeks;
        }
        if($this->dis == 'DAY'){
            return $this->amount * $this->days;
        }
    }
    
    private function chart(){
        
        if(!$this->month){
            $month = date('m',time()).'/1/'.date('Y',time());
        }else{
            $month = date('m',strtotime($this->month)).'/1/'.date('Y',strtotime($this->month));
        }
        $start = strtotime('Midnight '.$month);
        $start_day = date('w',$start);
        $end = strtotime('11:59pm last day of '.date('M Y',strtotime($month)));
        $end_day = date('w',$end);
        
        $day_inc = $start_day;
        
        for($i = 1; $i <= date('d',$end); $i++){
            if($day_inc != 0 && $day_inc != 6){
                //CALC DAYS
                $this->days += 1;
                //CALC WEEKS
                $this->weeks = $this->days/5;
            }
            //DAY INC
            $day_inc++;
            if($day_inc == 7){
                $day_inc = 0;
            }
        }
    }
    
    private function clear(){
        unset($this->dis,$this->amount,$this->month);
    }

}