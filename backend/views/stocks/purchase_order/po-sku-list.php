<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/16/2019
 * Time: 2:01 PM
 */

/*
 * Here is how we are dealing skus with variations
 *
 * */
?>
<?php
if ( isset($PoSkuList['variations']) && !empty($PoSkuList['variations']) ){
    foreach ( $PoSkuList['variations']  as $type=>$detail  ){

        ?>
        <?=Yii::$app->controller->renderPartial('purchase_order/po-sku-detail', ['po' => $po,'SkuDetail'=>$detail['parent'],
            'status'=>$status,'warehouse'=>$warehouse,'variation'=>0])?>
        <?php
        foreach ( $detail['child'] as $child_details ){
            ?>
            <?=Yii::$app->controller->renderPartial('purchase_order/po-sku-detail', ['po' => $po,'SkuDetail'=>$child_details,
                'status'=>$status,'warehouse'=>$warehouse,'variation'=>1])?>
            <?php
        }
        ?>

        <?php
    }
}


/*
 * Here is how we are dealing skus without variations
 *
 * */
if ( isset($PoSkuList['single']) && !empty($PoSkuList['single']) ){
    foreach ( $PoSkuList['single'] as $skuDetail ){
        ?>
        <?=Yii::$app->controller->renderPartial('purchase_order/po-sku-detail', ['po' => $po,'SkuDetail'=>$skuDetail,
            'status'=>$status,'warehouse'=>$warehouse,'variation'=>0])?>
    <?php
    }
}


/*
 * Here is how we are dealing with bundles
 *
 * */
if ( isset($PoSkuList['bundles']) && !empty($PoSkuList['bundles']) ) {

    foreach ($PoSkuList['bundles'] as $bundleDetail) {
        ?>
        <?= Yii::$app->controller->renderPartial('purchase_order/po-bundle-detail', ['po' => $po, 'SkuDetail' => $bundleDetail,
            'status' => $status, 'warehouse' => $warehouse]) ?>
        <?php
    }
}
?>