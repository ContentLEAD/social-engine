<div class="container">
    <div class="col-md-12">
        <?php
            if(isset($error)){
                echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
            }
        ?>
        <div id="login-box">
            <div class="col-md-12 text-center">
                <strong>Add A User</strong>
            </div>
            <form method="post">
                <input class="form-control" type="text" placeholder="First Name" name="first_name">
                <input class="form-control" type="text" placeholder="Last Name" name="last_name">
                <input class="form-control" type="email" placeholder="Email" name="email">
                <input class="form-control" type="password" placeholder="password" name="password">
                <input class="form-control" type="password" placeholder="Password Confirm" name="password_confirm">
                <button class="btn btn-success" disabled>Submit</button>
            </form>
        </div>
    </div>
</div>
<script>
    $('.form-control').keyup(function(){
        var p = $('[name="password"]').val();
        var pc = $('[name="password_confirm"]').val();
        var fn = $('[name="first_name"]').val();
        var ln = $('[name="last_name"]').val();
        var email = $('[name="email"]').val();
        if(p == pc && p.length >0 && fn.length >0 && ln.length >0 && email.length >0){
            $('.btn').removeAttr('disabled');
        }else{
            $('.btn').attr('disabled',true);
        }
    });
</script>