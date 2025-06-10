<?php
$this->params['breadcrumbs'][] = ['label' => 'PO List', 'url' => ['po']];
$this->params['breadcrumbs'][] = 'Create Purchase Order';
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3>Create Purchase Order</h3>
                <center>
                <div class="col-md-4">
                    <form class="form p-t-20" action="po-detail">
                        <div class="form-group">
                            <label for="exampleInputuname">Warehouse</label>
                            <div class="input-group">

                                <select class="form-control" name="warehouseId">
                                    <?php
                                    foreach ( $warehouses as $wh_detail ){
                                        ?>
                                        <option value="<?=$wh_detail->id?>"><?=$wh_detail->name?></option>
                                        <?php
                                    }
                                    ?>

                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success waves-effect waves-light m-r-10">Submit</button>
                    </form>
                </div>
                </center>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJsFile(
    '@web/ao-js/create-po.js?v='.time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);