<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/17/2018
 * Time: 6:25 PM
 */
$Bundle_stock=0;
$stock_bundle=[];


foreach ( $child_skus as $key=>$value ){
    if ( $value['stocks'] == '' || $value['stocks'] == null){
        $stock_bundle[]=0;
        continue;
    }
    $stock_bundle[]=floor($value['stocks']/$value['child_quantity']);
}
$stock_bundle[] = $Parent_Stocks_Quantity;
$Bundle_stock=min($stock_bundle);
?>
<h2>Main Sku : <?=$main_sku?></h2>
<p style="margin: 0px;font-weight: bold">Bundle Name: <?=$bundle_info[0]['relation_name']?></p>
<p style="margin: 0px;font-weight: bold">Bundle Type: <?=$bundle_info[0]['relation_type']?></p>
<p style="margin: 0px;font-weight: bold">Start At: <?=$bundle_info[0]['start_at']?></p>
<p style="margin: 0px;font-weight: bold">End At: <?=$bundle_info[0]['end_at']?></p>
<p style="margin: 0px;font-weight: bold">Bundle Cost: <?=$bundle_info[0]['bundle_cost']?></p>
<p style="margin: 0px;font-weight: bold">Bundle Stock: <?=$Bundle_stock?></p>
<table class="table">
    <thead>
        <th>Child Sku</th>
        <th>Qty</th>
        <th>Type</th>
        <th>Stocks</th>
        <th>Bundle Possiblities</th>
    </thead>
    <tbody>
    <?php
    foreach ( $child_skus as $key=>$value ){
        ?>
        <tr>
            <td><?=$value['sku']?></td>
            <td><?=$value['child_quantity']?></td>
            <td><?=$value['child_type']?></td>
            <td><?=isset($value['stocks']) ? $value['stocks'] : 'Stock is empty, There is no value set.' ?></td>
            <td><?=$Bundle_stock?></td>
        </tr>
    <?php
    }
    ?>
    </tbody>
</table>