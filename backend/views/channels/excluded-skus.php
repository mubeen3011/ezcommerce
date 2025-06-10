<?php
/**
 * Created by PhpStorm.
 * User: Abdullah
 * Date: 8/17/2018
 * Time: 11:52 AM
 */
?>
<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
/*echo '<pre>';
print_r($skus_list);
die;*/
?>
<style>
    .select2-container{
        width:100%;
    }
    .select2-dropdown{
        width: 200px !important;
    }
    .select2-selection--single{
        width: 200px;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['user/generic']];
        $this->params['breadcrumbs'][] = 'Stop Price/Stock sync on Shops';
        ?>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?='Stop Price/Stock sync on Shops'?></h3>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

                <?=$gridview?>
            </div>
        </div>
    </div>
    <div id="responsive-modal" class="modal fade excluded-skus-modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Modal Content is Responsive</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>

                </div>
                <div class="modal-body">
                    <form id="form-data">
                        <input type="hidden" id="shop_id" name="shop_id" value="">
                        <div class="row">
                            <div class="col-md-12">
                                <h2>Price</h2>
                                <div class="form-group">
                                    <label for="recipient-name" class="control-label custom-checkbox"></label>
                                    <div class="m-b-10">
                                        <label class="custom-control custom-checkbox">
                                            <input type="checkbox" name="price_unsync" class="custom-control-input price-unsync">
                                            <span class="custom-control-label">Unsync</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message-text" class="control-label">Skus List:</label>
                                    <div class="tags-default">
                                        <input type="text" id="price_skus_list" name="price_skus_list" value="" data-role="tagsinput" placeholder="" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message-text" class="control-label">Add Sku:</label>
                                    <select class="select2 select2-form-control form-control price-skus-choose">
                                        <option></option>
                                        <?php
                                        if ( isset($skus_list['skus']) ){
                                            foreach ( $skus_list['skus'] as $key=>$value ){
                                                ?>
                                                <option value="<?=$value?>"><?=$value?></option>
                                                <?php
                                            }
                                        }

                                        ?>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="col-md-12">
                                <h2>Stock</h2>
                                <div class="form-group">
                                    <label for="recipient-name" class="control-label custom-checkbox"></label>
                                    <div class="m-b-10">
                                        <label class="custom-control custom-checkbox">
                                            <input type="checkbox" name="stock_unsync" class="custom-control-input stocks-unsync">
                                            <span class="custom-control-label">Unsync</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message-text" class="control-label">Skus List:</label>
                                    <div class="tags-default">
                                        <input type="text" id="stock_skus_list" name="stock_skus_list" value="" data-role="tagsinput" placeholder="" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message-text" class="control-label">Add Sku:</label>
                                    <select class="select2 select2-form-control form-control stocks-skus-choose">
                                        <option></option>
                                        <?php
                                        if ( isset($skus_list['skus']) ){
                                            foreach ( $skus_list['skus'] as $key=>$value ){
                                                ?>
                                                <option value="<?=$value?>"><?=$value?></option>
                                                <?php
                                            }
                                        }

                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="message-text" class="control-label">Reason:</label>
                                    <textarea name="reason" id="reason" class="form-control reason">Write some reason here.</textarea>
                                </div>
                            </div>
                        </div>



                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info waves-effect waves-light" id="update-skus">Save changes</button>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.full.js', [\yii\web\View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJs("$(\".select2\").select2();");