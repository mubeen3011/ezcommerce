<?php

use common\models\Channels;
use yii\helpers\ArrayHelper;

$pr = (isset($_GET['Search']['price_range'])) ? explode(',',$_GET['Search']['price_range']) : '';
$status = ['pending'=>'pending','shipped'=>'Shipped','canceled'=>'Canceled'];
$ItemStatus = \common\models\OrderItems::find()->select(['item_status'])->distinct()->asArray()->all();
$ItemStatusList = ArrayHelper::map($ItemStatus, 'item_status', 'item_status');
?>
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <i class="mdi mdi-magnify-minus-outline" style="font-size: 17px;"></i>
                <h4 class="modal-title" id="myModalLabel">Advanced Search</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">

                    <form action="/sales/reporting" method="GET" autocomplete="off">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <input type="hidden" value="<?php (isset($_GET['record_per_page']))? $_GET['record_per_page']: 10 ?>" name="record_per_page">
                                        <label class="control-label">SKU(s):</label>
                                        <input type="hidden" value="skus" name="view">
                                        <select style="width: 100% !important;" name="Search[sku][]" class="select2 m-b-10 select2-multiple"
                                                multiple="multiple">
                                            <?php
                                            foreach ($filterdata as $k => $v):
                                                $selected = (isset($_GET['Search']['sku']) && in_array($v['sku'], $_GET['Search']['sku']))
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v['sku'] ?>"><?= $v['sku'] ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-control-feedback"> Multiple Skus can be selected </small>

                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Date Range:</label>
                                        <input class="form-control input-daterange-datepicker" value="<?=isset($_GET['Search']['created']) ? $_GET['Search']['created'] : '' ?>" type="text" name="Search[created]" />

                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Shop(s):</label>
                                        <select style="width: 100% !important;" name="Search[channel_id][]"
                                                class="select2 m-b-10 select2-multiple" multiple="multiple">
                                            <option></option>
                                            <?php
                                            foreach (Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1'])->asArray()->all() as $k => $v):
                                                $selected = (isset($_GET['Search']['channel_id'])
                                                    && in_array($v['id'], $_GET['Search']['channel_id']))
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v['id'] ?>"><?= $v['name'] ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-control-feedback"> Multiple Channels can be selected </small>
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Order Id(s):</label>
                                        <input type="text" class="form-control" name="Search[order_id]"
                                               value="<?= (isset($_GET['Search']['order_id'])) ? $_GET['Search']['order_id'] : '' ?>"
                                               placeholder="Place comma(,) between order id">
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Coupon Code:</label>
                                        <input type="text" class="form-control" name="Search[coupon_code]"
                                               value="<?= (isset($_GET['Search']['coupon_code'])) ? $_GET['Search']['coupon_code'] : '' ?>"
                                               placeholder="BLIP500">
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Order Status:</label>
                                        <select style="width: 100% !important;" name="Search[status]"
                                                class="select2 m-b-10 form-control">
                                            <option></option>
                                            <?php
                                            foreach ($status as $k => $v):
                                                $selected = (isset($_GET['Search']['status'])
                                                    && $v == $_GET['Search']['status'])
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v ?>"><?= ucwords($v) ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="page" value="1">
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Item Status:</label>
                                        <select style="width: 100% !important;" name="Search[item_status]"
                                                class="select2 m-b-10 form-control">
                                            <option></option>
                                            <?php
                                            foreach ($ItemStatusList as $k => $v):
                                                $selected = (isset($_GET['Search']['item_status'])
                                                    && $v == $_GET['Search']['item_status'])
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v ?>"><?= ucwords($v) ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="page" value="1">
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Show Category(s):
                                            <?php
                                            $showCategory = isset($_GET['Search']['show_category']) ? $_GET['Search']['show_category'] : '0';
                                            $checked = ($showCategory == 0) ? '' : 'checked';
                                            ?>
                                            <input <?=$checked?>  title="Show/Hide Category Column" value="1" style="width:25px;padding-left: 10px;" name="Search[show_category]" type="checkbox">
                                        </label>
                                        <select style="width: 100% !important;" name="Search[category][]"
                                                class="select2 cat m-b-10 select2-multiple" multiple="multiple">
                                            <option></option>
                                            <?php
                                            foreach (\common\models\Category::find()->where(['<>','id','167'])->asArray()->all() as $k => $v):
                                                $selected = (isset($_GET['Search']['category'])
                                                    && in_array($v['id'], $_GET['Search']['category']))
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v['id'] ?>"><?= $v['name'] ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-control-feedback"> Multiple Categories can be selected </small>
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Price Range (USD)

                                        </label>
                                        <?php
                                        if( isset($_GET['Search']['price_range']) && $_GET['Search']['price_range']!='' ){
                                            $range=$_GET['Search']['price_range'];
                                            $explode=explode(',',$_GET['Search']['price_range']);
                                            $from=$explode[0];
                                            $to=$explode[1];
                                        }else{
                                            $range='0,5000';
                                            $from='0';
                                            $to='5000';
                                        }
                                        ?>
                                        <input type="hidden" name="Search[price_range]" value="<?=$range?>" />
                                        <div id="range_22"></div>
                                    </div>
                                </div>
                                <div class=" col-md-6" tabindex="0" aria-controls="example23" rowspan="1" colspan="1">
                                    <div class="form-group">
                                        <label class="control-label">Deals</label>
                                        <select style="width: 100% !important;" name="Search[deal_id][]"
                                                class="select2 m-b-10 select2-multiple" multiple="multiple">
                                            <option></option>
                                            <?php
                                            foreach ($deals as $k => $v):
                                                $selected = (isset($_GET['Search']['deal_id']) && in_array($v['id'], $_GET['Search']['deal_id']))
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v['id'] ?>"><?= 'Deal-'.date('d-M',strtotime($v['start_date'])).' TO '.date('d-M',strtotime($v['end_date'])) ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions pull-right">

                            <button type="submit" class="btn btn-success" id="submit-filters"> <i class="mdi mdi-magnify-minus-outline"></i> Search</button>
                            <button type="button" class="btn btn-inverse" data-dismiss="modal">Cancel</button>

                        </div>

                    </form>
            </div>
            <div class="modal-footer"></div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<?php
$this->registerJs('$(".select2-multiple").select2();
$(\'.input-daterange-datepicker\').daterangepicker({
locale: {
      format: \'YYYY-MM-DD\',
        separator: \' to \',
    },
        autoUpdateInput: false,
        buttonClasses: [\'btn\', \'btn-sm\'],
        
        applyClass: \'btn-danger\',
        cancelClass: \'btn-inverse\',
        ranges: {
           \'Today\': [moment(), moment()],
           \'Yesterday\': [moment().subtract(1, \'days\'), moment().subtract(1, \'days\')],
           \'Last 7 Days\': [moment().subtract(6, \'days\'), moment()],
           \'Last 30 Days\': [moment().subtract(29, \'days\'), moment()],
           \'This Month\': [moment().startOf(\'month\'), moment().endOf(\'month\')],
           \'Last Month\': [moment().subtract(1, \'month\').startOf(\'month\'), moment().subtract(1, \'month\').endOf(\'month\')]
        }
    }, function(start, end, label) {
  $(\'.input-daterange-datepicker\').val(start.format(\'YYYY-MM-DD\') + \' to \' + end.format(\'YYYY-MM-DD\'));
});
    if($(\'input[name="Search[show_category]"]:checked\').length > 0)
    {
        $(".cat").removeAttr(\'disabled\');
    } else {
        $(".cat").attr(\'disabled\',true);
    }

    $(\'input[name="Search[show_category]"]\').click(function() {
        if (this.checked) {
            $(".cat").removeAttr(\'disabled\');
        } else {
            $(".cat").attr(\'disabled\',true);
        }
    });
    $("#range_22").ionRangeSlider({
    type: "double",
    min: 0,
    max: 5000,
    from: '.$from.',
    to: '.$to.',
    step: 10,
    onStart: function (data) {
        //console.log(data);
    },
    onChange : function (data) {
    //console.log(data);
},
onFinish:  function (data) {
    console.log(data);
    $(\'input[name="Search[price_range]"]\').val(data.from +\',\'+ data.to);
},
onUpdate:  function (data) {
    //console.log(data);
},
});
    
');
$this->registerCss(".daterangepicker{
    z-index: 1200 !important;
}

");
