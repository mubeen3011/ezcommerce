<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/10/2020
 * Time: 4:24 PM
 */
?>
<div class="row">
    <div class="col-md-4"></div>
    <div class="col-md-4">
        <form class="final-form-submission" action="" method="post">
            <input type="hidden" name="warehouse" value="<?=$_GET['warehouse']?>"/>
            <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />

            <div class="form-group">
                <label for="example-email">Zip Codes <span class="help"></span></label>
                <input type="text" id="justAnInputBox1" name="zip_codes" placeholder="Select"/>
            </div>

            <input type="submit" value="Update" class="form-control" />
        </form>
    </div>
    <div class="col-md-4"></div>
</div>
<script>
    var ZipCodesJson = <?=$allZipCodes?>;
    var PreSelectedZipCodes = [<?=implode(',',$pre_selected_zipcodes)?>];
    //alert(PreSelectedZipCodes);
</script>