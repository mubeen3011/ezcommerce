<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\SkuMarginSettingsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Sku Margin Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<table class="table table-striped table-bordered dt-responsive nowrap" cellspacing="2" cellpadding="2"  id="table">
    <thead>
    <tr>
        <th class="tg-ss">SKU</th>
        <th class="tg-ss">Category</th>
        <th class="tg-ss">Price</th>
        <th class="tg-ss">Type</th>

    </tr>
    </thead>
    <tfoot>
    <tr>
        <th class="tg-kr94">SKU</th>
        <th class="tg-kr94">Category</th>
        <th class="tg-kr94">Price</th>
        <th class="tg-kr94">Type</th>

    </tr>
    </tfoot>
    <tbody>
    <?php $i = 1;
    foreach ($skuList as $sl): ?>
        <tr>
            <form id="sku_<?= $sl['id'] ?>">
                <input id="form-token" type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                       value="<?= Yii::$app->request->csrfToken ?>"/>
                <td class="tg-ss" data-sku-id="<?= $sl['id'] ?>">

                    <input type="hidden" name="sku_id" value="<?= $sl['id'] ?>">
                    <a   style="color:blue"
                       href="<?= \yii\helpers\Url::to(['/competitive-pricing/sku-details', 'sku' => ($sl['sku'])]) ?>"><?= $sl['sku'] ?></a>
                </td>
                <td class="tg-ss">
                    <?= $sl['subCategory']['name']; ?>
                </td>
                <td class="tg-ss">
                    <?php
                    $price = isset($settingList[$sl['id']]) ? $settingList[$sl['id']]['price'] : '';
                    ?>
                    <input  type="number" name="price_<?=$sl['id']?>" value="<?= $price ?>"
                            class="form-control numc num_ss">
                </td>
                <td class="tg-ss">
                    <?php
                    $type = isset($settingList[$sl['id']]) ? $settingList[$sl['id']]['type'] : '';

                    ?>
                    <select class="form-control" name="type_<?=$sl['id']?>">
                        <option value="1" <?= $type == 1 ? 'selected' : ''?> >RM</option>
                        <option value="2" <?= $type == 2 ? 'selected' : ''?>>%</option>
                    </select>
                </td>
            </form>
        </tr>
        <?php $i++; endforeach; ?>
    </tbody>
</table>