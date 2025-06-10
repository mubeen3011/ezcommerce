
<div class="row">
    <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>"/>
    <input type="hidden" name="uqid" value="<?= $this->params['uniqId'];?>">
    <input type="hidden" name="pid" id="pid" value="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>">
    <?php if(isset($_GET['shop'])) { ?>
        <input name="status_pk_id" type="hidden" value="<?= $_GET['shop']?>">
    <?php } ?>
    <?php
    $amazon_checked=0;
    $i = 1;

    foreach ($shops as $sh):
        $checked = (isset($fields['p360']['shop']) && in_array($sh['prefix'], $fields['p360']['shop'])) ? 'checked' : '';
    ?>




        <div class="col-md-2">

            <div class="custom-control custom-checkbox">
                <input  <?= ($isDisable && $checked) ? 'onclick="return false;"' : '' ?> type="checkbox"
                                                             data-channel-id = "<?=$sh['id']?>"
                                                              data-marketplace="<?=$sh['marketplace']?>"
                                                             class="custom-control-input chk-<?=$sh['marketplace']?> shop-checkbox"
                                                             id="customCheck<?= $i ?>" <?= $checked ?>
                                                             name="p360[shop][]"
                                                             value="<?= $sh['prefix'] ?>">
                <label class="custom-control-label" for="customCheck<?= $i ?>"><?= $sh['name'] ?></label>
            </div>
            <input type="hidden" id="amzon_att_checked_flag" value="<?= ($sh['prefix']=='USBAMZ-USB' && $checked) ? "1":"0" ?>">
        </div>
        <?php $i++; endforeach; ?>

</div>
<br/>
<!--- categories---->
<div class="row">

    <div class="form-group col-md-4">
        <label>System Category * </label>
        <select class="select2 form-control" name="p360[sys_category]">
            <?php
            foreach ($categories as $key => $value) {
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
<!----------------->
<div class="row">
    <div class="form-group col-md-4">
        <label>Product Name *</label>
        <input type="text" class="form-control form-control-line" required
               name="p360[common_attributes][product_name]"
               value="<?= (isset($fields['p360']['common_attributes']['product_name'])) ? $fields['p360']['common_attributes']['product_name'] : '' ?>">
    </div>
    <div class="form-group col-md-4">
        <label>Product SKU *</label>
        <input <?= ($isDisable) ? 'disabled' : '' ?> type="text" id="product_sku" class="form-control form-control-line" required
                                                     name="p360[common_attributes][product_sku]"
                                                     value="<?= (isset($fields['p360']['common_attributes']['product_sku'])) ? $fields['p360']['common_attributes']['product_sku'] : '' ?>">
        <?php if($isUpdate): ?>
            <input type="hidden" name="p360[common_attributes][product_sku]" value="<?= (isset($fields['p360']['common_attributes']['product_sku'])) ? $fields['p360']['common_attributes']['product_sku'] : '' ?>">
        <?php endif;?>
    </div>

    <div class="form-group col-md-4">
        <label>EAN * </label>
        <input type="text" class="form-control form-control-line" required
               name="p360[common_attributes][ean]"
               value="<?= (isset($fields['p360']['common_attributes']['ean'])) ? $fields['p360']['common_attributes']['ean'] : '' ?>"
               placeholder="13 digit valid">
    </div>
</div>


<div class="form-group">
    <label>Description</label>
    <textarea id="editor1_remove" class="form-control editor_remove" rows="3" name="p360[common_attributes][product_short_description]"> <?= (isset($fields['p360']['common_attributes']['product_short_description'])) ? $fields['p360']['common_attributes']['product_short_description'] : '' ?></textarea>
</div>

<!----------------------------->

<!----------------------------->
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>RCCP</label>
            <small class='text-info' data-toggle="tooltip" title="RCCP must be greater then 5 and less then 2500"><i class="fa fa-info-circle"></i></small>
            <input type="number" class="form-control form-control-line" id="productPrice" step="0.01"
                   name="p360[common_attributes][product_price]"
                   value="<?= (isset($fields['p360']['common_attributes']['product_price'])) ? $fields['p360']['common_attributes']['product_price'] : '' ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Cost Price * </label>
            <small class='text-info' data-toggle="tooltip" title="Price must be greater then 5 and less then 2500"><i class="fa fa-info-circle"></i></small>
            <input type="number" step="0.01" class="form-control form-control-line" id="costPrice" required
                   name="p360[common_attributes][product_cprice]"
                   value="<?= (isset($fields['p360']['common_attributes']['product_cprice'])) ? $fields['p360']['common_attributes']['product_cprice'] : '' ?>">

        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Stock Quantity *</label>
            <small class='text-info' data-toggle="tooltip" title="Stock Quantity must be greater then or equal to 1"><i class="fa fa-info-circle"></i></small>
            <input type="number" class="form-control form-control-line" required
                   name="p360[common_attributes][product_qty]"
                   value="<?= (isset($fields['p360']['common_attributes']['product_qty'])) ? $fields['p360']['common_attributes']['product_qty'] : '' ?>">
        </div>
    </div>

</div>

<hr>
<h5 class="text-info">Dimensions</h5>
<br>
<!-----------Dimensions----------->
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Weight (LB)</label>
            <small class='text-info' data-toggle="tooltip" title="Must be greater then or equal to 1"><i class="fa fa-info-circle"></i></small>
            <input type="text" class="form-control form-control-line"
                   name="p360[common_attributes][package_weight]"
                   value="<?= (isset($fields['p360']['common_attributes']['package_weight'])) ? $fields['p360']['common_attributes']['package_weight'] : '' ?>">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Length (cm)</label>
            <small class='text-info' data-toggle="tooltip" title="Must be greater then or equal to 1"><i class="fa fa-info-circle"></i></small>
            <input type="text" class="form-control form-control-line"
                   name="p360[common_attributes][package_length]"
                   value="<?= (isset($fields['p360']['common_attributes']['package_length'])) ? $fields['p360']['common_attributes']['package_length'] : '' ?>">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Width(cm)</label>
            <small class='text-info' data-toggle="tooltip" title="Must be greater then or equal to 1"><i class="fa fa-info-circle"></i></small>
            <input type="text" class="form-control form-control-line"
                   name="p360[common_attributes][package_width]"
                   value="<?= (isset($fields['p360']['common_attributes']['package_width'])) ? $fields['p360']['common_attributes']['package_width'] : '' ?>">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Height(cm)</label>
            <small class='text-info' data-toggle="tooltip" title=" Must be greater then or equal to 1"><i class="fa fa-info-circle"></i></small>
            <input type="text" class="form-control form-control-line"
                   name="p360[common_attributes][package_height]"
                   value="<?= (isset($fields['p360']['common_attributes']['package_height'])) ? $fields['p360']['common_attributes']['package_height'] : '' ?>">
        </div>
    </div>
</div>

