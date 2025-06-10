<?php
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['/user/generic']];
$this->params['breadcrumbs'][] = 'Price Cross Check';
?>
<style>
    .select2-container{
        width: 100% !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <div id="displayBox" style="display: none;">
            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
        </div>
    </div>
    <div class="col-12">
        <div class="card">

            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='Price Cross Check'?></h3>
                        <?php if (Yii::$app->session->hasFlash('success')): ?>
                            <div class="alert alert-success alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                <h4><i class="icon fa fa-check"></i>Saved!</h4>
                                <?= Yii::$app->session->getFlash('success') ?>
                            </div>
                        <?php endif; ?>
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
<!-- Add new skus modal -->
<!-- Modal -->
