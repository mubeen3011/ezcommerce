<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/21/2018
 * Time: 4:50 PM
 */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Reports', 'url' => ['/reports/skus-crawl-report']];
$this->params['breadcrumbs'][] = 'Stock Sync Report';
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3>Lazada Stock Sync Report</h3>
            </div>
        </div>
    </div>
    <!--Negative margin skus-->
    <div class="col-12">
        <div class="row">

            <div class="col-lg-12 col-md-12 col-xlg-12 col-xs-12">
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <h1>Lazada</h1>
            </div>
            <?php
            foreach ( $Shops_Stock_Sync_Status['Lazada'] as $key=>$value ){
                ?>
                <div class="col-lg-4 col-md-6 col-xlg-2 col-xs-12">
                    <div class="ribbon-wrapper card">
                        <div class="ribbon ribbon-warning"><?=$value['Shop_Name']?></div>
                        <p>Successfully Updated : <b><?=$value['Successfully_Updated']?></b></p>
                        <p>Failed Updated : <b><?=$value['Failed_Updated']?></b></p>
                        <p>Last Updated : <b><?=$value['Last_Updated']?></b></p>
                        <?php
                        if ( $value['Failed_Updated'] > 0 )
                        {
                            ?>
                            <a href="#" onclick="ShowFailedList('<?=$value['Shop_Name']?>')" data-target=".bs-example-modal-lg">Click Here To View SKU'S</a>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

</div>
<?=Yii::$app->controller->renderPartial('Modals/failed-stock-sync');?>
