<div id="loading_dash">
    <div id="loading_dash-gif" style="display:block;">
        <img src="/images/loader.gif">
    </div>
</div>
<div class="container">
    <?php if(!empty($unclosed)) :?>
        <?php foreach($unclosed as $key) :?>
        <div class="col-md-12 alert alert-danger" style="margin-top:5px;">
            <strong>IMPORTANT NOTICE:</strong> <span class="close_items" data-sfid="<?= $key['sfid']?>" style="color:black;"><?= $key['client_name']?></span>  has been marked as terminated in SalesForce. Please close if no work remains.
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="col-md-4" id="left">
        
        <!--USER PANE WIDGET-->
        <div class="row widget" id="user_pane">
            <div class="col-md-4 text-center" id="avatar">
                <i class="fa fa-user"></i>
            </div>
            <div class="col-md-8">
                Hello&nbsp;<strong><?=$gbl_first_name?></strong><br />
            </div>
        </div>
        
        <!--STICKY NOTES WIDGET-->
        <div class="row widget" id="notes">
            <div class="col-md-6 text-left">
                <strong>Stickies</strong>
            </div>
            <div class="col-md-6 text-right">
                <i class="fa fa-plus-circle note-add"></i>
            </div>
            <hr />
            <div id="sticky_wid">
            <?php if(count($notes) >0) :?>
                <?php foreach($notes as $key) :?>
                <div data-sticky-id="<?=$key['id'];?>" class="col-md-12 stickies">
                    <div class="row text-right">
                        <i class="fa fa-trash" onclick="trash_note(<?=$key['id'];?>)"></i>
                    </div>
                    <div class="row">
                        <?=$key['note'];?>
                    </div>
                </div>
                <?php endforeach;?> 
            <?php endif ;?>
            </div>
        </div>
    </div>
    <div class="col-md-4" id="center">
        <div class="row text-center widget">
            <h2><?= date('D M d, Y',time())?></h2>
        </div>
        <div class="row widget">
            <div id="chart" style="height:200px;"></div>
        </div>
        <div class="row widget" >
            <div id="line" style="height:200px;"></div>
        </div>
    </div>
    <div class="col-md-4" id="right">
        <?php if($status == 1) :?>
        <div class="row widget" id="board">
            <a href="/index.php/dashboard/admin">Admin Board<i class="fa fa-arrow-circle-o-right"></i></a>
        </div>
        <?PHP endif; ?>
        <div class="row widget" id="board">
            <a href="/index.php/board/">Go To Board<i class="fa fa-arrow-circle-o-right"></i></a>
        </div>
        
        <!--OVERDUE CLIENTS -->
        <div class="row widget">
            <?php if(count($overdue) >0) : ?>
            <?php foreach($overdue as $key) : ?>
            <a class="row od-item" href="<?=$key['location']?>">
                <div class="col-md-8"><?=$key['name']?></div>
                <div class="col-md-4"><?=$key['amount']?></div>
            </a>
            <?php endforeach; ?>
            <?php else :?>
            <strong>No Overdue Items</strong>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $('.close_items').click(function(){
        var sfid = $(this).attr('data-sfid');
        swal({
            title: "Are you sure?",
            text: "Closing this client will close all associated items, this can not be undone!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, close it!",
            cancelButtonText: "No, Don't Close!",
            closeOnConfirm: false,
            closeOnCancel: false
        },
        function(isConfirm){
            if (isConfirm) {
                $.get('/index.php/ajax/shut_down_client/'+sfid,function(m){
                    console.log(m);
                    swal("Closed!", "This client has been removed from your workload.", "success",function(){
                        location.reload(); 
                    });
                });
            } else {
                swal("Cancelled", "", "error");
            }
        });
    });
    
    
    
    
    $(function () {

    // Radialize the colors
    Highcharts.getOptions().colors = Highcharts.map(Highcharts.getOptions().colors, function (color) {
        return {
            radialGradient: {
                cx: 0.5,
                cy: 0.3,
                r: 0.7
            },
            stops: [
                [0, color],
                [1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
            ]
        };
    });
    var data = JSON.parse('<?= json_encode($pie); ?>');
    // Build the chart
    $('#chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie',
            spacingTop: 0,
            spacingBottom: 0
        },
        title: {
            text: ''
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    },
                    connectorColor: 'silver'
                }
            }
        },
        series: [{
            name: 'Actions',
            data: data
        }]
    });
});
    
//GET DISTIBUTION
    
$.get('/index.php/ajax/dash_dist',function(m){
    var data = JSON.parse(m);
    var char_data = new Array;
    
    $('#line').highcharts({
        chart:{
            type:'line'
        },  
        title: {
            text: ''
        },
        xAxis: {
           
        },

        series: [{
            name:'Actions',
            data: data.response
        }]
    });
});
$(window).load(function(){
        $('#loading_dash').fadeOut();
    });
</script>