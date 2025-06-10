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

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Deals', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Category Mappings';
//echo '<pre>';print_r($cat);die;
?>
    <div class="row">

        <div class="col-12">
            <div class="card">

                <div class="card-body">

                    <div class=" row">
                        <div class="col-md-4 col-sm-12">
                            <h3><?='Category Mappings'?></h3>
                        </div>
                        <div class="col-md-4 col-sm-12">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>
                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>

                    <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe" data-tablesaw-mode-switch>
                        <thead>
                        <tr>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="Marketplace">Marketplace</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority="3">Shop</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="2">Non Official</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Exposure</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">PC</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">MCC</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">KA</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">GC</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">FC</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">Others</th>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cat as $c):
                            //echo '<pre>';print_r($c);die;
                            $total = $c->channel->non_official_store + $c->channel->adm_exposure + $c->pc + $c->mcc + $c->ka + $c->fc + $c->gc + $c->others;
                            ?>
                            <tr>
                                <td><?=ucwords($c->channel->marketplace)?></td>
                                <td><?=$c->channel->name?></td>
                                <td><?=$c->channel->non_official_store?></td>
                                <td><?=$c->channel->adm_exposure?></td>
                                <td><?=$c->pc?></td>
                                <td><?=$c->mcc?></td>
                                <td><?=$c->ka?></td>
                                <td><?=$c->gc?></td>
                                <td><?=$c->fc?></td>
                                <td>
                                    <div class="col-xs-2">
                                        <input type="number" oninput="updateTotal(<?=$c->channel_id?>)" name="others" class="form-control others_<?=$c->channel_id?>" value="<?=$c->others?>"/>
                                        <input type="hidden" value="<?=$c->others?>" class="previous_number_<?=$c->channel_id?>">
                                    </div>
                                </td>
                                <td class="total_<?=$c->channel_id?>"><?=$total?></td>

                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>