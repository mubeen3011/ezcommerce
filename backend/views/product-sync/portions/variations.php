<?php $variation_theme=isset($fields['p360']['variationtheme']) ?  isset($fields['p360']['variationtheme']):"";?>
<div class="row m-3">
    <div class="col-3">
    <select name="p360[variationtheme]" class="form-control form-control-sm select-variation-theme" style="background: #F2F7F8">
        <option value="">Select Variation Theme/Combination</option>
        <option <?= $variation_theme=="Color" ? "selected":""; ?> value="Color" data-option1="Color" input="text" input-fields-qty="1">Color</option>
        <option <?= $variation_theme=="SizeColor" ? "selected":""; ?> value="SizeColor" data-option1="Size" data-option2="Color" data-input-type1="text" data-input-type2="text" input-fields-qty="2">SizeColor</option>
        <option <?= $variation_theme=="ColorSize" ? "selected":""; ?> value="ColorSize" data-option1="Color" data-option2="Size" data-input-type1="text" data-input-type2="text"  input-fields-qty="2">ColorSize</option>
    </select>
    </div>
</div>
<div id="variation-population-span">

      <?php if(isset($fields['p360']['variations'])) {
          $splitCamelArray = preg_split('/(?=[A-Z])/', $fields['p360']['variationtheme']);
          $variation_combination=array_filter($splitCamelArray);
          $v_count=0;
          foreach($fields['p360']['variations'] as $variation) { ?>
    <div class="variation-basic-template" style="border:2px dotted #F2F7F8; box-shadow:2px 2px 3px gray">
        <div class="row m-3" id="selected_variation_span" >
            <div class="col-3">
                Variation selected
            </div>
            <?php foreach($variation_combination as $comb) { ?>
            <div class="col-3">
                <input placeholder="<?= $comb;?>" type="text" name="p360[variations][<?= $v_count;?>][<?= $comb;?>]" value="<?= isset($variation[$comb]) ? $variation[$comb]:"";?>" class="form-control form-control-sm"/>
            </div>
           <?php } ?>
            <div class="col-3"></div>
        </div>

        <div class="row m-3">
            <div class="col-3">
                Common
            </div>

            <div class="col-3">
                <input placeholder="Price" step="0.01" type="number" value="<?= $variation['price'];?>" name="p360[variations][<?= $v_count;?>][price]"  class="form-control form-control-sm"/>
            </div>
            <div class="col-3">
                <input type="number" placeholder="Stock" value="<?= $variation['stock'];?>" name="p360[variations][<?= $v_count;?>][stock]"  class="form-control form-control-sm" />
            </div>

        </div>

     <div class="row m-3">
            <div class="col-3">
                IDS
            </div>
            <div class="col-3">
                <input type="text" value="<?= $variation['sku'];?>" name="p360[variations][<?= $v_count;?>][sku]" placeholder="SKU"  class="form-control form-control-sm" />
            </div>
            <div class="col-3">
                <input type="text" value="<?= $variation['product-id'];?>" name="p360[variations][<?= $v_count;?>][product-id]" placeholder="EAN/UPC/GTIN/ASIN/ISBN"  class="form-control form-control-sm" />
            </div>
            <div class="col-3">
                <select name="p360[variations][<?= $v_count;?>][product-id-type]" class="form-control form-control-sm" >
                    <!--<option value="">product id type</option>-->
                    <option value="EAN" <?= $variation['product-id-type']=="EAN" ? "selected":"";?>>EAN</option>
                   <!-- <option value="UPC">UPC</option>
                    <option value="GTIN">GTIN</option>
                    <option value="ASIN">ASIN</option>
                    <option value="ISBN">ISBN</option>-->
                </select>

            </div>

        </div>

      <div class="row m-3">
            <div class="col-3">
                Images
            </div>
            <div class="col-9">
                <div  id="image-<?= $v_count;?>-10-7" class="dropzone variation-dropzone" >

                 <?php
                    foreach ($variation['images'] as $img) {
                    if($img!=""){
                    echo '<div class="col-md-2">
                        <img  class="img-thumbnail img-responsive" src="/product_images/' . $img . '" width="40%" style="height: 82%;" alt="">
                        <small><a href="javascript:;" style="cursor: pointer" class="delete" data-img-id="' . $img . '">Delete</a></small>
                    </div>';
                    }
                    } ?>
                </div>
            </div>

        </div>

        <div>
            <a data-toggle="tooltip" class="delete-variation-btn" title="Delete variation"><span class="fa fa-trash fa-2x" style="color:orange"></span> </a>
        </div>
    </div>




<?php $v_count ++ ; } ?>
          <div class="row m-2">
              <div class="col-4 offset-lg-4">
                  <a href="javascript:" class="add-new-variation btn btn-sm btn-success"><span class="fa fa-plus"> Add New Variation</span></a>
              </div>
          </div>
<?php    } ?>
</div>