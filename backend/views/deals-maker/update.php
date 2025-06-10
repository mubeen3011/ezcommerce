<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\DealsMaker */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Deals Makers', 'url' => ['dashboard']];
$this->params['breadcrumbs'][] = 'Update';

?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Update Deal Request</h3>
                </div>
            </div>
            <div id="displayBox" style="display: none;">
                <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <a href="javascript:void(0);" id="duplicate_deal_link" class="pull-right" data-toggle="modal" data-target="#responsive-modal">Duplicate Deal</a>
                    </div>
                </div>
                <div class="row">
                    <div class="deals-maker-update col-md-12" style="padding: 0px;">
                        <?= $this->render($view, [
                            'model' => $model, 'multiSkus' => $multiSkus/*, 'deal_margin' => $deal_margin*/,'channelSku'=>$channelSku,
                            'additional_sku' => $additional_sku
                        ]) ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
    <!-- sample modal content -->
    <div id="responsive-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Duplicate Deal</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <form id="form_duplicate_deal">
                        <div class="form-group">
                            <label for="recipient-name" class="control-label">New Deal Name:*</label>
                            <input type="text" class="form-control" id="duplicate_deal_name" maxlength="50">
                            <input type="hidden" id="old_deal_id" value="<?=$model->id?>">
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="message-text" class="control-label">Start Date:*</label>
                                <input type="text" id="duplicate_deal_start_date" maxlength="30" class="start_datetime form-control form-control-line" name="" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="message-text" class="control-label">End Date:*</label>
                                <input type="text" id="duplicate_deal_end_date" maxlength="30" class="end_datetime form-control form-control-line" name="" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">Close</button>
                    <button type="button" id="btn_duplicate_deal" class="btn btn-danger waves-effect waves-light">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /.modal -->
<?php
$this->registerJs("
$('#demo-foo-row-toggler').footable();
");

