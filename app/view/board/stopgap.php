<?php
    //echo '<pre>';
    //var_dump($term);
?>
<div id="stop_gap">
    <div class="container">
        <h2>Terminated Client</h2>
        <h4>You must close the client is SE, or have account manager move out of termination.</h4>
        <table class="table">
            <?php foreach($term as $k => $v) :?>
            <?php 
                $x = current($v);              
            ?>
            
            <tr>
                <td><?= $x['client_name']?></td>
                <td style="text-align:right;"><button class="btn btn-success" onclick="close_client('<?= $x['sfid'];?>')">Close Client</button></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<script>
function close_client(sfid){
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
}
</script>