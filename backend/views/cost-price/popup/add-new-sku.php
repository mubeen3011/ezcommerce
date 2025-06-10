<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/18/2018
 * Time: 11:14 AM
 */
use yii\helpers\ArrayHelper;
$data = ArrayHelper::map(\common\models\Products::find()->where(['<>', 'sub_category', '167'])->andWhere(['is_active' => '1'])->orderBy('sku asc')->asArray()->all(), 'id', 'sku');
?>
<div id="newskuModal" class="modal fade bs-example-modal-lg" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Manage Sku's</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">


                <div class="col-md-12">
                    <div class="vtabs">
                        <ul class="nav nav-tabs tabs-vertical" role="tablist">
                            <li class="nav-item"> <a id="linkAddSku" class="nav-link active" data-toggle="tab" href="#AddSku" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Add Sku</span> </a> </li>
                            <li class="nav-item"> <a id="linkUpdateSku" class="nav-link" data-toggle="tab" href="#UpdateSku"  role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Update Sku</span> </a> </li>
                            <li class="nav-item hide"> <a class="nav-link" data-toggle="tab" href="#import_sku" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Import</span></a> </li>
                            <li class="nav-item hide"> <a class="nav-link" data-toggle="tab" href="#mapping_sku" role="tab"><span class="hidden-sm-up"><i class="ti-email"></i></span> <span class="hidden-xs-down">Parent/Child Sku Mapping</span></a> </li>
                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane active" id="AddSku" role="tabpanel">
                                <div class="p-20">
                                    <div class="row">
                                        <form action="#" id="addSkuForm">

                                            <div class="form-body">
                                                <div class="row p-t-20">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Select Product Type*</label>
                                                            <select class="form-control" id="product_type" name="product_type">
                                                                <option value="ORDERABLE">Orderable Product</option>
                                                                <option value="FOC">FOC</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Sku Model ( like : SCF639/00 )*</label>
                                                            <input type="text" id="sku_model" name="sku_model" class="form-control" placeholder="">
                                                        </div>
                                                    </div>

                                                </div>
                                                <!--/row-->
                                                <div class="row">
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">12NC*</label>
                                                            <input type="text"  id="n_c" name="n_c" class="form-control form-control-danger" placeholder="">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Brief Product Description*</label>
                                                            <input type="text" id="product_description" name="product_description" class="form-control form-control-danger" placeholder="">
                                                        </div>
                                                    </div>

                                                </div>
                                                <!--/row-->
                                                <div class="row">
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Cost Price*</label>
                                                            <input type="number" id="cost_price" name="cost_price" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Extra Cost</label>
                                                            <input type="number" id="extra_cost" name="extra_cost" value="0.00" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                    <!--/span-->

                                                    <!--/span-->
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">RCP*</label>
                                                            <input type="number" id="rcp" name="rcp" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Promo Price</label>
                                                            <input type="number" min="0.00" value="" id="promo_price" name="promo_price" class="form-control" placeholder=""  >
                                                        </div>
                                                    </div>

                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label hide">Margin*</label>
                                                            <input type="hidden" min="0.00" step="0.01" value="5.00" id="margin" name="margin" class="form-control" placeholder=""  >
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6" id="sub-category-div-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Sub category*</label>
                                                            <select class="select2 select2-form-control " id="sub_category" name="sub_category">
                                                                <?php
                                                                foreach ( $Categories as $key=>$value ){
                                                                    ?>
                                                                    <option value="<?=$value['id']?>"><?=$value['name']?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label hide">Subsidy*</label>
                                                            <input type="hidden" min="0.00" step="0.01" value="0.00"  id="subsidy" name="subsidy"  class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group" id="sku_add_status" style="text-align: center;">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>
                                                <!--/row-->
                                                <hr>
                                                <!--/row-->
                                            </div>



                                            <div class="form-actions" style="float: right;">
                                                <button type="submit" class="btn btn-success" id="save_sku"> <i class="fa fa-check"></i> Save</button>
                                                <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane p-20" id="import_sku" role="tabpanel">
                                <form action="/cost-price/new-update-sku-import" method="POST" enctype="multipart/form-data">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="input-file-now">Upload CSV</label>
                                                    <input type="file" name="csv" required id="input-file-now" />
                                                    <input type="hidden" name="_csrf-backend" value="<?=Yii::$app->request->csrfToken?>">
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="input-file-now"></label>
                                                    <a href="/sample-files/sku_import_sample.csv">Download Sample CSV</a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-actions" style="float: right;">
                                        <button type="submit" class="btn btn-success" id="save_sku"> <i class="fa fa-check"></i> Upload</button>
                                        <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane p-20" id="mapping_sku" role="tabpanel">


                                <div class="form-body">
                                    <div class="row p-t-20">
                                        <form action="#" id="sku-mapping">
                                            <div class="col-md-6">
                                                <table>
                                                    <tbody>
                                                    <tr>
                                                        <td>
                                                            <input style="width: 150px;" type="text" id="sku_model" name="sku_model" class="form-control" placeholder="Search Sku">
                                                        </td>
                                                        <td>
                                                            <button type="submit" class="btn btn-success" id="mapping_sku_search"> <i class="fa fa-search"></i></button>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>

                                            </div>
                                        </form>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group" id="mapping_status" style="text-align: center;">
                                            </div>
                                        </div>
                                        <!--/span-->
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <form id="sku_mapping_form">
                                                <table class="table" id="mapping_sku_child_parent">

                                                </table>
                                                <div class="form-actions" style="float: right;">
                                                    <button type="submit" class="btn btn-success" id="mapping_sku_update"> Update</button>
                                                    <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Cancel</button>
                                                </div>
                                            </form>
                                            <div class="form-group" id="mapping_update_status" style="text-align: center;">
                                            </div>
                                        </div>
                                    </div>
                                    <!--/row-->
                                    <hr>
                                    <!--/row-->
                                </div>

                            </div>
                            <div class="tab-pane p-20" id="UpdateSku" role="tabpanel">
                                <div class="p-20">
                                    <div class="row">
                                        <form action="#" id="updateSkuForm">

                                            <div class="form-body">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <label class="control-label">Select Sku*</label>
                                                    <select name="dropdownSkus" class="select2 select2-multiple multi-select-sku"
                                                            placeholder="Select a SKU ..."
                                                            id="dropdownSkus">
                                                        <?php
                                                        foreach ($data as $key => $value) {
                                                            ?>
                                                            <option value="<?= $value ?>"><?= $value ?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    </div>
                                                </div>
                                                <div class="" id="divFormElemets" style="display: none;">
                                                <div class="row p-t-20" >
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Select Product Type*</label>
                                                            <select class="form-control" id="update_product_type" name="update_product_type">
                                                                <option value="ORDERABLE">Orderable Product</option>
                                                                <option value="FOC">FOC</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">TNC*</label>
                                                            <input type="text"  id="update_tnc" name="update_tnc" class="form-control form-control-danger" placeholder="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--/row-->
                                                <div class="row">
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Cost Price*</label>
                                                            <input type="number" id="update_cost_price" name="update_cost_price" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">RCP*</label>
                                                            <input type="number" id="update_rcp" name="update_rcp" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Extra Cost</label>
                                                            <input type="number" id="update_extra_cost" name="update_extra_cost" value="0.00" class="form-control" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Promo Price</label>
                                                            <input type="number" min="0.00" value="" id="update_promo_price" name="update_promo_price" class="form-control" placeholder=""  >
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group" id="sku_update_status" style="text-align: center;">
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>
                                                <!--/row-->
                                                </div>
                                                <hr>
                                                <!--/row-->
                                            </div>



                                            <div class="form-actions" style="float: right;">
                                                <button type="submit" class="btn btn-success" id="update_sku"> <i class="fa fa-check"></i> Save</button>
                                                <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="sku-add-status" style="font-size: 30px;"></p>

            </div>
            <div class="modal-footer">
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
