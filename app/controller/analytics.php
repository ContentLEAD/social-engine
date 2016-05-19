<?php

class analytics extends controller{
    
    public function global_activity($chart = 'line'){
        $res = $this->fez->se
            ->start_date(strtotime('midnight first day of this month'))
            ->end_date(time())
            ->date_group('d')
            ->sort_by(array('date'=>1))
            ->go();
        
        $chart = array();
        $data  = array();
        foreach($res as $k => $v){
            $chart['x_axis']['labels'][]= (string)$k;
            $data['data'][]= $v;
        }
        $chart['series'][]=$data;
        
        echo json_encode($chart);
    }
    
    public function time_of_day($chart = 'line'){
            $res = $this->fez->se
                ->start_date(strtotime('midnight first day of this month'))
                ->end_date(time())
                ->date_group('H')
                ->sort_by(array('date'=>1))
                ->go();

            $res = $this->fix_order($res);

            $chart = array();
            $data  = array();
            foreach($res as $k => $v){
                $chart['x_axis']['labels'][]= "'".$k."'";
                $data['data'][]= $v;
            }
            $chart['series'][]=$data;

            echo json_encode($chart);
        }

    //----------------------------------------------------------
    /*
    GECKO - METER FOR THE GECKOBOARD
    GENERATE MAX TIME AND COMPLETED TIME
    */
    
    public function status(){
          $res = $this->fez->se
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->math_by('period')
            ->go();
        
        $chart = array(
            'item'=> $res[date('M-y',time())]['item_time_percent'],
            'min'=>array('value'=>0),
            'max'=>array('value'=>100)
        );
        echo json_encode($chart);
    }

    
    public function overdue_clients(){
        $res = $this->fez->se
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->include_fields(array('sfid'))
            ->math_by('sfid')
            ->go();
        
        echo '<pre>';
        var_dump($res);
    }
    
    
    public function item_split(){
        $res = $this->fez->se
            ->status('LIVE')
            ->period(date('M-y',time()))
            ->include_fields(array('sfid'))
            ->math_by('sfid')
            ->go();
        
        $c = array('Very Overdue'=>0,'Overdue'=>0,'On Time'=>0);
        foreach($res as $k => $v){            
            if(($v['owed_to_date'] - $v['delivered']) > 75 ){
                $c['Very Overdue'] += 1;
            }else if($v['class'] == 'under'){
                $c['Overdue'] += 1;
            }else{
                $c['On Time'] += 1;
            }
        }
        $chart['item'] = array(
            array('value'=>$c['Very Overdue'],'text'=>'Very Overdue'),
            array('value'=>$c['Overdue'],'text'=>'Overdue'),
            array('value'=>$c['On Time'],'text'=>'On Time')
        );
        
        echo json_encode($chart);
    }
    
    
    
    public function fix_order($res){
        foreach($res as $k => $v){
            $temp[] = $k;
        }
        asort($temp);
        foreach($temp as $k => $v){
            $fixed[$v] = $res[$v];
        }
        return $fixed;

    }
}
