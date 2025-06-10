<?php

use common\models\Settings;

//$this->title = 'Products Stocks ';
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Stock List';
$settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();

?>
    <style type="text/css">
        .filters-visible{
            display: none;
        }
        .thead-border{
            /*border: 1px solid black !important;*/
        }
        pre{
            display: none;
        }
        a.sort {
            color: #0b93d5;
            text-decoration: underline;
        }
        .blockPage{
            border:0px !important;
            background-color: transparent !important;
        }



        /*.tg-kr94 > select {
            width:93px;
        }
        .tg-kr94 > input {
            width:80px;
        }*/


    </style>
    <style type="text/css">
        pre{
            display: none;
        }
        .blockPage{
            border:0px !important;
            background-color: transparent !important;
        }
        .scroll {
            border: 0;
            border-collapse: collapse;
        }

        .scroll tr {
            display: flex;
        }

        .scroll td {
            padding: 3px;
            flex: 1 auto;
            border: 0px solid #aaa;
            width: 1px;
            word-wrap: break;
        }

        .scroll thead tr:after {
            content: '';
            overflow-y: scroll;
            visibility: hidden;
            height: 0;
        }

        .scroll thead th {
            flex: 1 auto;
            display: block;
            border: 0px solid #000;
        }

        .scroll tbody {
            display: block;
            width: 100%;
            overflow-y: auto;
            height: 400px;
        }


        input.filter {
            text-align: center;
            font-size: 12px !important;
            font-weight: normal !important;
            color: #007fff;

        }
        .remove-margin-generic-grid{
            margin-bottom: 0px !important;
        }

    </style>
    <div class="row">
        <div id="displayBox" style="display: none;">
            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class=" row">
                        <div class="col-md-4 col-sm-12">
                            <h3 class="page-heading-generic-grid margin-special">Stock List </h3>
                        </div>
                        <div class="col-md-4 col-sm-12">

                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>

                    <!--------------------------------->
                    <?php
                    $session = Yii::$app->session;
                    if($session->hasFlash('error')): ?>
                        <hr>
                        <div class="alert alert-danger pull-left">
                            <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <strong><?= $session->getFlash('error');?></strong>
                        </div>


                    <?php endif; ?>
                    <?php if($session->hasFlash('success')): ?>
                    <hr>
                    <div class="alert alert-success pull-left">
                        <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><?= $session->getFlash('success');?></strong>
                    </div>
                    <?php endif; ?>
                    <!--------------------------------->

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <?=$gridview?>
                </div>
            </div>
        </div>

    </div>
