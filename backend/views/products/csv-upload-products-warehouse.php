<?php
use yii\web\View;
use yii\helpers\Html;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product', 'url' => ['/products/duplicate-assigned-warehouse-products']];
$this->params['breadcrumbs'][] = 'Product Details';
?>
    <!---css file----->
    <link href="/../css/sales_v1.css" rel="stylesheet">

    <div class="row">
        <div class="col-lg-12">
            <div class="card" >
                <div class="card-body" >
                    <div class="panel panel-default">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                        <h5>Products Sync to warehouse</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span class="listing-page">
    <!--------status options----->
        <?= Yii::$app->controller->renderPartial('products-warehouse-sync-statuses-view', $_params_); ?>

        <div class="card">
 <div class="card-body">

     <!---------filter------------>
     <?php

     ?>
     <div class="row">
            <div class="col-12">
               <div class="tab-pane  p-20" id="product_sync_status" role="tabpanel">
                <?php if (isset($status)): ?>
                    <div class="alert alert-<?= $status=='success' ? 'success':'danger'?> alert-rounded">
                        <?= $msg;?>
                        <br/>
                        <?= $status=="success" ? $inserted_count." Sku's uploaded":"";?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif;?>
                <h3>Assign Warehouse to products (sync with third party warehouse)</h3>
                <div class="row">
                    <div class="col-md-4">
                        <p>CSV FILE *</p>
                        <div class="competitive-pricing-form">

                            <?php
                            //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                            <form action="/products/csv-upload-product-warehouse-sync"  method="post" enctype='multipart/form-data'>
                                <div class="form-group">
                                    <input type="file" name="csv" class="dropify" required >
                                </div>
                                <div class="form-group">
                                    <?= Html::submitButton('Upload', ['class' => 'btn btn-success btn_submit_style']) ?>
                                </div>

                            </form>
                            <?php //ActiveForm::end(); ?>


                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="error_list_style" style="max-height:150px;height:150px;overflow-y: scroll">
                            <?php if(isset($not_inserted_list) && $not_inserted_list):?>
                                <h3 style="color:red">Following Skus not inserted</h3>
                                <?php
                                echo "<pre>";
                                print_r($not_inserted_list);?>
                            <?php endif;?>

                        </div>
                    </div>

                </div>
                   <!----sample--->
                <div class="col-md-12">
                    <b>sample</b>
                    <div class="competitive-pricing-form" style="background:#F6F6F6">

                        <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                            <thead>
                            <th>sku</th>
                            <th>warehouse_id</th>
                            </thead>
                            <tbody>
                            <tr>
                                <td>SKU123</td>
                                <td>11</td>
                            </tr>
                            <tr>
                                <td>SKU124</td>
                                <td>22</td>
                            </tr>
                            </tbody>
                        </table>


                    </div>
                </div>
                   <!----sample--->

            </div>
            </div>
     </div>

     <!--------listing------------>


</div>
</div>

</span>





