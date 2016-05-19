<?php
//    echo '<pre>';
//    var_dump($client);

foreach($client as $key => $value){
    if(is_array($value['items'])){
        foreach($value['items'] as $net => $info){
            foreach($info as $num => $item){
                $x = $item;
                $x->client_name = $value['name'];
                $network[$net][$item->action][$key][$item->item_id] = $x;
            }
        }
    }
}

function dump($ob){
    echo '<pre>';
    var_dump($ob);
    echo '</pre>';
}
//    echo '<pre>';
//  var_dump($network);

?>
<div class="container">
    <div class="col-md-4 col-xs-4" id="left-bar">
        
        <div class="row widget">
            <div class="row sel toggler" id="task-toggle">
                <div class="col-md-8">
                    <h4><i class="fa fa-envelope"></i>&nbsp;Tasks</h4>
                </div>
                <div class="col-md-4 text-right"><span class="badge"><?= count($tasks)?></span></div>
            </div>
            <div class="row toggle" data-toggle="task-toggle">
                <div class="col-md-12 col-xs-12">
                    <?php if(!empty($tasks)) :?>
                    <?php foreach($tasks as $t => $v):?>
                        <div class="task row" id="task_<?= $v['id'] ?>" data-task='<?= json_encode($v) ; ?>' data-due='<?= date('m-d-y',$v['due_date'])  ?>' data-created= '<?= date('m-d-y',$v['created_date'])  ?>'>
                            <div class="col-xs-8">
                            <?= substr($v['subject'],0,30);  ?>
                            </div>
                            <div class="col-xs-4">
                            <?= date('m-d-y',$v['due_date'])  ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row sel toggler" id="overdue-toggle">
                <div class="col-md-8">
                    <h4><i class="fa fa-exclamation"></i>&nbsp;Overdue Items</h4>
                </div>
                <div class="col-md-4 text-right"><span class="badge"><?= count($overdue)?></span></div>
            </div>
            <div class="row toggle" data-toggle="overdue-toggle">
                <div clas="col-md-12 col-xs-12" >
                     <?php foreach($overdue as $t => $v):?>
                        <div class="row overdue" data-overdue="<?=$v['id']?>" style="margin-left:0px;margin-right:0px;">
                            <div class="col-md-12"><?=$v['name']?></div>
                            <div class="col-md-12"><?=$v['network']?>&nbsp;<?=$v['action']?></div>
                        </div>
                     <?php endforeach;?>
                </div>
            </div>
            <div class="row sel toggler" id="view-toggle">
                <div class="col-md-8">
                    <h4><i class="fa fa-desktop"></i>&nbsp;Views</h4>
                </div>            
            </div>
            <div class="row toggle" data-toggle="view-toggle">
                <div clas="col-md-12 col-xs-12" >
                     <a href="/index.php/board/network_view" style="display:block;padding:10px;">Network</a>
                     <a href="/index.php/board/index/" style="display:block;padding:10px;">Clients</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8 col-xs-8">
        <div class="row widget task-reader">
            <div class="col-md-12 text-right close-task" style="margin-bottom:10px;color:red;">
                <span class="badge"><i class="fa fa-times"></i>&nbsp;Back</span>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-12">
                        <h3 id="task-subject"></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" id="task-note">
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <strong>Due Date</strong><br />
               <span id="task-due-date"></span><br />
                 <strong>Client Name</strong><br />
                <span id="task-client-name"></span><br />
                 <strong>Created By</strong><br />
                <span id="task-created-by"></span><br />
                 <strong>Created Date</strong><br />
                <span id="task-created-date"></span>
            </div>
            <div class="col-md-6" >
                <button class="btn btn-xs btn-primary task-closer" data-task-id=""><i class="fa fa-check"></i>&nbsp;Complete Task</button>
            </div>
        </div>
        <div class="row widget client-list">
        <?php //ONE ?>    
        <?php foreach($network as $net_name => $v) :?>
            <div class="row new-client-full-div" data-balance="<?= $v['balance'];?>" style="margin-left:0px;margin-right:0px;">
                <!--client block-->
                <div data-id="<?= $net_name;?>" class="new-client col-md-12">
                    <div class="col-md-7 col-xs-7">
                        <?= $net_name;?>
                    </div>
                    <div class="col-md-4 col-xs-4 text-right">

                    </div>
                    <div class="col-md-1 col-xs-1 text-right" data-balance-counter="<?= $key;?>">

                    </div>
                </div>
                <!--client order-->
                <div class="new-client-order" data-order="<?= $net_name;?>">
                    <!--OPTIONS-->
                    <div class="row options" >
                        <div class="col-md-6 col-xs-6">
                        </div>
                    </div>
                    <?php foreach($v as $action => $v2) :?>
                    <div class="row" style="margin-left:0px;margin-right:0px;">
                        <div class="col-md-8" style="border-bottom:1px solid lightgrey;margin-top:15px;"><?=$action;?></div>
                        <?php foreach($v2 as $client_id => $item):?>
                            <?php foreach($item as $i) :?>
                                <div class="col-md-6 col-xs-6" style="min-height:20px;">
                                    <?php if( $i->type == 'BACKLOG' ):?>
                                    <div class="new-item row backlog" id="<?=$i->item_id;?>" data-client-id="<?= $net_name;?>" data-per-int="<?= $i->numbers->percentage_inc?>" data-owed-to-date="<?=round($i->numbers->owed_to_date)?>" data-delivered="<?=$i->numbers->delivered;?>">
                                        <div class="col-xs-1 <?=$i->classes?>  new-item-network <?=$i->item_id;?>"><i class="fa fa-<?=strtolower($net_name);?>"></i></div>
                                        <div class="col-xs-4 <?=$i->classes?> new-item-action <?=$i->item_id;?>" ><?= substr($i->client_name,0,10).'...' ?></div>
                                        <?php if($i->classes != ' nyl '):?>
                                        <div class="col-xs-1 <?=$i->classes?> new-item-button text-center notifier"><i class="fa fa-exclamation-triangle "></i></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-remove <?=$i->item_id;?>"><i class="fa fa-times"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-commit <?=$i->item_id;?>"><i class="fa fa-check"></i></div>
                                        <?php else :?>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center "><i class=" fa fa-play" style="color:rgb(27,192,255);"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>" data-start-amount="<?=$i->numbers->start_amount?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-nyl"><i class="fa fa-play"></i></div>
                                        <?php endif;?>
                                    <?php elseif( $i->type == 'FIXED' ) :?>
                                    <div class="new-item row fixed" id="<?=$i->item_id;?>" data-client-id="<?= $net_name;?>" data-per-int="<?= $i->numbers->percentage_inc?>" data-owed-to-date="<?=round($i->numbers->owed_to_date)?>" data-delivered="<?=$i->numbers->delivered;?>">
                                        <div class="col-xs-1 <?=$i->classes?>  new-item-network <?=$i->item_id;?>"><i class="fa fa-<?=strtolower($net_name)?>"></i></div>
                                        <div class="col-xs-4 <?=$i->classes?> new-item-action <?=$i->item_id;?>" ><?= substr($i->client_name,0,10).'...' ?></div>
                                        <?php if($i->classes != ' nyl '):?>
                                        <div class="col-xs-1 <?=$i->classes?> new-item-button text-center notifier"><i class="fa fa-thumb-tack "></i></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-remove <?=$i->item_id;?>"><i class="fa fa-times"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-commit <?=$i->item_id;?>"><i class="fa fa-check"></i></div>
                                        <?php else :?>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center "><i class=" fa fa-play" style="color:rgb(27,192,255);"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>" data-start-amount="<?=$i->numbers->start_amount?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-nyl"><i class="fa fa-play"></i></div>
                                        <?php endif;?>   
                                    <?php else:?>    
                                    <div class="new-item row" id="<?=$i->item_id;?>" data-client-id="<?= $net_name;?>" data-per-int="<?= $i->numbers->percentage_inc?>" data-owed-to-date="<?=round($i->numbers->owed_to_date)?>" data-delivered="<?=$i->numbers->delivered;?>">
                                        <div class="col-xs-1 <?=$i->classes?>  new-item-network <?=$i->item_id;?>"><i class="fa fa-<?=strtolower($net_name)?>"></i></div>
                                        <div class="col-xs-5 <?=$i->classes?> new-item-action <?=$i->item_id;?>" ><?= substr($i->client_name,0,10).'...' ?></div>
                                        <?php if($i->classes != ' nyl '):?>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-remove <?=$i->item_id;?>"><i class="fa fa-times"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-commit <?=$i->item_id;?>"><i class="fa fa-check"></i></div>
                                        <?php else :?>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center "><i class=" fa fa-play" style="color:rgb(27,192,255);"></i></div>
                                        <div class="col-xs-2 new-item-counter" data-item-count="<?= $i->item_id ?>" data-start-amount="<?=$i->numbers->start_amount?>"><?= $i->numbers->owed; ?></div>
                                        <div class="col-xs-2 <?=$i->classes?> new-item-button text-center new-nyl"><i class="fa fa-play"></i></div>
                                        <?php endif;?>
                                    <?php endif;?>
                                    </div>
                                </div>
                            <?php endforeach;?>
                        <?php endforeach;?>

                    </div>
                    <?php endforeach ;?>
                </div>
                <div class="new-item-info" data-item-info="<?= $net_name;?>">
                    <div class="row" style="margin-left:0px;margin-right:0px;">
                        <div class="col-md-5 col-xs-5"></div>
                        <div class="col-md-7 col-xs-7 text-right">
                            <a href="javascript:void(0);" class="close-info"><i style="font-size:20px;" class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div id="info_<?= $net_name;?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    
</div>
   
<script>
    
    function load_chart(){
        
        $('#info-chart:visible').highcharts({
            chart: {
                type: 'column',
                style: {
                    fontFamily: 'futura-pt'
                }
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: cats
            },
            yAxis: {
            },
            credits: {
                enabled: false
            },
            plotOptions: {
                column: {
                    stacking: 'normal'
                    }
            },
            series: [{
                name: 'Undelivered',
                data: undel
            }, {
                name: 'Delivered',
                data: del
            }]
        });
    }
</script>