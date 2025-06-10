<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/28/2018
 * Time: 3:21 PM
 */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales Details', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Finance Validation';
?>
<style type="text/css">
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

    input.filter {
        text-align: center;
        font-size: 12px !important;
        font-weight: normal !important;
        color: #007fff;

    }

    /*.tg-kr94 > select {
        width:93px;
    }
    .tg-kr94 > input {
        width:80px;
    }*/



</style>
<div class="row">
    <div class="col-12">
        <div class="col-md-3">


        </div>
    </div>
    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Finance Validation</h3>
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

                <?=$gridview?>
            </div>
        </div>
    </div>

</div>
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Import</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <?php
                $form = \yii\widgets\ActiveForm::begin(['action' =>['/sales/upload-batch'] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Upload Orders Batch</label>
                            <input type="file" name="csv" class="csv-file">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <?= \yii\helpers\Html::submitButton('Import', ['class' => 'btn btn-primary btn-fc-import']) ?>
                        </div>
                    </div>
                </div>
                <?php \yii\widgets\ActiveForm::end(); ?>
            </div>
            <div class="modal-footer">

            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>