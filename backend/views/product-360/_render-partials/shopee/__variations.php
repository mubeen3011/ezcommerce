<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 7/11/2019
 * Time: 1:59 PM
 */

if (isset($fields['p360']['variations'])) :
    $variations = isset($fields['p360']['variations']) ? $fields['p360']['variations'] : [];
elseif ( isset($fields['shopee_variations'] ) ):
    $variations = isset($fields['shopee_variations']) ? $fields['shopee_variations'] : [];
//    $variations = [];
//    foreach ( $ShopeeVariations as $key=>$value ) :
//        $variations[$key]['type']['Color'] = $value['name'];
//        $variations[$key]['price'] = $value['price'];
//        $variations[$key]['rccp'] = $value['price']-1;
//        $variations[$key]['stock'] = $value['stock'];
//        $variations[$key]['sku'] = $value['variation_sku'];
//    endforeach;
endif;
// for shopee variation

?>

<?php if ( empty($variations) ) : ?>
    <div class="row">
        <div class="variation-section">

        </div>
        <span class="btn enable-variation">
            <i class="fa fa-plus-circle"></i>
            Enable Variations
        </span>
    </div>
<?php elseif ( $variations ): ?>

    <div class="row">
        <div class="variation-section">
            <?=Yii::$app->controller->renderPartial('/product-360/_render-partials/shopee/sales-information',['variations'=>$variations,'Shop_Id'=>$Shop_Id])?>
        </div>
        <span class="btn enable-variation" style="display: none;">
            <i class="fa fa-plus-circle"></i>
            Enable Variations
        </span>
    </div>

<?php endif; ?>
