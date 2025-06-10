<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/10/2020
 * Time: 3:24 PM
 */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Distributor Setting'];
$this->params['breadcrumbs'][] = 'Assign Areas Settings';
?>
<div class="row">
    <div class="col-12">
        <div class="card card-body">
            <div class=" row">
                <div class="col-md-4 col-sm-12">
                    <h3>Assign Areas</h3>
                </div>
                <div class="col-md-4 col-sm-12">

                </div>
                <div class="col-md-4 col-sm-12">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </div>
            </div>


            <?=Yii::$app->controller->renderPartial('assign-orders-areas/distributor-warehouses',['warehouses'=>$warehouses]);?>

            <?php if ( isset($_GET['warehouse']) && $_GET['warehouse']!='' ) : ?>

                <?=Yii::$app->controller->renderPartial('assign-orders-areas/zip-codes-list',['allZipCodes'=>$allZipCodes,
                    'pre_selected_zipcodes'=>$pre_selected_zipcodes]);?>

            <?php endif; ?>

        </div>
    </div>
</div>
