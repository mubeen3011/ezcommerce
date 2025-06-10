<?php

use common\models\Category;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use kartik\daterange\DateRangePicker;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\CostPriceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Cost Prices';
$this->params['breadcrumbs'][] = $this->title;
$focskus = \common\models\CostPrice::find()->select(['id','sku'])->where(['sub_category'=>'167'])->all();
$data = ArrayHelper::map($focskus,'id', 'sku');
?>

<table class="table table-striped" id="table" >
    <thead>
    <tr>
        <th class="tg-ss" >SKU</th>
        <th class="tg-ss">Category</th>
        <th class="tg-ss" style="width:15% !important;">Selling Status</th>
        <th class="tg-ss" style="width:15% !important;">Stock Status</th>
        <th class="tg-ss">Name</th>
        <th class="tg-ss">Cost Price</th>
        <th class="tg-ss">RCCP Cost</th>
        <th class="tg-ss" style="width:15% !important;">FBL</th>
        <th class="tg-ss" style="width:15% !important;">Active</th>
        <th class="tg-ss" style="width:50%;">FOC Skus</th>


    </tr>
    </thead>
    <tfoot>
    <tr>
        <th class="tg-kr94" style="width:8px !important;">SKU</th>
        <th class="tg-kr94">Category</th>
        <th class="tg-kr94" style="width:15% !important;">Selling Status</th>
        <th class="tg-kr94" style="width:15% !important;">Stock Status</th>
        <th class="tg-kr94">Name</th>
        <th class="tg-kr94">Cost Price</th>
        <th class="tg-kr94">RCCP Cost</th>
        <th class="tg-kr94" style="width:15% !important;">FBL</th>
        <th class="tg-kr94" style="width:15% !important;">Active</th>
        <th class="tg-ss"></th>

    </tr>
    </tfoot>
    <tbody>
    <?php $i = 1;
    foreach ($skuList as $sl): ?>
        <tr>
            <form id="sku_<?= $sl['id'] ?>">
                <input id="form-token" type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                       value="<?= Yii::$app->request->csrfToken ?>"/>
                <td class="tg-ss" style="width:8px !important;" data-sku-id="<?= $sl['id'] ?>">

                    <input type="hidden" name="sku_id" value="<?= $sl['id'] ?>">
                    <a   style="color:blue"
                       href="<?= \yii\helpers\Url::to(['/competitive-pricing/sku-details', 'sku' => ($sl['sku'])]) ?>"><?= $sl['sku'] ?></a>
                </td>
                <td class="tg-ss">
                    <?= $sl['subCategory']['name']; ?>
                </td>
                <td class="tg-cp"  style="width:15% !important;">
                    <?php
                    $selling_status = isset($sl['selling_status']) ? $sl['selling_status'] : '';
                    ?>
                    <select class="form-control" name="selling_status_<?= $sl['id'] ?>" >
                        <option value="" <?= $selling_status == "" ? 'selected' : '' ?> >Select</option>
                        <option value="Slow" <?= $selling_status == "Slow" ? 'selected' : '' ?> >Slow</option>
                        <option value="Medium" <?= $selling_status == "Medium" ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= $selling_status == "High" ? 'selected' : '' ?>>High</option>
                    </select>
                </td>
                <td class="tg-cp"  style="width:15% !important;">
                    <?php
                    $stock_status = isset($sl['stock_status']) ? $sl['stock_status'] : '';
                    ?>
                    <select class="form-control" name="stock_status_<?= $sl['id'] ?>" >
                        <option value="" <?= $stock_status == "" ? 'selected' : '' ?> >Select</option>
                        <option value="Slow" <?= $stock_status == "Slow" ? 'selected' : '' ?> >Slow</option>
                        <option value="Medium" <?= $stock_status == "Medium" ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= $stock_status == "High" ? 'selected' : '' ?>>High</option>
                        <option value="Not Moving" <?= $stock_status == "Not Moving" ? 'selected' : '' ?>>Not Moving</option>
                    </select>
                </td>
                <td class="tg-ss" style="width: 20%">
                    <?= $sl['name']; ?>
                </td>
                <td class="tg-cp">
                    <?php
                    $price = isset($sl['cost']) ? $sl['cost'] : '';
                    ?>
                    <?= $price ?>
                </td>
                <td class="tg-cp">
                    <?php
                    $rccp_price = isset($sl['rccp_cost']) ? $sl['rccp_cost'] : '';
                    ?>
                    <?= $rccp_price ?>
                </td>
                <td class="tg-cp" style="width:15% !important;">
                    <?php
                    $fbl = $sl['fbl'];

                    ?>
                    <select class="form-control" name="fbl_<?= $sl['id'] ?>">
                        <option value="1" <?= $fbl == 1 ? 'selected' : '' ?> >Yes</option>
                        <option value="0" <?= $fbl == 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </td>
                <td class="tg-cp" style="width:15% !important;">
                    <?php
                    $active = $sl['is_active'];

                    ?>
                    <select class="form-control" name="active_<?= $sl['id'] ?>">
                        <option value="1" <?= $active == 1 ? 'selected' : '' ?> >Yes</option>
                        <option value="0" <?= $active == 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </td>
                <td class="tg-cp">
                    <?php
                    $seledFocs = [];
                    $focs = \common\models\FocSkus::find()->where(['sku_id'=>$sl['id']])->all();
                    if($focs)
                    {
                        foreach ($focs as $v)
                            $seledFocs[] = $v->foc_sku_id;
                    }
                    ?>
                    <?php
                    echo Select2::widget([
                        'name' => 'foc_skus_'.$sl['id'],
                        'data' => $data,
                        'value' => $seledFocs,
                        'options' => [
                            'placeholder' => 'Select FOCs ...',
                            'multiple' => true
                        ],
                    ]);

                    ?>
                </td>
            </form>
        </tr>
        <?php $i++; endforeach; ?>
    </tbody>
</table>
<?php
$this->registerJs('function save_cp(cur)
{
    var $row = cur.parents(\'tr\');
    var fields = $row.find(\'form\').serialize();
   /* var result = window.confirm(\'Are you sure to make changes for this SKU?\');
    if(result != false)
    {
        $.ajax({
            type: "post",
            url: "/cost-price/save",
            data: fields,
            dataType: "json",
            beforeSend: function () {},
            success: function (data) {
                cur.css(\'border-color\',\'green\');

            },
        });
    }*/
    $.ajax({
        type: "post",
        url: "/cost-price/save",
        data: fields,
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css(\'border-color\',\'green\');

        },
    });
}');