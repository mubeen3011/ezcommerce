<?php

use common\models\Channels;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\DealsMakerSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'SKUs Margins Definitions';
$this->params['breadcrumbs'][] = ['label' => 'Deals', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="deals-maker-index">

    <div class="col-md-12 panel panel-default pull-left sku-dl-view">
            <hr>
            <?= Html::a('<i class="glyph-icon icon-cog"></i> Update deal margins settings', ['/settings/update?id=13'], ['class' => 'btn btn-sm btn-warning']) ?>
        <div class="panel-body">
            <table class="table table-border dlm-admin-skus-2">
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>Selling Status</th>
                    <th>Stock Value</th>
                    <th>Aging Status</th>
                    <th>Margins <small>(Weighted Avg)</small></th>
                    <th>Allowed Margins</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($skus as $r): ?>
                    <tr>
                        <td><?=$r->sku->sku?></td>
                        <td><?=ucwords($r->selling_status)?></td>
                        <td><?=ucwords($r->stock_status)?></td>
                        <td><?=ucwords($r->aging_status)?></td>
                        <td><?=$r->weighted_avg_margins?></td>
                        <td><?=$r->allowed_margins?></td>

                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
