<?php

if ($po){
    ?>
    <input name="po_code" class="form-control" readonly value="<?= $po->po_code ?>">
<?php
}else{
    ?>
    <select name="po_code" class="po_code form-control custom-select">
        <option value="<?=$po_code?>"><?=$po_code?></option>
    </select>

    <?php
}

?>
