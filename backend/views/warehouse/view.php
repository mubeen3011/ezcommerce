<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\warehouses */
//echo '<pre>';print_r($model);die;
?>
<style>
    #myTable_wrapper{
        margin-top:35px;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['user/generic']];
        $this->params['breadcrumbs'][] = 'Warehouse Settings';
        ?>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?='Warehouse Settings'?></h3>
                <div class="warehouses-create">
                    <ul class="nav nav-tabs customtab2" role="tablist">
                        <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#home" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Basic Info</span></a> </li>
                        <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#profile" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Assign Areas</span></a> </li>
                        <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#messages" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-email"></i></span> <span class="hidden-xs-down">Stock badges</span></a> </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active show" id="home" role="tabpanel">
                            <div class="row" style="margin-top: 35px">
                                <div class="col-6">
                                    <div class="form-group field-warehouses-name has-success">
                                        <label class="control-label" for="warehouses-name">Name</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value=" <?=$model->name?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Channels</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$WarehouseChannels?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Is Active</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=($model->is_active==0) ? 'inactive' : 'active'?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Warehouse Platform</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->warehouse?>">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Threshold 1 (no of days)</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->t1?>">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Threshold 2 (no of days)</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->t2?>">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Transit Days</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->transit_days?>">
                                    </div>
                                </div>

                                <div class="col-3">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">City</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->city?>">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">State</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->state?>">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Country</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->country?>">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Zipcode</label>
                                        <input type="text" id="warehouses-name" class="form-control" readonly value="<?=$model->zipcode?>">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group field-warehouses-channels">
                                        <label class="control-label" for="warehouses-name">Address</label>
                                        <textarea id="warehouses-name" class="form-control" readonly><?=$model->full_address?></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="tab-pane show" id="profile" role="tabpanel">
                            <table id="myTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Country</th>
                                        <th>State</th>
                                        <th>City</th>
                                        <th>ZipCode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ( $AssignedAreas as $AreaDetail ){
                                    ?>
                                    <tr>
                                        <td><?=$AreaDetail['country']?></td>
                                        <td><?=$AreaDetail['state_name']?></td>
                                        <td><?=$AreaDetail['city_name']?></td>
                                        <td><?=$AreaDetail['zipcode']?></td>
                                    </tr>
                                <?php
                                }
                                ?>

                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane show" id="messages" role="tabpanel">
                            <?php
                            if ( $model->settings!='' ){
                                $settings = json_decode($model->settings,1);
                            }else{
                                $settings = [];
                            }
                            ?>
                            <div class="row" style="margin-top: 35px">
                                <div class="col-md-4 m-b-30">
                                    <div class="example">
                                        <h5 class="box-title">Out Of Stock</h5>
                                        <input type="text" disabled class="complex-colorpicker form-control" value="<?=(isset($settings['StockListBadges']['DaysListBadges']['out_of_sock'])) ? $settings['StockListBadges']['DaysListBadges']['out_of_sock'] : '#7ab2fa'?>" /> </div>
                                    <div class="input-group" style="width:56%;margin-top: 20px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Days < </span>
                                        </div>
                                        <input type="number" disabled value="<?=(isset($settings['StockListBadges']['DaysLevelRanges']['out_of_sock'])) ? $settings['StockListBadges']['DaysLevelRanges']['out_of_sock'] : '0'?>" class="form-control" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4 m-b-30">
                                    <div class="example">
                                        <h5 class="box-title">Going to OOS</h5>
                                        <input type="text" disabled class="complex-colorpicker form-control" value="<?=(isset($settings['StockListBadges']['DaysListBadges']['out_of_stock_soon'])) ? $settings['StockListBadges']['DaysListBadges']['out_of_stock_soon'] : '#fa7a7a'?>" />
                                    </div>
                                    <div class="input-group" style="width:75%;margin-top: 20px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Days> </span>
                                        </div>
                                        <input type="number" disabled value="<?=(isset($settings['StockListBadges']['DaysLevelRanges']['out_of_stock_soon_greater'])) ?$settings['StockListBadges']['DaysLevelRanges']['out_of_stock_soon_greater'] : '0'?>" class="form-control" min="0">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Days < </span>
                                        </div>
                                        <input type="number" disabled value="<?=(isset($settings['StockListBadges']['DaysLevelRanges']['out_of_sock_soon_less'])) ? $settings['StockListBadges']['DaysLevelRanges']['out_of_sock_soon_less'] : '30'?>" class="form-control" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4 m-b-30">
                                    <div class="example">
                                        <h5 class="box-title">In Stock</h5>
                                        <input type="text" disabled class="complex-colorpicker form-control" value="<?=(isset($settings['StockListBadges']['DaysListBadges']['in_stock'])) ? $settings['StockListBadges']['DaysListBadges']['in_stock'] : '#bee0ab'?>" />
                                    </div>
                                    <div class="input-group" style="width:56%;margin-top: 20px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Days > </span>
                                        </div>
                                        <input type="number" disabled class="form-control" value="<?=(isset($settings['StockListBadges']['DaysLevelRanges']['in_stock'])) ? $settings['StockListBadges']['DaysLevelRanges']['in_stock'] : '30'?>" min="0">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <h1><?= Html::encode($this->title) ?></h1>



                </div>

            </div>
        </div>
    </div>

</div>
<?=$this->registerJs("
$('#myTable').DataTable();
$(\".complex-colorpicker\").asColorPicker({
    mode: 'complex'
});
")?>