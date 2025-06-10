<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/17/2018
 * Time: 4:39 PM
 */
?>

<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator ', 'url' => ['/user/generic']];
        $this->params['breadcrumbs'][] = 'Bundle List';
        ?>
</div>
<div class="col-12">
    <div class="card">
        <div class="card-body">
            <div class=" row">
                <div class="col-md-4 col-sm-12">
                    <h3><?='Bundle List'?></h3>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#newbundleModal" data-whatever="@fat">Add New Bundle</button>
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
<div id="showBundlePopup" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Bundle Detail</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="bundleChild_popup">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>
<?=$this->render('popup/add-new-bundle', ['foc_skus'=>$foc_skus,'skus_without_foc'=>$skus_without_foc]);?>
<?php
$this->registerJsFile(
    '@web/ao-js/bundles.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);