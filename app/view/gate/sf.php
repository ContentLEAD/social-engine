<div class="container">
    <div class="col-md-12 text-center">
            <?php
            if(isset($error)){
                echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
            }
            ?>
        <div id="login-box">
            <h3>Sign into SocialEngine</h3>
            <a class="btn btn-primary"  style="color:white;" href="https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id=3MVG9yZ.WNe6byQCMmYGSGT6ArxOx.Tle4pncwhPae4TRqDgP6iTQHF9BchqKXaWonUohsBjEUG2CdoVXmR0Q&redirect_uri=https://tech.brafton.com/socialengine/index.php/gate/oauth/"><i class="fa fa-cloud"></i>&nbsp;Login with Salesforce</a>
            <br />
            <br />
        </div>
    </div>
</div>