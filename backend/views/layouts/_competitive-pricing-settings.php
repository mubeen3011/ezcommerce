<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 11/9/2017
 * Time: 3:08 PM
 */

use kartik\daterange\DateRangePicker;
use yii\helpers\Html;

$insertDate = Yii::$app->request->post('insert_date');
$insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
?>
<div class="">
    <form id="filter" action="/competitive-pricing/create" method="post">
        <input name="_csrf-backend"
               value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
               type="hidden">
        <div class="form-group field-sku_id required has-success">
            <label class="control-label" for="sku_id">Archive:</label>
            <?= DateRangePicker::widget([
                'name' => 'insert_date',
                'value' => $insertDate,
                'convertFormat' => true,
                'options' => ['class' => 'date_filter form-control', 'onkeydown' => 'return false'],
                'pluginOptions' => [
                    'autoclose' => true,
                    'singleDatePicker' => true,
                    'showDropdowns' => false,
                    'todayBtn' => true,
                    'locale' => ['format' => 'Y-m-d'],
                    'maxDate' => date('Y-m-d'),
                ]
            ]);
            ?>
            <div class="help-block"></div>
        </div>
        <div class="form-group Columns required has-success">
            <label class="control-label" for="sku_id">Columns:</label>
            <br/>
                <label for="default" style="margin: 2px" class="col-md-8 btn btn-primary btn-small">Product Name<input
                            type="checkbox" id="default" name="pname"
                            class="badgebox" data-class="pname" value="1"><span class="badge">&check;</span></label>
                <label for="default2" style="margin: 2px" class="col-md-8 btn btn-primary btn-small">Price Change<input
                            type="checkbox" id="default2" name="pname"
                            class="badgebox" data-class="pchange" value="1"><span class="badge">&check;</span></label>
            <hr>
                <?= Html::a('Import Competitive Pricing', ['import'], ['class' => 'btn btn-info col-md-8']) ?>


            <div class="help-block"></div>
        </div>
    </form>
</div>
