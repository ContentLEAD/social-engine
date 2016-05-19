<html>
    <head>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css?v=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
        <link rel="stylesheet" href="/css/sweetalert.css">


        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://use.typekit.net/woo5yta.js"></script>
        <script src="/js/sweetalert.min.js"></script>

        <script src="https://use.typekit.net/odr1dhg.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>
        <script src="https://use.typekit.net/woo5yta.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>    </head>
    <body>
        <div class="row">
            <div class="col-sm-3">
                <div class="row widget">
                    <h3>
                        <?= $client['client_name']?>
                    </h3>
                    <?= $item['network']?>
                    <?= $item['action']?><br />                    
                    <?= $item['type']?><br />                    
                </div>
                <div class="row widget item-options">
                    <?php if($item['type'] != 'BACKLOG') :?>
                    <a href="javascript:void(0);" onclick="adjust_amount('<?= $item['item_id']?>');">Adjust Amount</a>
                    <?php endif; ?>
                    <?php if($item['type'] == 'ROLLING') :?>
                    <a href="javascript:void(0);" onclick="close_item('<?= $item['item_id']?>');">Close Item</a>
                    <?php endif; ?>
                </div>
                <div class="row widget">
                    <label>CREATED DATE</label><?= date('m/d/y', $item['created_date']) ?>
                    <label>LIVE DATE</label><?= date('m/d/y', $item['live_date']) ?>
                    <label>CURRENT AMOUNT</label><?=$item['start_amount'] ?>
                    <label>CURRENT OWNER</label><?=$client['user_id'] ?>
                </div>
                <div class="row widget text-left">
                    <strong>Activity Periods</strong>
                    <?php foreach($item['period'] as $p) : ?>
                    <table class="table table-condensed">
                        <tr><td><?= $p['name']?></td><td><?= $p['owed']?></td></tr>
                    </table>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="row widget">
                    <div class="col-sm-6">
                        <strong>Current Numbers</strong>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a class="ext" href="javascript:void(0);" data-href="<?= $item['item_id']?>"><i class="fa fa-external-link"></i></a>
                    </div>
                    
                    <table class="table table-condensed">
                        <tr>
                            <TH></TH>
                            <th>OWED</th>
                            <th>DELIVERED</th>
                            <th>BALANCE</th>
                        </tr>
                        <tr>
                            <td>MNTH</td>
                            <td id="current_amount"><?= $sum_data['owed']?></td>
                            <td><?= $sum_data['delivered'] ?></td>
                            <td><?= $sum_data['owed']- $sum_data['delivered'] ?></td>
                        </tr>
                        <tr>
                            <td>TODATE</td>
                            <td><?= round($sum_data['owed_td'],1, PHP_ROUND_HALF_UP ) ?></td>
                            <td><?= $sum_data['delivered'] ?></td>
                            <td><?= round($sum_data['owed_td']- $sum_data['delivered'],1, PHP_ROUND_HALF_UP )?></td>
                        </tr>
                    </table>
                </div>
                <div class="row widget">
                    <div class="col-md-12 text-right">
                        |<a href="#">Monthly Delivery</a>|
                        <a href="#">Distibution</a>|
                        <a href="#">Monthly Distibution</a>|
                    </div>
                    <div id="dist" >

                    </div>                
                </div>

            </div>
        </div>
        <?php
            foreach($chart as $month => $ar){
                $cat[] = $month;
                $undel[] = $ar['owed']-$ar['del'];
                $owed[] = $ar['owed'];
                $del[] = $ar['del'];
            }
            var_dump($chart);
            $cat = json_encode($cat);
            $undel = json_encode($undel);
            $owed = json_encode($owed);
            $del = json_encode($del);
        ?>
        <script>
            
            $('.ext').click(function(){
                window.parent.location = '/index.php/lightbox/item_info/'+$(this).attr('data-href');
            });
            //CHANGE ITEM
            function adjust_amount(id){
                //GRAB THE CURRENT AMOUNT.
                var current_amount = $('#current_amount').html();
                //SWEET ALERT
                swal({
                    title: "An input!",
                    text: "Write something interesting:",
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    animation: "slide-from-top",
                    inputPlaceholder: "Write something"
                },
                function(inputValue){
                    if (inputValue === false) return false;

                    if (inputValue === "") {
                        swal.showInputError("You need to write something!");
                        return false
                    }
                    if (parseInt(inputValue) - parseInt(inputValue) != 0) {
                        swal.showInputError("Needs to be a number!");
                        return false
                    }
                    var data = 'item_id='+id+'&start_amount='+parseInt(inputValue);
                    $.post('/index.php/ajax/change_amount',data).done(function(m){
                        console.log(m);
                        swal("Awesome!", "We've Changed this month's amount to: " + inputValue, "success");
                    });
                });
                //CONFIM AND REFRESH
                
            }
            function adjust_bl(id){
                //FIRST CHECK FOR BACK LOG
                
                //
            }
            //CLOSE ITEM
            function close_item(id){
                swal({
                    title: "Are you sure?",
                    text: "Closing this Item cannot be undone",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, Close it!",
                    cancelButtonText: "No, Keep it live",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function(isConfirm){
                    if (isConfirm) {
                        $.get('/index.php/ajax/close_item/'+id,function(){
                            swal("Closed!", "This item has been removed from you workflow.", "success");
                        });
                    } else {
                        swal("Cancelled", "This item is safe :)", "error");
                    }
                });          

            }
        </script>
        <script>                
            
            $(function () {
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
                var cats = JSON.parse('<?= $cat; ?>');
                var undel = JSON.parse('<?= $undel; ?>');
                var owed = JSON.parse('<?= $owed; ?>');
                var del = JSON.parse('<?= $del; ?>');
                
                $('#dist').highcharts({
                    chart: {
                        type: 'column',
                        style: {
                            fontFamily: 'futura-pt'
                        }
                    },
                    title: {
                        text: 'Monthly Performance'
                    },
                    xAxis: {
                        categories: cats
                    },
                    yAxis: {
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
            });
        </script>
    </body>
</html>