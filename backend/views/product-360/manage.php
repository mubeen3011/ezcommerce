<?php
/* @var $this yii\web\View */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product 360', 'url' => ['manage']];
$this->params['breadcrumbs'][] = 'Manage';

use common\models\Category;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

$Categories = Category::find()->where(['is_active' => 1])->andWhere(['parent_id' => NULL])->asArray()->all();
if (isset($fields['uqid'])) {
    $uniqId = $fields['uqid'];
} else {
    $uniqId = uniqid();
}
$lzdCatselected = (isset($fields['p360']['lzd_category'])) ? $fields['p360']['lzd_category'] : '';
$usboxing = (isset($fields['p360']['presta_category'])) ? $fields['p360']['presta_category'] : '';
$ShopeCatselected = (isset($fields['p360']['shope_category'])) ? $fields['p360']['shope_category'] : '';
$StreetCatselected = (isset($fields['p360']['street_category'])) ? $fields['p360']['street_category'] : '';

$productList = ArrayHelper::map(\common\models\Products360Fields::find()->all(), 'id', 'sku');
if (isset($_GET['shop']))
    $shopid = \backend\util\HelpUtil::exchange_values('id','shop_id',$_GET['shop'],'product_360_status');
else
    $shopid = '';
?>
    <style>
        .images {
            display: block;
        }

        .img {
            display: inline-block;
            max-width: 30%;
            margin: 0 2.5%;
        }
    </style>
    <div class="row">

        <div class="col-12">
            <div class="card">

                <div class="card-body">

                    <div class=" row">
                        <div class="col-md-4 col-sm-12">
                            <h3>Product Management</h3>
                            <h4>Create new Product or Update?</h4>
                        </div>
                        <div class="col-md-4 col-sm-12">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>
                    <!-- content goes here -->
                    <hr>
                    <div class="col-md-12 new-shops-selection">
                        <h3>Images <small> * Minimum Height/Width should be greater than 330X330</small></h3> 
                        <form id="pimages" action="/product-360/upload" class="floating-labels m-t-40 dropzone"
                              enctype="multipart/form-data" method="post">
                            <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>"
                                   value="<?= Yii::$app->request->csrfToken; ?>"/>
                            <input type="hidden" name="uqid" value="<?= $uniqId ?>">
                            <input type="hidden" name="sid" id="sid"
                                   value="<?= isset($_GET['shop']) ? $_GET['shop'] : '' ?>">
                            <!-- preload images -->
                            <?php
                            if ($images) {
                                echo "<h4>Exists Images</h4>";
                                echo '<div class="row">';
                                foreach ($images as $img) {
                                    if($img!=""){
                                    echo '<div class="col-md-2">
                                            <img  class="img-thumbnail img-responsive" src="/product_images/' . $img . '" width="40%" style="height: 50%;" alt="">
                                            <small><a href="javascript:;" style="cursor: pointer" class="delete" data-img-id="' . $img . '">Delete</a></small>
                                        </div>';
                                    }
                                }
                                echo "</div>";
                            }

                            ?>

                        </form>
                        <form id="pinfo" class="form-material mt-5" enctype="multipart/form-data" method="post">
                            <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>"
                                   value="<?= Yii::$app->request->csrfToken; ?>"/>
                            <input type="hidden" name="uqid" value="<?= $uniqId ?>">
                            <?php if(isset($fields['p360']['shopee_item_id'])):?>
                             <input type="hidden" name="p360[shopee_item_id]" value="<?=trim($fields['p360']['shopee_item_id'])?>">
                            <?php endif; ?>
                            <?php if(isset($fields['p360']['shopee_variation_id'])):?>
                             <input type="hidden" name="p360[shopee_variation_id]" value="<?=trim($fields['p360']['shopee_variation_id'])?>">
                            <?php endif; ?>
                            <input type="hidden" name="pid" id="pid"
                                   value="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>">
                            <h3>Details</h3>
                            <h6>For which Shops are you creating?</h6>
                             <div class="row">
                            <?php
                            $i = 1;

                            foreach ($shops as $sh):
                                $checked = (isset($fields['p360']['shop']) && in_array($sh['prefix'], $fields['p360']['shop'])) ? 'checked' : '';
                                ?>
                               

                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input <?= ($isDisable) ? 'disabled' : '' ?> type="checkbox"
                                                                                         data-channel-id = "<?=$sh['id']?>"
                                                                                         class="custom-control-input chk-<?=$sh['marketplace']?>"
                                                                                         id="customCheck<?= $i ?>" <?= $checked ?>
                                                                                         name="p360[shop][]"
                                                                                         value="<?= $sh['prefix'] ?>">
                                            <label class="custom-control-label"
                                                   for="customCheck<?= $i ?>"><?= $sh['name'] ?></label>
                                        </div>
                                    </div>
                                <?php $i++; endforeach; ?>
                                </div>
                                <?php
                                /*echo '<pre>';
                                print_r($fields);
                                die;*/
                            if ($isUpdate && $fields['p360'] != null && array_key_exists("shop",$fields['p360'])) {
                                foreach ($fields['p360']['shop'] as $s)
                                    echo "<input type='hidden' name='p360[shop][]' value='" . $s . "'>";
                            }
                            ?>
                            <div class="clearfix"><br></div>
                            <div class="row">
                                <div class="form-group lzd-cat hide col-md-6">
                                    <label>Lazada Category</label>
                                    <select id="lzd_category" class="select2 form-control" name="p360[lzd_category]">
                                        <option value="">Select Category</option>
                                        <?php
                                        \backend\util\Product360Util::getLzdCategory(1, $lzdCatselected); ?>
                                    </select>
                                </div>
                                <div class="form-group usbox-presta-cat hide col-md-6">
                                    <label>Prestashop Category</label>
                                    <?php /*echo $usboxing;die; */?>
                                    <select id="presta_category" data-channel-id="16" class="select2 form-control" name="p360[presta_category]">
                                        <option value="">Select Category</option>
                                        <?php
                                        \backend\util\Product360Util::GetCategory(16, $usboxing); ?>
                                    </select>
                                </div>
                                <div class="form-group shopee-cat hide col-md-6">
                                    <label>Shopee Category</label>
                                    <select id="shope_category" class="select2 form-control" name="p360[shope_category]">
                                        <option value="">Select Category</option>
                                        <?php
                                        \backend\util\Product360Util::getShopeCategory(2, $ShopeCatselected); ?>
                                    </select>
                                </div>
                            </div>
                             <div class="row">
                            <div class="form-group street-cat hide col-md-6">
                                <label>11 Street Category</label>
                                <select id="street_category" class="select2 form-control" name="p360[street_category]">
                                    <option value="">Select Category</option>
                                    <?php
                                   // \backend\util\Product360Util::getStreetCategory(3, $StreetCatselected); ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>System Category</label>
                                <select class="select2 form-control" name="p360[sys_category]">
                                    <?php
                                    foreach ($Categories as $key => $value) {
                                        $selected = (isset($fields['p360']['sys_category']) && $fields['p360']['sys_category'] == $value['id']) ? 'selected' : '';
                                        ?>
                                        <option <?= $selected ?>
                                                value="<?= $value['id'] ?>"><?= $value['name'] ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-4">
                                <label>Product Name</label>
                                <input type="text" class="form-control form-control-line"
                                       name="p360[common_attributes][product_name]"
                                       value="<?= (isset($fields['p360']['common_attributes']['product_name'])) ? $fields['p360']['common_attributes']['product_name'] : '' ?>">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Product SKU</label>
                                <input <?= ($isDisable) ? 'disabled' : '' ?> type="text" id="product_sku" class="form-control form-control-line"
                                       name="p360[common_attributes][product_sku]"
                                       value="<?= (isset($fields['p360']['common_attributes']['product_sku'])) ? $fields['p360']['common_attributes']['product_sku'] : '' ?>">
                                <?php if($isUpdate): ?>
                                    <input type="hidden" name="p360[common_attributes][product_sku]" value="<?= (isset($fields['p360']['common_attributes']['product_sku'])) ? $fields['p360']['common_attributes']['product_sku'] : '' ?>">
                                <?php endif;?>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Color</label>
                                <input type="text" class="form-control form-control-line"
                                       name="p360[common_attributes][product_color]"
                                       value="<?= (isset($fields['p360']['common_attributes']['product_color'])) ? $fields['p360']['common_attributes']['product_color'] : '' ?>"
                                       placeholder="ex: Green">
                            </div>
                            </div>
                           

                            <div class="form-group">
                                <label>Description</label>
                                <textarea id="editor1" class="form-control editor" rows="3"
                                    name="p360[common_attributes][product_short_description]">
                                    <?= (isset($fields['p360']['common_attributes']['product_short_description'])) ? $fields['p360']['common_attributes']['product_short_description'] : '' ?>
                                </textarea>
                            </div>
                            <!-- <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Special From Date</label>
                                        <input type="text" id="dealsmaker-start_date" class="start_datetime form-control form-control-line" name="p360[common_attributes][special_from_date]" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="<?= (isset($fields['p360']['common_attributes']['special_from_date'])) ? $fields['p360']['common_attributes']['special_from_date'] : '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Special To Date</label>
                                        <input type="text" id="dealsmaker-end_date" class="end_datetime form-control form-control-line" name="p360[common_attributes][special_to_date]" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="<?= (isset($fields['p360']['common_attributes']['special_to_date'])) ? $fields['p360']['common_attributes']['special_to_date'] : '' ?>">
                                    </div>
                                </div>
                            </div> -->
                            <div class="row">
                            <div class="col-md-4">
                            <div class="form-group">
                                <label>RCCP</label>
                                <small>RCCP must be greater then 5 and less then 2500</small>
                                <input type="text" class="form-control form-control-line" id="productPrice"
                                       name="p360[common_attributes][product_price]"
                                       value="<?= (isset($fields['p360']['common_attributes']['product_price'])) ? $fields['p360']['common_attributes']['product_price'] : '' ?>">
                            </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cost Price</label>
                                    <small> Price must be greater then 5 and less then 2500</small>
                                    <input type="text" class="form-control form-control-line" id="costPrice"
                                           name="p360[common_attributes][product_cprice]"
                                           value="<?= (isset($fields['p360']['common_attributes']['product_cprice'])) ? $fields['p360']['common_attributes']['product_cprice'] : '' ?>">

                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <small>Stock Quantity must be greater then or equal to 1</small>
                                <input type="text" class="form-control form-control-line"
                                       name="p360[common_attributes][product_qty]"
                                       value="<?= (isset($fields['p360']['common_attributes']['product_qty'])) ? $fields['p360']['common_attributes']['product_qty'] : '' ?>">
                            </div>
                            </div>

                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Weight (kg)</label>
                                        <small> Must be greater then or equal to 1</small>
                                        <input type="text" class="form-control form-control-line"
                                               name="p360[common_attributes][package_weight]"
                                               value="<?= (isset($fields['p360']['common_attributes']['package_weight'])) ? $fields['p360']['common_attributes']['package_weight'] : '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Length (cm)</label>
                                        <small> Must be greater then or equal to 1</small>
                                        <input type="text" class="form-control form-control-line"
                                               name="p360[common_attributes][package_length]"
                                               value="<?= (isset($fields['p360']['common_attributes']['package_length'])) ? $fields['p360']['common_attributes']['package_length'] : '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Width(cm)</label>
                                        <small> Must be greater then or equal to 1</small>
                                        <input type="text" class="form-control form-control-line"
                                               name="p360[common_attributes][package_width]"
                                               value="<?= (isset($fields['p360']['common_attributes']['package_width'])) ? $fields['p360']['common_attributes']['package_width'] : '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Height(cm)</label>
                                        <small> Must be greater then or equal to 1</small>
                                        <input type="text" class="form-control form-control-line"
                                               name="p360[common_attributes][package_height]"
                                               value="<?= (isset($fields['p360']['common_attributes']['package_height'])) ? $fields['p360']['common_attributes']['package_height'] : '' ?>">
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <div class="variation-section-lazada">

                            </div>
                            <div class="row">
                                <!-- column -->
                                <div class="col-lg-6 col-md-6 lzd-cat hide">
                                    <!-- Card -->
                                    <div class="card">
                                        <img class="card-img-top img-responsive" style="width: 250px;text-align: center" src="/images/lazada-logo.jpg" alt="Lazada Specific Attributes">
                                        <br />
                                        <div class="form-group lzd-cat hide">
                                            <label>Brand</label>
                                            <select name="p360[common_attributes][brand]" class="form-control form-control-line select2">
                                                <option value="">Select Brand</option>
                                                <option <?= (isset($fields['p360']['common_attributes']['brand']) && $fields['p360']['common_attributes']['brand'] == 'Philips') ? 'selected' : ''; ?>
                                                        value="Philips">Philips
                                                </option>
                                                <option <?= (isset($fields['p360']['common_attributes']['brand']) && $fields['p360']['common_attributes']['brand'] == 'Philips AVENT') ? 'selected' : ''; ?>
                                                        value="Philips AVENT">Philips AVENT
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group lzd-cat hide">
                                            <label>Warranty</label>
                                            <small> * Lazada specific</small>
                                            <select class="form-control form-control-line select2"
                                                    name="p360[lzd_attributes][normal][warranty_type]">
                                                <option value="">Select Warranty</option>
                                                <option <?= (isset($fields['p360']['lzd_attributes']['normal']['warranty_type']) && $fields['p360']['lzd_attributes']['normal']['warranty_type'] == 'No Warranty') ? 'selected' : ''; ?>
                                                        value="No Warranty">No Warranty
                                                </option>
                                                <option <?= (isset($fields['p360']['lzd_attributes']['normal']['warranty_type']) && $fields['p360']['lzd_attributes']['normal']['warranty_type'] == 'International Seller Warranty') ? 'selected' : ''; ?>
                                                        value="International Seller Warranty">International Seller Warranty
                                                </option>
                                                <option <?= (isset($fields['p360']['lzd_attributes']['normal']['warranty_type']) && $fields['p360']['lzd_attributes']['normal']['warranty_type'] == 'Local Manufacturer Warranty') ? 'selected' : ''; ?>
                                                        value="Local Manufacturer Warranty">Local Manufacturer Warranty
                                                </option>
                                                <option <?= (isset($fields['p360']['lzd_attributes']['normal']['warranty_type']) && $fields['p360']['lzd_attributes']['normal']['warranty_type'] == 'Local Supplier Warranty') ? 'selected' : ''; ?>
                                                        value="Local Supplier Warranty">Local Supplier Warranty
                                                </option>
                                                <option <?= (isset($fields['p360']['lzd_attributes']['normal']['warranty_type']) && $fields['p360']['lzd_attributes']['normal']['warranty_type'] == 'International Manufacturer Warranty') ? 'selected' : ''; ?>
                                                        value="International Manufacturer Warranty">International Manufacturer
                                                    Warranty
                                                </option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group lzd-cat hide">
                                                    <label>Special From Date</label>
                                                    <input type="text" id="dealsmaker-start_date" class="start_datetime form-control form-control-line" name="p360[common_attributes][special_from_date]" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="<?= (isset($fields['p360']['common_attributes']['special_from_date'])) ? $fields['p360']['common_attributes']['special_from_date'] : '' ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group lzd-cat hide">
                                                    <label>Special To Date</label>
                                                    <input type="text" id="dealsmaker-end_date" class="end_datetime form-control form-control-line" name="p360[common_attributes][special_to_date]" style="background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;" autocomplete="off" aria-required="true" aria-invalid="false" value="<?= (isset($fields['p360']['common_attributes']['special_to_date'])) ? $fields['p360']['common_attributes']['special_to_date'] : '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="lzd-attr">

                                        </div>
                                    </div>
                                    <!-- Card -->
                                </div>
                                <!-- column -->
                                <!-- column -->
                                <div class="col-lg-6 col-md-6 shopee-cat hide">
                                    <!-- Card -->
                                    <div class="card">
                                        <img class="card-img-top img-responsive" style="width: 250px;text-align: center" src="/images/shopee-logo.png" alt="Shopee Specific Attributes">
                                        <br />
                                    <div class="form-group shopee-cat hide">
                                        <label>Logistics</label>
                                        <small> * Shopee specific</small>
                                        <select class="form-control form-control-line select2"
                                                name="p360[shopee_attributes][shpe_logistics]">
                                            <option value="">Select Logistics</option>
                                            <option <?= (isset($fields['p360']['shopee_attributes']['shpe_logistics']) && $fields['p360']['shopee_attributes']['shpe_logistics'] == 20011) ? 'selected' : ''; ?> value="20011">J&T Express</option>
                                            <option <?= (isset($fields['p360']['shopee_attributes']['shpe_logistics']) && $fields['p360']['shopee_attributes']['shpe_logistics'] == 20007) ? 'selected' : ''; ?> value="20007">Poslaju</option>

                                        </select>
                                    </div>
                                    <div class="shopee-attr"></div>
                                    
                                </div>
                                    <!-- Card -->
                                </div>
                                <!--
                                Presta fields
                                -->
                                <?=Yii::$app->controller->renderPartial('_render-partials/presta/fields')?>
                            </div>

                            <?= Yii::$app->controller->renderPartial('_render-partials/shopee/__variations',['fields'=>$fields,
                                'Shop_Id'=>(isset($_GET['shop'])) ? $shopid : '']) ?>

                            <div class="card-body">
                                <?php if ($isUpdate): ?>
                                    <input type="submit" name="save" class="btn btn-success" value="Update">
                                <?php else: ?>
                                    <input type="submit" name="save" class="btn btn-success" value="Publish">
                                    <input type="submit" name="save" class="btn btn-warning" value="Draft">
                                <?php endif; ?>
                            </div>
                        </form>
                        <input id="sku_server_error" type="hidden" value=""/>
                    </div>

                </div>

            </div>

        </div>

    </div>
<div class="hidden-inputs">
    <input id="sku_server_error" type="hidden" value=""/>
</div>
<script>
    var isUpdate = '<?= $isUpdate ?>';
    var status = '<?= $status ?>';
    var shop_id = '<?=$shopid?>';

    if (isUpdate && status=='Success'){
        var shopattr = '<?=(isset($fields['attr'])) ? json_encode($fields['attr']) : ''?>';
    }else {
        var shopattr = '<?=(isset($fields['p360']['shopee_attributes'])) ? json_encode($fields['p360']['shopee_attributes']) : ''?>';
    }
    if (isUpdate && status=='Success'){
        var prestaattr = '<?=(isset($fields['attr'])) ? json_encode($fields['attr']) : ''?>';
    }else {
        var prestaattr = '<?=(isset($fields['p360']['presta_attributes'])) ? json_encode($fields['p360']['presta_attributes']) : ''?>';
    }
</script>
<?php
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/assets/plugins/tinymce/tinymce.min.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '//cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/assets/plugins/dropzone-master/dist/dropzone.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/js/product-360.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/ao-js/product-variations.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);
