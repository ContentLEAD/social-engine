<html>
    <head>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css?v=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
                <script src="https://use.typekit.net/odr1dhg.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>
        <script src="https://use.typekit.net/woo5yta.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>
        
    </head>
    <body>
        <div class="container">
            <div class="alert alert-danger" style="display:none;">
            </div>
            <form>
            <input type="hidden" name="sfid" value="<?= $client_id ?>">
                <label>Type:</label>
            <select name="type" class="form-control">
                <option>ROLLING</option>
                <option>FIXED</option>
            </select>
            <input type="date" name="end_date" class="form-control" style="display:none;">
            <label>Network:</label>
            <select name="network" class="form-control">
                <?php foreach($network as $key) :?>
                    <option><?=$key->network;?></option>
                <?php endforeach;?>
            </select>
            <label>Action:</label>    
            <select class="form-control" name="action">
                    <option>POST</option>
                    <option>AD</option>
                    <option>LISTENING</option>
            </select>
                <label>Amount:</label>
            <div class="row">
                <div class="col-md-6">
                    <input type="number" placeholder="Amount" name="start_amount" class="form-control">
                </div>
                <div class="col-md-6">
                 
                </div>
            </div>
            </form>
            <button class="btn btn-success" style="height:30px;width:100px;text-align:center;">Create Item</button>
        </div>
        
        <script>
            $('[name="type"]').change(function(){
                  if($(this).val == 'FIXED'){
                    $('[name="end_date"]').show();
                  }else{
                    $('[name="end_date"]').hide();
                  }
            });
            
            $('[name="network"]').change(function(){
                $.get('/index.php/ajax/item_form/'+ $('[name="network"]').val(),function(ret){
                    console.log(ret);
                    $('[name="action"]').html(ret);
                });
            })
            
            $('.btn').click(function(){
                $(this).html('<i class="fa fa-cog fa-spin"></i>');
                $(this).attr('disabled','true');
                var post_s = $('form').serialize();
                $.post('/index.php/ajax/create_item',post_s).done(function(m){
                    
                    console.log(m);
                    var m = JSON.parse(m);
                    if(!m.success){
                        $('.alert').html(m.response);
                        $('.alert').slideDown();
                        $('.btn').attr('disabled',false);
                        $('.btn').html('Create Item');
                    }else{
                        window.parent.location.reload();
                    }
                });
                
            });
        </script>
    </body>
</html>
