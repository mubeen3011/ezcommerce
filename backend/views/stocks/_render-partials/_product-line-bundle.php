<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/19/2018
 * Time: 11:18 AM
 */
// remove bundle already exist
if ($_POST['already_in_list_bundle']!='[]')
    $cond="AND pr.id NOT IN (".implode(',',$_POST['already_in_list_bundle']).")";
else
    $cond='';

$sql = "SELECT * FROM products_relations pr
WHERE pr.is_active = 1 AND pr.end_at > '".date('Y-m-d')."' AND (pr.relation_type = 'FB' OR pr.relation_type='VB' OR pr.relation_type='FOC')
".$cond;
//echo $sql;die;
$data = \common\models\ProductsRelations::findBySql($sql)->asArray()->all();
?>
<style>
    .select2-container{
        width:100% !important;
    }
</style>
<div class="form-group">
    <label>Bundle</label>
    <select name="<?=$warehouse?>_bundle" class="bundle-selects select2 form-control <?=$warehouse?>_bundle" style="padding: 0px !important;">
        <option value="-1">Select Bundle</option>
        <?php
        foreach ($data as $k=>$v):?>
            <option value="<?=$v['id']?>"><?=$v['relation_name']?></option>

        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Quantity</label>
    <input type="number" value="1" class="bundle-quantity-<?=$warehouse?> form-control" oninput="validity.valid||(value='');" onchange="if(this.value<=0 ){this.value=1;}" placeholder="Quantity">
</div>

<div class="form-group pull-right">
    <input type="button" class="btn-check-bundle form-control btn btn-success" style="
    color: white;width: 70px;" value="Add">
</div>

