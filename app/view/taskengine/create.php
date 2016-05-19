<?php
//echo '<pre>';
//var_dump($clients);
?>
<form method="post">
    <div class="container">
        <div class="col-md-8">
            <div class="col-md-6">
                <label>Created By:&nbsp;</label><?=$user_name;?><br />
            </div>
            <div class="col-md-6">
                <label>Due Date:&nbsp;</label><input id="datepicker" type="date" name="due_date"><br />
            </div>
            <div class="col-md-6">
                <label>Assign to Client</label>
                <select name="client" class="form-control">
                    <option value="null">Unassigned</option>
                    <?php foreach ($clients as $key):?>
                    <option value="<?= $key->Id ?>"><?= $key->fields->Name ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Assign to Owner</label>
                <select name="owner" class="form-control">
                    <option value="null"><?=$user_name;?></option>
                    <?php foreach ($teammates as $key =>$v):?>
                    <option value="<?= $v['id'] ?>"><?= $v['first_name'].' '.$v['last_name']  ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-12">
                <label>Subject: </label><br />
                <input type="text" name="subject" class="form-control" />
                <label>Note: </label><br />
                <textarea name="note" class="form-control" ></textarea>
                <input type="submit" class="btn btn-success" value="submit" />
            </div>
    </div>
</form>
  <script>
  $(function() {
    $( "#datepicker" ).datepicker();
  });
  </script>