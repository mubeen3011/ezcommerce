<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 4/23/2018
 * Time: 2:47 PM
 */

use kartik\daterange\DateRangePicker;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$this->title = "";
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Sales Pricing Export';
$show = "all";
$channelList = \backend\util\HelpUtil::getChannels();

$this->registerCssFile("@web/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.css", [
    'depends' => [\yii\bootstrap\BootstrapAsset::className()],
    ]);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?= 'Sales Pricing Export' ?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>



                <div class="competitive-pricing-form">

                    <form id="p-export" action="/pricing/sales-export" method="post">
                        <input name="_csrf-backend"
                               value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
                               type="hidden">
                        <div class="form-group field-sku_id required has-success">
                            <label class="control-label" for="sku_id">Archive:</label>
                            <div class="input-group">
                                <input type="text" name="date" class="date_filterx form-control mydatepicker" placeholder="<?=$date?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="icon-calender"></i></span>
                                </div>
                            </div>

                            <div class="help-block"></div>
                        </div>

                        <div class="form-group Columns required has-success">
                            <?php foreach ($channelList as $cl):
                                ?>
                                <a href="<?= \yii\helpers\Url::to(['/pricing/export','chid'=>$cl['id'],'date'=>strtotime($date),'show'=>$show]) ?>">
                                    <i class="mdi mdi-download"></i> <?= $cl['name'] ?> Export <br/>
                                </a>

                            <?php endforeach; ?>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>
</div>

<?php

$this->registerJs("
$('.mydatepicker').datepicker(\"destroy\");
var today = '".date('Y-m-d')."';
jQuery('.date_filterx').on('change', function () {
        $('#p-export').submit();
    });
    jQuery('.mydatepicker, #datepicker').datepicker(
        {'format':'yyyy-mm-dd','maxDate': today}
    );
    ");