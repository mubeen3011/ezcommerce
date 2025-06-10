<style>
    td, th {
        border: solid 1px black;
    }
</style>
<table>
    <tr>
        <td>Deal Name:</td>
        <td><?=$dm->name?></td>
    </tr>
    <tr>
        <td>Shop:</td>
        <td><?=$dm->channel->name?></td>
    </tr>
    <tr>
        <td>Start Date:</td>
        <td><?=$dm->start_date?></td>
    </tr>
    <tr>
        <td>End Date:</td>
        <td><?=$dm->end_date?></td>
    </tr>
    <tr>
        <td>Motivation:</td>
        <td><?=$dm->motivation?></td>
    </tr>
</table>
<br/>
<br/>
<br/>
<table style="border: 1px solid" id="demo-foo-row-toggler"  class="dm-multi-sku table toggle-circle table-hover default breakpoint footable-loaded footable table-striped table-bordered nowrap">
    <thead>
    <tr style="text-align: center">

        <th data-toggle="true" class="footable-visible footable-first-column footable-sortable footable-sorted multi-sku side-border" style="border-left: 0px !important;">
            SKUs
        </th>
        <th class="multi-sku-price side-border footable-visible footable-sortable">Price</th>
        <th class="multi-sku-subsdiy side-border footable-visible footable-last-column footable-sortable">Subsidy</th>
        <th class="multi-sku-subsdiy side-border footable-sortable footable-sortable">Current Stock</th>
        <th class="multi-sku-qty side-border footable-sortable" >Sales Target</th>
        <th class="multi-sku-qty side-border footable-sortable" >Margin %</th>
        <th class="multi-sku-qty side-border footable-sortable" >Margin <?=Yii::$app->params['currency']?></th>
        <th class="multi-sku-qty side-border footable-sortable">Reasons</th>
        <th class="multi-sku-qty side-border footable-sortable">Status</th>
        <th class="multi-sku-qty side-border footable-sortable">Comments</th>

    </tr>
    </thead>
    <?php
    $Ccounter=0;
    foreach ($multiSkus as $k => $v):
        if ($Ccounter % 2 == 0) {
            $rowClass= "white";
        }else{
            $rowClass= "#f2f7f8";
        }
        $bgColor = ($v['status'] == 'Approved') ? '#f5f5f5' : '';
        $bgColor = ($v['status'] == 'Cancel' || $v['status'] == 'Reject' ) ? '#fbd3d3' : $bgColor;
        $readonly = ($v['status'] == 'Approved') ? 'readonly' : '';
        ?>
        <tr style="background-color: <?=$rowClass?> !important;" class='row-<?= str_replace('s_','',$k) ?>'>
            <td  ><?= $v['sku'] ?></td>
            <td class="tg-kr944"  ><?= $v['price'] ?></td>
            <td class="tg-kr944"  ><?= $v['subsidy'] ?></td>
            <td><strong class="form-control"><?= isset($v['stocks']) ? $v['stocks'] : '0' ?></strong></td>
            <td class="tg-kr944"  ><?= $v['qty'] ?></td>

            <td class="tg-kr944"  ><?=$v['margin_per']?>%</td>
            <td class="tg-kr944"  ><?=$v['margin_rm']?></td>
            <td class="tg-kr944"  ><?=$v['reason']?></td>
            <td class="tg-kr944"  ><?= ($v['status'] == '') ? 'Pending':$v['status'] ?></td>
            <td class="tg-kr944"  ><?= $v['comments']?></td>


        </tr>
        <?php
        $Ccounter++;
    endforeach; ?>
</table>