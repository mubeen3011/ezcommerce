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
if ($insertDate != date('Y-m-d'))
    $disable = 'disabled="disabled"';


$this->title = 'Add Competitive Pricing - ' . $insertDate;
$this->params['breadcrumbs'][] = ['label' => 'Competitive Pricings', 'url' => ['index']];
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
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
            height: 400px;
            overflow-y: auto;
        }
        #w0,#w2,#w1{
            width:150px;
        }
        .numc{
            width:150px;
        }
        pre{
            display:none;
        }
    </style>
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
                       href="<?= \yii\helpers\Url::to(['crawl-sku-details', 'sku' => ($sl['sku'])]) ?>"><?= $sl['sku'] ?></a>
                    <br />

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
                <td class="tg-kr94" style="width: 60px;">
                    <?php
                    $kw = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['keywords'] : '';
                    ?>
                    <input <?= $disable ?> type="text" name="kw_1" value="<?= $kw ?>"
                                           class="form-control">
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
                <td class="tg-kr94" style="width:115px !important;">
                    <?php
                    $lowPrice = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['low_price'] : '';
                    ?>
                    <input <?= $disable ?> type="text" name="low_price_1" value="<?= $lowPrice ?>"
                                           class="form-control numc">
                </td>
                <td><?php
                    if( isset($sku_crawl_list[$sl['sku']][1]['seller_name']) ){
                        echo $sku_crawl_list[$sl['sku']][1]['seller_name'];
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if( isset($sku_crawl_list[$sl['sku']][1]['price']) ){
                        echo $sku_crawl_list[$sl['sku']][1]['price'];
                    }
                    ?>
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
                    <input <?= $disable ?> type="text" name="low_price_2" value="<?= $lowPrice ?>"
                                           class="form-control numc"></td>
                <td>-</td>
                <td>-</td>
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
                <td class="tg-kr94" style="width:115px !important;">
                    <?php
                    $lowPrice = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $archiveList[$sl['id']]['3']['low_price'] : '';
                    ?>
                    <input <?= $disable ?> type="text" name="low_price_3" value="<?= $lowPrice ?>"
                                           class="form-control numc">
                </td>
                <td><?php
                    if( isset($sku_crawl_list[$sl['sku']][3]['seller_name']) ){
                        echo $sku_crawl_list[$sl['sku']][3]['seller_name'];
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if( isset($sku_crawl_list[$sl['sku']][3]['price']) ){
                        echo $sku_crawl_list[$sl['sku']][3]['price'];
                    }
                    ?>
                </td>
                <td><a href="/crawl/run?sku_id=<?=$sl['sku']?>"  >Force fetch</a></td>
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
                <!--<td class="tg-kr94">
                    <input <?/*= $disable */?> type="button" class="cp-save" name="save" value="save"
                                           class="btn btn-info btn-xs">
                </td>-->
            </form>
        </tr>
        <?php $i++; endforeach; ?>
    </tbody>