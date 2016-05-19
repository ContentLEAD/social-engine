<div class="container">
    <div class="col-md-12 text-center">
            <?php
            if(isset($error)){
                echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
            }
            ?>
        <div id="login-box">
            <h3>Sign into SocialEngine</h3>
            <form method="post">
                <input type="email" name="email" placeholder="email" class="form-control">
                <input type="password" name="password" placeholder="Password" class="form-control">
                <button class="btn btn-success">
                    Login
                </button>
            </form>
            <br />
            <br />
            <a href="https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id=3MVG9yZ.WNe6byQCMmYGSGT6ArxOx.Tle4pncwhPae4TRqDgP6iTQHF9BchqKXaWonUohsBjEUG2CdoVXmR0Q&redirect_uri=https://tech.brafton.com/socialengine/index.php/gate/oauth">Login with Salesforce</a>
            <?=var_dump($_POST)?>
            <br /><br />
            <a href="/index.php/gate/add_user">Add User</a> | <a href="javascript:void(0)">Forgot Password</a>
        </div>
    </div>
</div>