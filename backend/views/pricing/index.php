<?php

use common\models\CompetitivePricing;
use kartik\daterange\DateRangePicker;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\PricingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Sales Price';
$this->params['breadcrumbs'][] = $this->title;
$include = [1, 2, 3, 5, 6];
$channelList = \backend\util\HelpUtil::getChannels($include);
$skuList =  $cp = CompetitivePricing::find()->where([ 'created_at' => $date])->groupBy(['sku_id'])->all();
?>

<style type="text/css">
    input, select, textarea {
         width: auto !important;
    }
    /* Hiding the checkbox, but allowing it to be focused */
    .badgebox
    {
        opacity: 0;
    }

    .badgebox + .badge
    {
        /* Move the check mark away when unchecked */
        text-indent: -999999px;
        /* Makes the badge's width stay the same checked and unchecked */
        width: 27px;
    }

    .badgebox:focus + .badge
    {
        /* Set something to make the badge looks focused */
        /* This really depends on the application, in my case it was: */

        /* Adding a light border */
        box-shadow: inset 0px 0px 5px;
        /* Taking the difference out of the padding */
    }

    .badgebox:checked + .badge
    {
        /* Move the check mark back when checked */
        text-indent: 0;
    }



</style>

<div class="pricing-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <legend>
        <div class="col-md-12">
            <form id="filter" action="/pricing/index" method="post">
                <input name="_csrf-backend"
                       value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
                       type="hidden">
                <div class="form-group field-sku_id required has-success">
                    <label class="control-label" for="sku_id">Archive:</label>
                    <?= DateRangePicker::widget([
                        'name' => 'date',
                        'value' => $date,
                        'convertFormat' => true,
                        'options'=>['class'=>'date_filter form-control','onkeydown'=>'return false'],
                        'pluginOptions' => [
                            'autoclose' => true,
                            'singleDatePicker' => true,
                            'showDropdowns' => false,
                            'todayBtn' => true,
                            'locale'=>['format'=>'Y-m-d'],
                            'maxDate' => date('Y-m-d'),
                        ]
                    ]);
                    ?>
                    <div class="help-block"></div>
                </div>
                <div class="form-group Columns required has-success">
                    <label class="control-label" for="sku_id">Columns:</label>
                    <br/>
                    <div class="row">
                        <label for="default" class="btn btn-warning">BASE PRICES AT ZERO MARGINS <input type="checkbox" id="default" class="badgebox" data-class="bp-zm" value="1"><span class="badge">&check;</span></label>
                        <label for="primary" class="btn btn-warning">BASE PRICES BEFORE SUBSIDY <input type="checkbox" id="primary" class="badgebox" data-class="bp-bs" value="1"><span class="badge">&check;</span></label>
                        <label for="info" class="btn btn-warning">BASE PRICES AFTER SUBSIDY <input type="checkbox" id="info" class="badgebox" data-class="bp-as" value="1"><span class="badge">&check;</span></label>
                        <label for="success" class="btn btn-warning">GROSS PROFIT <input type="checkbox" id="success" class="badgebox" data-class="gp" value="1"><span class="badge">&check;</span></label>
                        <label for="warning" class="btn btn-warning">SALES PRICE <input disabled checked type="checkbox" id="warning" class="badgebox" data-class="sp" value="1"><span class="badge">&check;</span></label>
                        <label for="danger" class="btn btn-warning">MARGIN AT LOWEST MARKET PRICE <input type="checkbox" id="danger" class="badgebox" data-class="marg" value="1"><span class="badge">&check;</span></label>


                    </div>

                    <div class="help-block"></div>
                </div>
            </form>
        </div>

    </legend>

    <hr>
    <table class="pricing-tbl table-bordered table-responsive table-rounded" id="table"
           data-toggle="table"
           data-show-export="true">
        <tr>
            <th class="tg-kr94" colspan="2">SKUs</th>
            <th class="tg-kr94 bp-zm" colspan="5">BASE PRICES AT ZERO MARGINS</th>
            <th class="tg-kr94 bp-bs" colspan="5">BASE PRICES BEFORE SUBSIDY</th>
            <th class="tg-kr94 bp-as" colspan="5">BASE PRICES AFTER SUBSIDY</th>
            <th class="tg-kr94 gp" colspan="5">GROSS PROFIT</th>
            <th class="tg-kr94 sp" colspan="5">SALES PRICE</th>
            <th class="tg-kr94 marg" colspan="5">MARGIN AT LOWEST MARKET PRICE</th>
        </tr>
        <tr>
            <th class="tg-kr94" colspan="2">ID</th>
            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 bp-zm"><?= $cl['name'] ?></th>
            <?php endforeach; ?>

            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 bp-bs"><?= $cl['name'] ?></th>
            <?php endforeach; ?>

            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 bp-as"><?= $cl['name'] ?></th>
            <?php endforeach; ?>

            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 gp"><?= $cl['name'] ?></th>
            <?php endforeach; ?>

            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 sp"><?= $cl['name'] ?></th>
            <?php endforeach; ?>

            <?php foreach ($channelList as $cl): ?>
                <th class="tg-kr94 marg"><?= $cl['name'] ?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($skuList as $sku ): ?>
        <tr>
            <td class="tg-kr94" colspan="2" data-sku-id="<?= $sku->sku_id ?>">
                <a style="color:blue" href="<?=\yii\helpers\Url::to(['/competitive-pricing/sku-details','sku'=>($sku->sku->sku)])?>"><?= $sku->sku->sku ?></a>
                <?/*=(isset($channelSkuList['2'][$sku->sku_id])) ? print_r($channelSkuList['2'][$sku->sku_id]) : '' */?>
            </td>
            <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 bp-zm"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['base_price_at_zero_margin'] : '' ?></td>
            <?php endforeach; ?>
            <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 bp-bs"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['base_price_before_subsidy'] : '' ?></td>
            <?php endforeach; ?>
            <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 bp-as"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['base_price_after_subsidy'] : '' ?></td>
            <?php endforeach; ?>
        <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 gp"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['gross_profit'] : '' ?></td>
            <?php endforeach; ?>
        <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 sp"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['sales_price'] : '' ?></td>
            <?php endforeach; ?>
        <?php foreach ($channelList as $cl):  ?>
                <td class="tg-kr94 marg"><?=(isset($channelSkuList[$cl['id']][$sku->sku_id])) ? $channelSkuList[$cl['id']][$sku->sku_id]['sales_margins'] : '' ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
