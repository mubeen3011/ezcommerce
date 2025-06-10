<?php

use kartik\daterange\DateRangePicker;
use yii\helpers\Url;

$channelList = \backend\util\HelpUtil::getChannels();

$date = Yii::$app->request->post('date');
$date = ($date == '') ? date('Y-m-d') : $date;
?>
<div class="">
    <a href="<?= Url::to(['pricing/index?show=today']) ?>"
       class="btn vertical-button remove-border bg-azure"
       title="Sales Pricing Sheet">
        Show Today's Pricing Sheet
    </a>
    <form id="filter" action="/pricing/index" method="post">
        <input name="_csrf-backend"
               value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
               type="hidden">
        <div class="form-group field-sku_id required has-success">
            <label class="control-label" for="sku_id">Archive:</label>
            <?= DateRangePicker::widget([
                'name' => 'date',
                'value' => $date,
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
            <?php foreach ($channelList as $cl): if ($cl['id'] == 1) continue; ?>
                <label for="default-<?= $cl['id'] ?>" style="margin: 2px"
                       class="btn btn-primary btn-small col-md-12"><?= $cl['name'] ?><input
                        type="checkbox" id="default-<?= $cl['id'] ?>"
                        name="<?= $cl['name'] ?>"
                        class="badgebox" data-class="chl-<?= $cl['name'] ?>" value="1"><span
                        class="badge">&check;</span></label><br/>
            <?php endforeach; ?>
        </div>
    </form>
    <hr>

    <div class="form-group Columns required has-success">
        <label class="control-label" for="sku_id">Price Sync</label>
        <br/>
        <a   href="<?= \yii\helpers\Url::to(['/api/update-products-price']) ?>">
            <span class="glyph-icon icon-upload"></span> Price Update (LIVE SYNC)
        </a>
    </div>
</div>
