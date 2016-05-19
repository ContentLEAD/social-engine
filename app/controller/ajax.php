<?php

class ajax extends controller{
    public $u;
    public $ret = array(
        'success'=>true,
        'response'=>null
    );
    
    //---------
    //CHECK TO MAKE SURE THE CALL IS AN AJAX CALL
    //-----------
    
    public function is_ajax(){
        //ARE YOU WHO YOU SAY YOU ARE
        //$this->u = $this->fez->session->get_data();
        //CHECK IF 
        if(is_null($_SERVER['HTTP_X_REQUESTED_WITH'])){
            $this->ret_val(false,'Not Ajax');
            $this->end_call();
        }
    }
    
    //---------
    //LOG AN ACTIVITY
    //-----------
    public function commit_activity_2(){
        $u = $this->fez->session->get_data();
        //CHECK IF AJAX        
        $this->is_ajax();
        //SANITIZE POST DATA
        if(!isset($_POST['item_id'])){
            $this->ret_val(false,'No ID Found');
            $this->end_call();
        }
        //MAKE COM
        if($_POST['dir'] == 'plus'){
            se::add_commit($_POST['item_id'],$u['id']->val);
        }else{
            se::sub_commit($_POST['item_id']);
        }
        //GET THE POST ID
        $this->end_call();
    }
    
    //-------------------
    //BACKLOG HELP
    //-------------------
    public function backlog_build(){
        $items = json_decode($_POST['data']);

        //BUILD ITEMS
        foreach($items as $item){
            
            $c = new catalog;
            $c->load((int)$item->item);
            //PACKAGE FOR RECORD;
            $package = array(
                'item_id' => uniqid(),
                'created_date' => time(),
                'period'=>array(
                    array('name'=> date('M-y',time()),'owed'=>(int)$item->owed)
                ),
                'status'=>'LIVE',
                'type'=>'BACKLOG',
                'network'=>strtoupper($c->get('network')),
                'action'=>strtoupper($c->get('action')),
                'start_amount'=>(int)$item->owed,
                'live_date'=>time()
            );
            //PUSH INTO ITEMS ARRAY
//            $res = $this->fez->mongo->push(array('items'=>$package))
//                ->in('records')
//                ->where(array('sfid'=>$_POST['sfid']))
//                ->go();
            
            //CREATE FOR ITEMS DB
            $user = $this->fez->session->get_data();
            $i = array();
            $i['sfid'] = $_POST['sfid'];
            $i['user_id'] = $user['id']->val;
            $i['item_id'] = $package['item_id'];
            $i['type'] =    'BACKLOG';
            $i['network'] = $package['network'];
            $i['action'] = $package['action'];
            $i['period'] =date('M-y',time());
            $i['start_amount'] = (int)$package['start_amount'];
            $i['status'] = 'LIVE';
            $i['current_owed'] = (int)$package['start_amount'];
            
            $res = $this->fez->mongo->insert($i)
                ->into('items')
                ->go();
            //INSERT INTO NEW SYSTEM
//            
//            if(!$res['updatedExisting']){
//                $this->end_call();
//            }
        }
        //ITEMS WENT THROUGH NOW REMOVE THE TIME FROM THE BACKLOG
        $res = $this->fez->mongo->set(array('unassigned_backlog'=>(int)$_POST['bl']))
            ->in('records')
            ->where(array('sfid'=>$_POST['sfid']))
            ->go();

        if($res['updatedExisting']){
            $this->end_call();
        }
    }
    
    //---------
    //CHANGE AMOUNT OF AN ITEM
    //-----------
    
    public function change_amount(){
        $this->is_ajax();
        //CHANGE IN RECORDS DB
        $this->fez->mongo->set(array('items.$.start_amount'=>(int)$_POST['start_amount']))
            ->in('records')
            ->where(array('items.item_id'=>$_POST['item_id']))
            ->go();
        //UPDATE PERIOD DATA
        $p_data = $this->build_period_data($_POST['item_id'],(int)$_POST['start_amount']);
        //PUSH TO PERIOD DATA
        $res = $this->fez->mongo->set(array('items.$.period'=>$p_data))
            ->into('records')
            ->where(array('items.item_id'=>$_POST['item_id']))
            ->go();
        //UPDATE ITEMS DB
        $this->fez->mongo->set(array('start_amount'=>(int)$_POST['start_amount'],'current_owed'=>(int)$_POST['start_amount']))
            ->in('items')
            ->where(array('item_id'=>$_POST['item_id'],'period'=>date('M-y',time())))
            ->go();
        //UPDATE COMMITS DB
        $this->fez->mongo->set(array('owed'=>(int)$_POST['start_amount']))
            ->in('commits')
            ->options(array('multiple'=>true))
            ->where(array('item_id'=>$_POST['item_id'],'period'=>date('M-y',time())))
            ->go();

        $this->ret =  $res;       
        $this->end_call();
    }
    
    //-----------------
    //GET ITEM INFORMATION
    //------------------
    public function item_info(){
        //CHECK IF AJAX        
        $this->is_ajax();
        $info = $this->fez->se
            ->item($_POST['item_id'])
            ->math_by('period')
            ->include_fields(array('item_id','network','action','sfid'))
            ->go();
        $html = $this->build_info_html($info);
        //END CALL
        echo $html;
    }
    
    //---------
    //MAKE AN ITEM LIVE
    //-----------
    public function make_live(){
        //CHECK FOR AJAX
        $this->is_ajax();
        //CHECK IF ITEM IS THERE
        if(!isset($_POST['item_id'])){
            $this->ret_val(false,'Not Item Id Found');
            $this->end_call();
        }
        //GRAB THE ITEM
        $item_id = $_POST['item_id'];
        //GET CUR PERIOD
        $cur_period = date('M-y',time());
        //GET START AMOUNT
        $owed = (int)$_POST['start_amount'];
        //GET DAYS LEFT IN MONTH
        $dl = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),date('d',time()));
        $wm = $this->countDays(date('Y',time()),date('m',time()),array(0, 6),1);
        //PRORATE AMOUNT
        $dis = $owed/$wm;
        $pro = $dis*$dl;
        $pro = ceil($pro);
        //CREATE PUSH DATA FOR PERIOD
        if(isset($_POST['fixed'])){
            $pro = (int)$_POST['start_amount'];
        }
        //UPDATE PERIOD DATA IN THE RECODS DB
        $period_data = array(
            'name'=>$cur_period,
            'owed'=>$pro
        );
        //PUSH PERIOD DATA QUERY
        $res = $this->fez->mongo->push(array('items.$.period'=>$period_data))
            ->where(array('items.item_id'=>$item_id))
            ->in('records')
            ->go();
        
        //SET STATUS TO LIVE IN RECORDS DB
        $res = $this->fez->mongo->set(array('items.$.status'=>'LIVE','items.$.live_date'=>time()))
            ->where(array('items.item_id'=>$item_id))
            ->in('records')
            ->go();
        
        //UPDATE IN ITEMS DB
        $this->fez->mongo->set(array('status'=>'LIVE','current_owed'=>$pro,'period'=>$cur_period))
            ->in('items')
            ->where(array('item_id'=>$item_id))
            ->go();
        
        //END THE CALL
        $this->end_call();
    }
    
    //---------
    //SHUT DOWN THE CLIENT
    //-----------
    public function shut_down_client($sfid){
       $res = $this->fez->mongo->set(array('client_status'=>false))
            ->in('records')
            ->where(array('sfid'=>$sfid))
            ->go();
        
        $items = $this->fez->mongo->find(array('items'=>1))
            ->in('records')
            ->where(array('sfid'=>$sfid))
            ->go();
        
        $items = current($items);
        foreach($items['items'] as $key){
            $this->close_item($key['item_id']);
        }
    }
    
    //---------
    //BUILD AN ITEM FORM
    //-----------

    public function item_form($network){
        //CHECK FOR AJAX
        $this->is_ajax();
        $network = urldecode($network);
        //GET ACTIONS INFORMATION FROM THE DATABASE
        $actions = $this->fez->db->select('action')
            ->from('catalog')
            ->where('status=1 AND network="'.$network.'"')
            ->result('object');
        //BUILD THE RESPONSE
        foreach($actions as $key){
            echo '<option>'.$key->action.'</option>';
        }
        //END THE CALL
        die();
        //$this->end_call();
    }
    
    //---------
    //CREATE THE ITEM
    //-----------
    
    public function create_item(){
        //CHECK AJAX
        $this->is_ajax();
        //GET USER ID
        $user = $this->fez->session->get_data();
        //VALIDATE
        if(!(int)$_POST['start_amount'] > 0){
            $this->ret_val(false,'Needs a start amount');
            $this->end_call();
        }
        //TRANSFER POST
        $post = $_POST;
        
        //CREATE THE ITEM IN MONGO
        $id = $post['sfid'];
        unset($post['sfid']);

        //CHECK TO SEE IF CLIENT EXISTS
        $client = $this->fez->mongo->find()
            ->in('records')
            ->where(array('sfid'=>$id))
            ->go();
        
        if(empty($client)){
            //MAKE CLIENT RECORD
            //GRAB SALESFORCE NAME
            $res = $this->fez->sforce->brafton->query("Select Name FROM Account WHERE Id='".$id."'");
            $res = $res->records;
            //CREATE THE CLIENT IN THE SYSTEM
            $client=array(
                'client_name'=> $res[0]->fields->Name,
                'sfid'=>$id,
                'client_status'=>true,
                'start_date'=>time(),
                'user_id' =>$user['id']->val,
                'prev_users'=>array(),
                'items'=>array(),
                'unassigned_backlog'=>0
            );            
            
            $res = $this->fez->mongo->insert($client)
                ->into('records')
                ->go();
            
            if(!$res['updatedExisting'] && $res != NULL){
                $this->ret_val(false, 'Mongo Error');
                $this->end_call();
            }
        }
        //ENTER THE ITEM
        $item = $post;
        $item['item_id'] = uniqid();
        $item['created_date'] = time();
        $item['period'] = array();
        $item['status'] = 'NYL';
        //push to records db
        $res = $this->fez->mongo->push(array('items'=>$item))
            ->in('records')
            ->where(array('sfid'=>$id))
            ->go();
        
        //GENERATE ITEM for item db
        $i['sfid'] = $id;
        $i['user_id'] = $user['id']->val;
        $i['item_id'] = $item['item_id'];
        $i['type'] =    $post['type'];
        $i['network'] = $post['network'];
        $i['action'] = $post['action'];
        $i['period'] = '';
        $i['start_amount'] = (int)$post['start_amount'];
        $i['status'] = 'NYL';
        $i['current_owed'] = 0;
        //INSERT INTO NEW SYSTEM
        $this->fez->mongo->insert($i)
            ->into('items')
            ->go();
        
        if(!$res['updatedExisting']){
            $this->ret_val(false, 'Mongo Error');
            $this->end_call();
        }
        //END THE CALL
        $this->ret_val(true);
        $this->end_call();
    }
    
    //---------
    //CREATE A TASK
    //-----------
    
    public function create_task(){
        
        $this->is_ajax();
        
        $u = $this->fez->session->get_data();

        $res = $this->fez->sforce->brafton->query("SELECT Name,Social_Media_Exec__c, Social_Media_Exec__r.UserName FROM Account WHERE Id ='".$_GET['client_id']."'");
        $res = $res->records;
        
        $r = $this->fez->db->select('id')
            ->from('user')
            ->where('sfid="'.$res[0]->fields->Social_Media_Exec__c.'"')
            ->row();
        
        $data = array(
            'user_id'=> $u['id']->val,
            'client_name'=>$res[0]->fields->Name,
            'client_id'=>$_GET['client_id'],
            'user_name'=> $u['first_name']->val.' '.$u['last_name']->val,
            'assigned_name'=>$res[0]->fields->Social_Media_Exec__r->fields->Username,
            'assigned_id'=>$r['id']
        );

        echo $this->build_task_html($data);
        //$this->end_call();
    }
    
    //---------
    //SAVE TASK
    //-----------
    public function save_task(){
        //CHECK AJAX 
        $this->is_ajax();
        //GET USER DATA
        $u = $this->fez->session->get_data();
        //INSTANTIATE NOTE
        $t = new task;
        //SET THE STATUS
        $t->set('status',1);
        $t->set('client',$_POST['client']);
        $t->set('client_name',$_POST['client_name']);
        $t->set('subject',addslashes( $_POST['subject'] ) );
        $t->set('created_by',$u['id']->val);   
        $t->set('created_date',time());   
        if($_POST['due_date'] != ''){
            $t->set('due_date',strtotime($_POST['due_date']));   
        }else{
            $t->set('due_date',null);   
        }
        if($_POST['owner'] != 'null'){
            $t->set('owner',(int)$_POST['owner']);
        }else{
            $t->set('owner',(int)$u['id']->val);
        }
        $t->set('note',addslashes( $_POST['note'] ) );
        $t->save();
        //END THE CALL
        $this->ret_val(true,array('note'=>$t->fields['note']['val'], 'id'=>$t->fields['id']['val']));
        $this->end_call();
    }
    
    //---------
    //CREATE THE ITEM
    //-----------
    public function return_field(){
        $ret = $this->fez->db->select($_GET['field'])
            ->from($_GET['table'])
            ->where('id='.$_GET['id'])
            ->row();
        echo $ret[$_GET['field']];
    }
    
    //---------
    //CLOST AN ITEM
    //-----------
    
    public function close_item($item_id){
        $this->is_ajax();
        
        if(!$item_id){
            $this->ret = 'No id';
            $this->end_call();
            return;
        }
        
        $res = $this->fez->mongo->set(array('items.$.status'=>'CLOSED','items.$.end_date'=>time()))
            ->where(array('items.item_id'=>$item_id))
            ->in('records')
            ->go();
        
        //NEW PROCESS
        $this->fez->mongo->set(array('status'=>'CLOSED','end_date'=>time()))
            ->in('items')
            ->options(array('multiple'=>true))
            ->where(array('item_id'=>$item_id))
            ->go();
        
        $this->fez->mongo->set(array('status'=>'CLOSED'))
            ->in('commits')
            ->options(array('multiple'=>true))
            ->where(array('item_id'=>$item_id))
            ->go();
        
        //END THE CALL
        $this->end_call();
    }
    
    //-------------------
    //close a task
    //-------------------
     public function close_task($id){
        $this->is_ajax();
        $t = new task;
        $t->load($id);
        $t->set('status',0);
        $t->set('closed_date',time());
        $t->save();
    }
    
    //-------------------
    //save info for a client
    //-------------------
    public function save_info(){
        $this->is_ajax();
        $n = new info;
        $n->set('info',addslashes($_POST['info']));
        $n->set('client',$_POST['client_id']);
        $n->save();
        $this->end_call();
    }
    

    //-------------------
    //PRIVATE HELPER FUNCTIONS
    //-------------------

    private function ret_val($abool, $aresponse=''){
        $this->ret['success'] = $abool;
        $this->ret['response'] = $aresponse;
    }
    
    private function end_call(){
        echo json_encode($this->ret);
        die();
    }
    //COUNT DAYS FOR 
    private function countDays($year, $month, $ignore, $start) {
        $count = 0;
        $counter = mktime(0, 0, 0, $month, $start, $year);
        while (date("n", $counter) == $month) {
            if (in_array(date("w", $counter), $ignore) == false) {
                $count++;
            }
            $counter = strtotime("+1 day", $counter);
        }
        return $count;
    }
    
    private function build_period_data($item,$new_amount){
        
        //GET THE PERIOD DATA
       $data =  $this->fez->mongo->find(array('items.$.period'=>true))
            ->where(array('items.item_id'=>$item))
            ->in('records')
            ->go();
        //CORRECT DUMB MONGO DATA
        $data = current($data);
        $data = current($data['items']);
        $data = $data['period'];
        
        //IS THERE BACKLOG?
        //ADD THE UNFINISHED ITEMS TO LAST PERIOD
        foreach($data as $k){
            $h = $k;
            //CHECK FOR PERIODS ALREADY ENTERED
            if($k['name'] == date('M-y',time())){
                $h['owed'] = (int)$new_amount;
            }
            $new_period_data[] = $h;
        }
        return $new_period_data;
    }
    
    public function get_info($client_id){
        //CHECK IF AJAX;
        $this->is_ajax();
        
        $res = $this->fez->db->select('info')
            ->from('info')
            ->where('client="'.$client_id.'"')
            ->row();
        $ci = "'".$client_id."'";
        echo '<strong>Double click to edit information.</strong>';
        if($res){
            echo '<div class="col-md-12 info-display" style="min-height:200px">'.stripcslashes($res['info']).'</div>';
            echo '<div class="col-md-12 info-edit" style="display:none" ><textarea id="edit-info-textarea" class="form-control">'.stripcslashes($res['info']).'</textarea>';
            echo '<br /><button class="btn btn-success" onclick="save_info('.$ci.')">Save It!</button></div>';
        }else{
            echo '<div class="col-md-12 info-display">No Info</div>';
            echo '<div class="col-md-12 info-edit" style="display:none"><textarea id="edit-info-textarea" class="form-control"></textarea>';
            echo '<br /><button class="btn btn-success" onclick="save_info('.$ci.')">Save It!</div>';
        }
    }

    //-------------------------------------------------------------------------------------
    //------------------html builder for info -----------------------------
    //-------------------------------------------------------------------------------------
    
    private function build_task_html($data){
        $html = '<div class="col-md-12" >
            <div class="col-md-6">
                <label>Created By:&nbsp;</label>'.$data['user_name'].'
            </div>
            <div class="col-md-6">
                <label>Due Date:&nbsp;</label><input id="datepicker" type="date" name="due_date" class="form-control">
            </div>
            <div class="col-md-6">
                <label>Assign to Client</label>
                '.$data['client_name'].'
                <input type="hidden" name="client" value="'.$data['client_id'].'" class="form-control" />
                <input type="hidden" name="client_name" value="'.$data['client_name'].'" class="form-control" />
            </div>
            <div class="col-md-6">
                <label>Assign to Owner</label>
                '.$data['assigned_name'].'
                <input type="hidden" name="owner" value="'.$data['assigned_id'].'" class="form-control" />
            </div>
            <div class="col-md-12" >
                <label>Subject: </label>
                <input type="text" name="subject" class="form-control" />
                <label>Note: </label>
                <textarea name="note" class="form-control" ></textarea>
                <button class="btn btn-success" onclick="save_task('."'".$data['client_id']."'".')">Save Task</button>
            </div>
        </div>';
    
    
        return $html;
    
    }
    
    private function build_info_html($data){
        $cur = $data[date('M-y',time())];
        $html = '<div class="col-xs-4">
            <div class="row widget">
                <div id="network-info" class="col-md-12"><h4 style="line-height:1px;">'.$cur['network'].'</h4></div>    
                <div id="action-info" class="col-md-12">'.$cur['action'].'</div>    
                <div id="action-info" class="col-md-12"><small>'.$cur['type'].'</small></div>    
            </div>
            <div class="row widget">';
            if($cur['type'] != 'BACKLOG'){
               $html .=' <div class="col-xs-12 sel" onclick="change_amount('."'".$cur['item_id']."'".')">Adjust Amout</div><hr/>';
            }
            $html .='    <div class="col-xs-12 sel" onclick="close_item('."'".$cur['item_id']."'".')">Close Item</div>
            </div>
            <div class="row widget">  
                <div class="col-md-12">CURRENT AMOUNT</div>
                <div id="current_amount" class="col-md-6 col-md-offset-6 text-right">'.$cur['total_owed'].'</div>    
            </div>';
        $html .='        
        </div>
        <div class="col-xs-8">
            <div class="row widget">
                <table class="table table-condensed" style="margin-bottom:0px;font-size:13px;">
                    <tr>
                        <td>Total Delivered</td>
                        <td>Owed To Date</td>
                        <td>Total Owed</td>
                    </tr>
                    <tr>
                        <td>'.$cur['delivered'].'</td>
                        <td>'.round($cur['owed_to_date']).'</td>
                        <td>'.$cur['total_owed'].'</td>
                    </tr>
                </table>
            </div>
            <div class="row widget" id="info-chart" style="height:200px;padding:0px;"></div>
            
        </div>';
        foreach($data as $key => $val){
            $x['cats'][] = $key;
            $x['del'][] = $val['delivered'];
            $x['owed'][] = $val['total_owed']-$val['delivered'];
        }
        $html .= '<script>
                var cats = '.json_encode($x['cats']).';
                var del = '.json_encode($x['del']).';
                var undel = '.json_encode($x['owed']).';
            </script>
        ';

        return $html;
    }
    
    private function build_info_html_sego($data){
        $cur = $data[date('M-y',time())];
        $html = '<div class="col-xs-4">
            <div class="row widget">
                <div id="network-info" class="col-md-12"><h4 style="line-height:1px;">'.$cur['network'].'</h4></div>    
                <div id="action-info" class="col-md-12">'.$cur['action'].'</div>    
                <div id="action-info" class="col-md-12"><small>'.$cur['type'].'</small></div>    
            </div>
            <div class="row widget">';
            if($cur['type'] != 'BACKLOG'){
               $html .=' <div class="col-xs-12 sel" onclick="change_amount('."'".$cur['item_id']."'".')">Adjust Amout</div><hr/>';
            }
            $html .='    <div class="col-xs-12 sel" onclick="close_item('."'".$cur['item_id']."'".')">Close Item</div>
            </div>
            <div class="row widget">  
                <div class="col-md-12">CURRENT AMOUNT</div>
                <div id="current_amount" class="col-md-6 col-md-offset-6 text-right">'.$cur['total_owed'].'</div>    
            </div>';
            if($cur['network'] == 'TWITTER'){
               $html .=' 
            <div class="row widget">
               <div class="col-md-12 sel" onclick="sego('."'TWITTER'".','."'".$cur['sfid']."'".')">
               TWEET
               </div>
            </div>';
            }
        $html .='        
        </div>
        <div class="col-xs-8">
            <div class="row widget">
                <table class="table table-condensed" style="margin-bottom:0px;font-size:13px;">
                    <tr>
                        <td>Total Delivered</td>
                        <td>Owed To Date</td>
                        <td>Total Owed</td>
                    </tr>
                    <tr>
                        <td>'.$cur['delivered'].'</td>
                        <td>'.round($cur['owed_to_date']).'</td>
                        <td>'.$cur['total_owed'].'</td>
                    </tr>
                </table>
            </div>
            <div class="row widget" id="info-message" style="height:200px;display:none;">
                
            </div>
            <div class="row widget" id="info-chart" style="height:200px;padding:0px;"></div>
            
        </div>';
        foreach($data as $key => $val){
            $x['cats'][] = $key;
            $x['del'][] = $val['delivered'];
            $x['owed'][] = $val['total_owed']-$val['delivered'];
        }
        $html .= '<script>
                var cats = '.json_encode($x['cats']).';
                var del = '.json_encode($x['del']).';
                var undel = '.json_encode($x['owed']).';
            </script>
        ';

        return $html;
    }
    
    
    
    
    
    
    private function get_data($item_id){
        
        
        $agr = array(
//                array('$match'=>array('items.item_id'=>$item_id)),
//                array('$project'=>array('items'=>true)),
                array('$unwind'=>'$items'),
                array('$match'=>array('items.item_id'=>$item_id)),
                array('$unwind'=>'$items.period'),
//                array('$match'=>array('items.period.name'=>$cur_period)),
                array('$unwind'=>'$items.activity'),
//                array('$match'=>array('$or'=>array(
//                    array('items.activity.date'=>array('$gt'=>$start_time)),
//                    array('items.activity.date'=>0)
//                ))),
                //IF THIS SPITS OUT AN ARRAY COUNT OF 0 PIPE ENDS
                array('$group'=>array(
                    '_id'=>array('item_id'=>'$items.item_id'),
                    'act'=>array('$addToSet'=>'$items.activity.date'),
                    'per'=>array('$addToSet'=>array('name'=>'$items.period.name','owed'=>'$items.period.owed','backlog'=>'$items.period.backlog'))
                    )
                ),
            ); 
        
        
        $data = $this->fez->mongo->agg($agr)
        ->in('records')
        ->go();
        
        $month = array();
        if(isset($data['result'][0])){
            
            asort($data['result'][0]['act']); 
            foreach($data['result'][0]['act'] as $i => $v){
                if($v){
                    $month[date('M',$v)]['del']++;
                }
            }
            foreach($data['result'][0]['per'] as $i => $v){
                $month[date('M',strtotime($v['name']))]['owed'] = (int)$v['owed'];
                if($v['backlog']){
                $month[date('M',strtotime($v['name']))]['backlog'] = (int)$v['backlog'];
                }
            }
            
        }
        
        return $month;
    }
    
}