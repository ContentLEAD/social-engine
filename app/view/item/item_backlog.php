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
    <body id="backlog_page">
        <div id="loading">
            <div id="loading-gif">
                <img src="/images/loader.gif">
            </div>
        </div>
        <div class="col-sm-9">
            <div class="row widget">
                <div class="col-md-10">
                    <h3><?=$client_name?></h3>
                </div>
                <div class="col-md-2 item-options">
                    <a href="javascript:void(0)" onclick="$('#backlog').html('<?= $ub ?>');$('.bl_count').html('0');$('.order-form').html('');">CLEAR</a>
                </div>
                <?php foreach($items as $item) :?>
                    <div class="col-sm-4">
                        <label data-label="<?= $item['id']?>"><?= $item['network'].' '.$item['action']  ?></label>
                        <div class="counter" id="<?= $item['id']?>" class="col-sm-12">
                            <div class="col-xs-4 plus" data-item="<?= $item['id']?>" data-time="<?= $item['time']?>"><i class="fa fa-plus"></i></div>
                            <div class="col-xs-4 bl_count" data-counter="<?= $item['id']?>">0</div>
                            <div class="col-xs-4 minus" data-item="<?= $item['id']?>" data-time="<?= $item['time']?>"><i class="fa fa-minus"></i></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>
        <div class="col-sm-3">
            <div class="row widget">
               <strong></strong>
                <div class="col-md-12 text-center">
                        Backlog Minutes
                </div>
                <div class="col-md-12 text-center">
                    <span id="backlog"><?= $ub; ?></span>
                </div>
            </div>
            <div class=" row widget item-options">
                <a href="javascript:void(0);" onclick="build_items();">Build Backlog Items</a>
            </div>
            <div class="row widget order-form">
                
            </div>
        </div>
        
        <script>
            var ob = <?= (int)$ub;?>;
            $('.plus').click(function(){
                //time 
                var time = parseInt($(this).attr('data-time'));
                var item = parseInt($(this).attr('data-item'));
                var counter = parseInt($('[data-counter="'+item+'"]').html());
                var backlog = parseInt($('#backlog').html());
                if((backlog-time) <0){
                    $(this).parent().effect('shake');
                    return;
                }
                //CHANGE COUNTER
                $('[data-counter="'+item+'"]').html(counter+1);
                $('#backlog').html(backlog-time);
                //ADD TO ORDER FORM
                if($('[data-order="'+item+'"]').length == 0){
                    html = "<div class='ord-item' data-order='"+item+"'><div class='col-xs-4' data-counter='"+item+"'>1</div><div class='col-xs-8'>"+$('[data-label="'+item+'"]').html()+"</div></div>";
                    $('.order-form').append(html);
                }
            });
            $('.minus').click(function(){
                //time 
                var time = parseInt($(this).attr('data-time'));
                var item = parseInt($(this).attr('data-item'));
                var counter = parseInt($('[data-counter="'+item+'"]').html());
                var backlog = parseInt($('#backlog').html());
                if((counter - 1) < 0){
                    $(this).parent().effect('shake');
                    return;
                }
                //CHANGE COUNTER
                $('[data-counter="'+item+'"]').html(counter-1);
                $('#backlog').html(backlog+time);
                //ADD TO ORDER FORM
                if((counter-1) == 0){
                    $('[data-order="'+item+'"]').remove();
                }
            });
            
            function build_items(){
                var sfid = '<?= $sfid?>';
                //LOADING ON
                $('#loading').slideDown();
                //CHECK TO MAKE SURE THERE ARE ITEMS TO BUILD
                if($('.ord-item').length == 0){
                    $('#loading').slideUp();
                    swal('Oh No!','There were no items to make!','error');
                }
                var order = new Array;
                //SORT ITEMS INTO MANAGBLE ARRAY
                $('.ord-item').each(function(){
                    item = $(this).attr('data-order');
                    count = $('[data-counter="'+item+'"]').html();
                    order.push({'item':item,'owed':count});
                });
                //
                var data = 'bl='+$('#backlog').html()+'&sfid='+sfid+'&data='+JSON.stringify(order);
                $.post('/index.php/ajax/backlog_build/',data).done(function(m){
                    m = JSON.parse(m);
                    if(m.success){
                        window.parent.location.reload();
                    }
                    else{
                        console.log(m);
                    }
                });
            }
        </script>
    </body>
</html>