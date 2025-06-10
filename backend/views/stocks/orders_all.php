<?php

use common\models\Settings;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Purchase Orders';
//$numbers = \backend\util\HelpUtil::getCurrentStockValue();
$numbers = [];
$html = "";
foreach ($numbers as $k => $v) {
    if ($k == 'total')
        continue;

    $html .= "<table style='width: 100%'><tr><td>{$k}:</td><td style='float: right'>RM {$v}</td></tr></table>";
}

?>
<style type="text/css">
    .thead-border {
        /*border: 1px solid black !important;*/
    }

    pre {
        display: none;
    }

    a.sort {
        color: #0b93d5;
        text-decoration: underline;
    }

    .blockPage {
        border: 0px !important;
        background-color: transparent !important;
    }

    input.filter {
        text-align: center;
        font-size: 12px !important;
        font-weight: normal !important;
        color: #007fff;

    }

    .tg-kr94 > select {
        width: 93px;
    }

    .tg-kr94 > input {
        width: 93px;
    }

    /*thead*/

    /*tbody*/
</style>
<div class="row">
    <div class="col-md-12 ">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <h3 class="page-heading-generic-grid margin-special">Purchase Orders</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="alert alert-warning" style="margin: 0px;">
                            Grand Total : <span
                                    style="color: green;font-weight: bold"><?= Yii::$app->params['currency']?> <?= number_format($total) ?></span>

                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">×</span></button>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if($total == 0){
                    ?>
                    <div class="alert alert-warning" style="margin: 0px;">
                       <span
                            style="color: red;font-weight: bold">No Record Found</span>

                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                    </div>
                <?php } ?>
                <?= $gridview ?>

            </div>
        </div>
    </div>