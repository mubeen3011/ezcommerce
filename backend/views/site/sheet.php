<?php


use common\models\Sellers;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\typeahead\Typeahead;
use yii\jui\AutoComplete;


/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */
$include = [1, 2, 3];
$channelList = \backend\util\HelpUtil::getChannels($include);

$disable = '';
$insertDate = Yii::$app->request->post('insert_date');
$insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
$disable = 'disabled="disabled"';


$this->title = 'Competitive Pricing - ' . $insertDate;
$this->params['breadcrumbs'][] = $this->title;

$refineBits = ['0' => 'No', '1' => 'Yes'];

$sellers = Sellers::find()->orderBy('id')->asArray()->all();
$sellersList = ArrayHelper::map($sellers, 'seller_name', 'seller_name');
$sl = [];
$data = Sellers::find()
    ->select(['seller_name as value', 'seller_name as  label', 'seller_name as id'])
    ->asArray()
    ->all();


?>
<style>
    #table_length,.dt-buttons{
        display:none;
    }
</style>
<form id="filter" action="/site/index" method="post">
    <input name="_csrf-backend"
           value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
           type="hidden">
    <div class="form-group field-sku_id required has-success">
        <label class="control-label" for="sku_id">Archive:</label>
        <?= DateRangePicker::widget([
            'name' => 'insert_date',
            'value' => $insertDate,
            'convertFormat' => true,
            'options' => ['class' => 'date_filter form-control', 'onkeydown' => 'return false'],
            'pluginOptions' => [
                'autoclose' => true,
                'singleDatePicker' => true,
                'showDropdowns' => false,
                'todayBtn' => true,
                'locale' => ['format' => 'Y-m-d'],
                'maxDate' => date('Y-m-d'),
            ]
        ]);
        ?>
        <div class="help-block"></div>
    </div>

</form>

<table class="table fixed table-striped table-bordered dt-responsive" id="table">
    <thead>
    <tr>
        <th class="tg-kr94" colspan="3">SKUs</th>
        <th class="tg-kr94 pname">Name</th>
        <?php foreach ($channelList as $cl): ?>
            <th class="tg-kr94" colspan="2"><?= $cl['name'] ?></th>
        <?php endforeach; ?>
        <th class="tg-kr94 pchange" colspan="3">Price Change</th>

    </tr>
    <tr>
        <th class="tg-kr94">SKU</th>
        <th class="tg-kr94">Category</th>
        <th class="tg-kr94">Selling Status</th>
        <th class="tg-kr94 pname">Name</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <?php foreach ($channelList as $cl): ?>
            <th class="tg-kr94 pchange"><?= $cl['name'] ?></th>
        <?php endforeach; ?>

    </tr>
    </thead>
    <tfoot>
    <tr>
        <th class="tg-kr94">SKU</th>
        <th class="tg-kr94">Category</th>
        <th class="tg-kr94">Selling Status</th>
        <th class="tg-kr94 pname">Name</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <th class="tg-kr94" style="width: 120px !important;">Seller</th>
        <th class="tg-kr94">Low Price</th>
        <?php foreach ($channelList as $cl): ?>
            <th class="tg-kr94 pchange"><?= $cl['name'] ?></th>
        <?php endforeach; ?>
    </tr>
    </tfoot>
    <tbody>
    <?php $i = 1;
    $csrfToken=Yii::$app->request->csrfToken;
    foreach ($skuList as $sl): ?>
        <tr>
            <form id="sku_<?= $sl['id'] ?>">
                <input type="hidden" name="insert_date" value="<?= $insertDate ?>">
                <input id="form-token" type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                       value="<?= $csrfToken ?>"/>
                <td class="tg-kr94" data-sku-id="<?= $sl['id'] ?>">

                    <input type="hidden" name="sku_id" value="<?= $sl['id'] ?>">
                    <a   style="color:blue"
                       href="<?= \yii\helpers\Url::to(['/competitive-pricing/sku-details', 'sku' => ($sl['sku'])]) ?>"><?= $sl['sku'] ?></a>
                </td>
                <td class="tg-kr94">
                    <?= $sl['subCategory']['name']; ?>
                </td>
                <td class="tg-kr94">
                    <?= $sl['selling_status']; ?>
                </td>
                <td class="tg-kr94 pname">
                    <?= $sl['name']; ?>
                </td>


                <td class="tg-kr94">
                    <?php
                    $sellerName = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['seller_name'] : '';
                    $sellerName = ucwords($sellerName);

                    if ($disable) {
                        echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                    } else {
                        echo AutoComplete::widget([
                            'name' => 'seller_1',
                            'value' => $sellerName,
                            'clientOptions' => [
                                'source' => $data,
                            ],
                            'options' => [
                                'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                            ],
                        ]);
                    }

                    ?>
                </td>
                <td class="tg-kr94">
                    <?php
                    $lowPrice = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['low_price'] : '';
                    ?>
                    <input <?= $disable ?> type="number" name="low_price_1" value="<?= $lowPrice ?>"
                                           class="form-control numc">
                </td>

                <td class="tg-kr94">
                    <?php
                    $sellerName = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['2'])) ? $archiveList[$sl['id']]['2']['seller_name'] : '';
                    $sellerName = ucwords($sellerName);
                    if ($disable) {
                        echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                    } else {
                        echo AutoComplete::widget([
                            'name' => 'seller_2',
                            'value' => $sellerName,
                            'clientOptions' => [
                                'source' => $data,
                            ],
                            'options' => [
                                'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                            ],
                        ]);
                    }

                    ?>
                </td>
                <td class="tg-kr94">
                    <?php
                    $lowPrice = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['2'])) ? $archiveList[$sl['id']]['2']['low_price'] : '';
                    ?>
                    <input <?= $disable ?> type="number" name="low_price_2" value="<?= $lowPrice ?>"
                                           class="form-control numc"></td>

                <td class="tg-kr94">
                    <?php
                    $sellerName = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $archiveList[$sl['id']]['3']['seller_name'] : '';
                    $sellerName = ucwords($sellerName);
                    if ($disable) {
                        echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                    } else {
                        echo AutoComplete::widget([
                            'name' => 'seller_3',
                            'value' => $sellerName,
                            'clientOptions' => [
                                'source' => $data,
                            ],
                            'options' => [
                                'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                            ],
                        ]);
                    }
                    ?>
                </td>
                <td class="tg-kr94">
                    <?php
                    $lowPrice = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $archiveList[$sl['id']]['3']['low_price'] : '';
                    ?>
                    <input <?= $disable ?> type="number" name="low_price_3" value="<?= $lowPrice ?>"
                                           class="form-control numc">
                </td>
                <td class="tg-kr94 pchange ch1_txt">
                    <?php
                    echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $refineBits[$archiveList[$sl['id']]['1']['change_price']] : '';
                    ?>
                </td>
                <td class="tg-kr94 pchange ch2_txt">
                    <?php
                    echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['2'])) ? $refineBits[$archiveList[$sl['id']]['2']['change_price']] : '';
                    ?>
                </td>
                <td class="tg-kr94 pchange ch3_txt">
                    <?php
                    echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $refineBits[$archiveList[$sl['id']]['3']['change_price']] : '';
                    ?>
                </td>

            </form>
        </tr>
        <?php $i++; endforeach; ?>
    </tbody>
</table>
