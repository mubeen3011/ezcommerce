<?php
use common\models\Channels;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\DealsMaker */
/* @var $form yii\widgets\ActiveForm */
$channel = Channels::find()->where(['is_active' => '1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');
$data = ArrayHelper::map(\common\models\Products::find()->where(['<>','sub_category','167'])->andWhere(['is_active'=>'1'])->orderBy('sku asc')->asArray()->all(), 'id', 'sku');
//$category = \common\models\Category::find()->where(['is_active' => '1'])->andWhere(['not in','map_with',''])->groupBy(['map_with'])->orderBy('map_with')->asArray()->all();
$category = \common\models\Category::find()->where(['is_active' => '1'])->andWhere(['=', 'parent_id', 0])->orWhere(['=', 'parent_id', NULL])->orderBy('name')->asArray()->all();
$categoryList = ArrayHelper::map($category, 'name', 'name');

$reasons = ['Competitor Top' => 'Competitor Top', 'Focus SKUs' => 'Focus SKUs', 'Philips Campaign' => 'Philips Campaign',
    'Flash Sale' => 'Flash Sale', 'Shocking Deal' => 'Shocking Deal', 'Aging Stocks' => 'Aging Stocks', 'EOL' => 'EOL',
    'Competitive Pricing' => 'Competitive Pricing', 'Outright' => 'Outright','Mega Campaign'=>'Mega Campaign', 'Others' => 'Others'];
if($model->pre_approve == 1)
    $status = ['Pending'=>'Pending','Adjustments'=>'Adjustments','Approved'=>'Submit','Reject'=>'Reject','Cancel'=>'Cancel','Submitted'=>'Approved'];
else
    $status = ['Pending'=>'Pending','Adjustments'=>'Adjustments','Approved'=>'Approved','Reject'=>'Reject','Cancel'=>'Cancel'];
if(time() >= strtotime($model->start_date) && time() <= strtotime($model->end_date))
    $approve_status = ['Approved'=>'Approved','Cancel'=>'Cancel'];
else
    $approve_status = ['Approved'=>'Approved','Cancel'=>'Cancel','Adjustments'=>'Adjustments'];
$dname = explode('_',$model->name);
$model->name = ($model->name && isset($dname[1])) ? $dname[1] : $model->name ;
$prefixName = (!$model->isNewRecord) ? $dname[0] : '' ;
$is_deal_skus_error = $model->getErrors('requestedSkus');
$model->category = explode(',',$model->category);

?>
<style>
    .tg-kr944 select
    {
        width:100px;
    }
    .tg-kr944 textarea{
        width:100px;
    }
</style>
<?php
if(!empty($is_deal_skus_error)) :
    ?>

    <div class="alert alert-danger">
        <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong><?=implode(',',$is_deal_skus_error)?></strong>
    </div>


<?php endif ;?>
<div class="col-md-12">
    <p  for="dealsmaker-rn">Requested By <?=$model->requester->full_name?> <strong style="font-weight: bold">(<?="Prefix:".$prefixName?>)</strong></p>
</div>

<div class="content-box-wrapper">
    <?php $form = ActiveForm::begin(['id' => 'deal-maker-request']); ?>

    <div class="col-md-12" style="padding: 0px;">


        <div class="row">
            <div class="col-md-6" style="max-width:49.5%">
                <?=  $form->field($model, 'name')->textInput(['maxlength' => true , 'disabled'=>'disabled'])->hint('(optional) ** Auto prefix with Channel and Id') ?>

            </div>
            <div class="col-md-6" style="max-width:49.5%">
                <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['class' => 'form-control', 'prompt' => 'Select Channel', 'disabled'=>'disabled']) ?>

            </div>
        </div>
        <div class="row">
            <div class="col-md-6" style="max-width:49.5%">
                <?= $form->field($model, 'start_date')->textInput(['style'=>'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;','maxlength' => true,'class'=>'start_datetime form-control']); ?>

            </div>
            <div class="col-md-6" style="max-width:49.5%">
                <?= $form->field($model, 'end_date')->textInput([ 'style'=>'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;','maxlength' => true,'class'=>'end_datetime form-control']); ?>

            </div>
        </div>

        <div class="row" style="height: 300px;">
            <div class="col-md-6" style="max-width:49.5%">
                <?= $form->field($model, 'motivation')->textarea(['maxlength' => true,'readonly'=>true]) ?>
            </div>
            <div class="col-md-6" style="max-width:49.5%">
                <?= $form->field($model, 'budget')->textInput(['maxlength' => true,'class'=>'numberinput form-control']) ?>
                <?php
                $isTop = "";
                if ($model->status == 'active' || $model->status == 'expired'):
                    $isTop = "top:-192px";
                    $roi = ($model->actual_sales > 0 && (double)array_sum($model->actual_sales) != 0) ? (((double)$model->budget / (double)array_sum($model->actual_sales)) * 100) : 0; ?>
                    <?= $form->field($model, 'target_sales')->textInput(['maxlength' => true, 'readonly' => true, 'value' => number_format(array_sum($model->target_sales), 2)]) ?>
                    <?= $form->field($model, 'actual_sales')->textInput(['maxlength' => true, 'readonly' => true, 'value' => number_format(array_sum($model->actual_sales), 2)]) ?>
                    <?= $form->field($model, 'roi')->textInput(['maxlength' => true, 'readonly' => true, 'value' => number_format($roi, 2) . '%']) ?>
                <?php endif; ?>
            </div>

            <div class="col-md-6" style="max-width:49.5%;<?=$isTop?>">
                <?= $form->field($model, 'category')->dropDownList(array_merge(['all' => 'all'], $categoryList), ['class' => 'select2 form-control', 'multiple' => true, 'disabled' => !$model->isNewRecord]) ?>

                <?php
                    ?>
                    <?= $form->field($model, 'pre_approve',['options'=>['class'=>'col-md-3 pull-left']])->checkbox(['maxlength' => true,'disabled' => !$model->isNewRecord]) ?>
                    <?php
                ?>
            </div>
        </div>

        <input type="hidden" id="deal_id" value="<?= $model->id ?>"/>
        <input type="hidden" name="DealsMaker[pre_approve]" id="pre_approve" value="<?=($model->pre_approve==1) ? 1 : 0?>"/>



    </div>
    <div class="col-md-12">
        <hr>
        <div class="multi-skus">
            <div class="table-responsive m-t-40">
                <div class="dataTables_wrapper">
                    <table id="demo-foo-row-toggler"  class="dm-multi-sku table toggle-circle table-hover default breakpoint footable-loaded footable table-striped table-bordered nowrap">
                        <thead>
                        <tr style="text-align: center">
                            <th class="multi-sku-qty side-border footable-sortable">

                            </th>
                            <th data-toggle="true" class="footable-visible footable-first-column footable-sortable footable-sorted multi-sku side-border" style="border-left: 0px !important;">
                                SKUs
                            </th>
                            <th class="multi-sku-price side-border footable-visible footable-sortable">Price</th>
                            <th class="multi-sku-subsdiy side-border footable-visible footable-last-column footable-sortable">Subsidy</th>
                            <th class="multi-sku-subsdiy side-border footable-sortable footable-sortable">Current Stock</th>
                            <th class="multi-sku-qty side-border footable-sortable" >Sales Target</th>
                            <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                                <th class="multi-sku-qty side-border footable-sortable" >Actual Sales Count</th>
                            <?php endif;?>
                            <th class="multi-sku-qty side-border footable-sortable" >Margin %</th>
                            <th class="multi-sku-qty side-border footable-sortable" >Margin RM</th>
                            <th class="multi-sku-qty side-border footable-sortable">Reasons</th>
                            <th class="multi-sku-qty side-border footable-sortable">Status</th>
                            <th class="multi-sku-qty side-border footable-sortable">Comments</th>

                        </tr>
                        </thead>
                        <?php
                        $Ccounter=0;
                        foreach ($multiSkus as $k => $v):
                            $sku = str_replace('s_','',$k);
                            if(isset($_GET['sku']) && $_GET['sku'] != $sku  )
                                continue;
                            if ($Ccounter % 2 == 0) {
                                $rowClass= "white";
                            }else{
                                $rowClass= "#f2f7f8";
                            }
                            $bgColor = ($v['status'] == 'Submitted') ? '#f5f5f5' : '';
                            $bgColor = ($v['status'] == 'Cancel' || $v['status'] == 'Reject' ) ? '#fbd3d3' : $bgColor;
                            $readonly = ($v['status'] == 'Submitted') ? 'readonly' : '';
                            $statusx = ($v['status'] == 'Approved') ? $approve_status : $status;
                            ?>
                            <tr style="background-color: <?=$rowClass?> !important;" class='row-<?= str_replace('s_','',$k) ?>'>
                                <!--<td class="tg-kr944" style="padding: 5px;background-color: <?/*=$bgColor*/?>"><input type='text' data-sku-id='<?/*= str_replace('s_','',$k) */?>'  class='form-control ' name='DM[<?/*= $k */?>][sku]'-->
                                <td class="footable-first-column">
                                    <!--<span class="mdi mdi-plus-circle-outline" style="font-size: 25px;"></span>-->
                                    <a style="color: #54667a;" href='javascript:;' data-sku-id='<?= str_replace('s_','',$k) ?>'  class='dm-more up'>
                                        <i class='mdi mdi-plus-circle-outline' data-toggle="tooltip" title="More info" style="font-size: 29px"></i>
                                    </a>
                                </td>
                                <td  >
                                    <input type='hidden' data-sku-id='<?= str_replace('s_','',$k) ?>'  class='form-control' name='DM[<?= $k ?>][fbl]'
                                           value='<?= isset($v['fbl']) ? $v['fbl']:"";  ?>'>
                                    <input style="width: 100px" type='text' data-sku-id='<?= str_replace('s_','',$k) ?>'  class='form-control' name='DM[<?= $k ?>][sku]'
                                           readonly value='<?= $v['sku'] ?>'>
                                </td>
                                <td class="tg-kr944"  ><input readonly type='text'  data-sku-id='<?= $k ?>' class='form-control'
                                                              name='DM[<?= $k ?>][price]' value='<?= $v['price'] ?>'>
                                </td>
                                <td class="tg-kr944"  ><input readonly type='text'  data-sku-id='<?= $k ?>' class=' form-control'
                                                              name='DM[<?= $k ?>][subsidy]' value='<?= $v['subsidy'] ?>'>
                                </td>
                                <!--<td class="tg-kr944" style="padding: 15px;background-color: <?/*=$bgColor*/?>"><?/*= $v['stocks'] */?></td>-->
                                <td><input readonly type='text'  class=' form-control' value="<?= isset($v['stocks']) ? $v['stocks'] : '0' ?>">
                                </td>
                                <td class="tg-kr944"  ><input readonly type='text'  data-sku-id='<?= $k ?>' class='form-control' name='DM[<?= $k ?>][qty]'
                                                              value='<?= $v['qty'] ?>'>
                                </td>
                                <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                                    <td class="tg-kr944"  ><input readonly type='text'  data-sku-id='<?= $k ?>' class='form-control' name='DM[<?= $k ?>][qty]'
                                                                  value='<?= $v['aqty'] ?>'>
                                    </td>
                                <?php endif;?>
                                <td class="tg-kr944"  ><input style="font-weight: bold" readonly type='text' data-sku-id='<?= $k ?>' readonly class='form-control' name='DM[<?= $k ?>][margin_per]'  value='<?=$v['margin_per']?>%'>
                                </td>
                                <td class="tg-kr944"  ><input  readonly type='text'  data-sku-id='<?= $k ?>' readonly class='form-control' name='DM[<?= $k ?>][margin_rm]'  value='<?=$v['margin_rm']?>'>
                                </td>
                                <td class="tg-kr944"  >
                                    <input readonly class='form-control' value="<?=$v['reason']?>" name='DM[<?= $k ?>][reason]'>
                                </td>
                                <td class="tg-kr944"  >
                                    <select  class='form-control select-status' name='DM[<?= $k ?>][status]'>
                                        <?php foreach ($statusx as $kx=>$r):
                                            $selected = ($kx == $v['status']) ? 'selected' : ''; ?>
                                            <option <?=$selected?> value="<?=$kx?>"><?= $r ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="tg-kr944"  ><textarea style="height: 35px;
    overflow: auto;"  data-sku-id='<?= $k ?>' class='form-control'
                                                                 name='DM[<?= $k ?>][comments]'><?= $v['comments'] ?></textarea>
                                </td>
                            </tr>
                            <tr class='more-<?= str_replace('s_','',$k) ?> hide'>
                                <td colspan="11" style="background-color: <?=$bgColor?>">
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-12">
                                            <div class="card" style="margin-bottom: 3px">
                                                <div class="card-body dt-widgets" style="height: 408px;">

                                                    <div class="table-responsive">
                                                        <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                                            <tr><td class='dl-more-td'><label  >Actual Cost</label></td><td class='dl-more-td'><strong class='aprice_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Deal Cost</label></td><td class='dl-more-td'><strong class='price_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Extra Cost</label></td><td class='dl-more-td'><strong class='ecost_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-12">
                                            <div class="card" style="margin-bottom: 3px">
                                                <div class="card-body dt-widgets" style="height: 408px;">
                                                    <div class="table-responsive">
                                                        <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                                            <tr><td class='dl-more-td'><label  >Commission</label></td><td class='dl-more-td'><strong class='commision_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Shipping</label></td><td class='dl-more-td'> <strong class='shipping_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Gross Profit</label> </td><td class='dl-more-td'><strong class='gp_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Price w/ subsidy</label> </td><td class='dl-more-td'><strong class='pws_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Customer Pays</label></td><td class='dl-more-td'> <strong class='cp_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-12">
                                            <div class="card" style="margin-bottom: 3px">
                                                <div class="card-body dt-widgets" style="height: 408px;">

                                                    <div class="table-responsive">
                                                        <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                                            <tr><td class='dl-more-td'><label  >Total Stocks</label></td><td class='dl-more-td'><strong class='stocks_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >ISIS Stocks</label></td><td class='dl-more-td'> <strong class='isis_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >Office Stocks</label> </td><td class='dl-more-td'><strong class='os_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >FBL Blip Stocks</label> </td><td class='dl-more-td'><strong class='fbs_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >FBL 909 Stocks </label></td><td class='dl-more-td'> <strong class='f9s_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >FBL Deal4U Stocks </label></td><td class='dl-more-td'> <strong class='fds_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                            <tr><td class='dl-more-td'><label  >FBL Avent Stocks </label></td><td class='dl-more-td'> <strong class='fas_<?= str_replace('s_','',$k) ?>'></strong></td></tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>





                                </td>
                            </tr>
                            <?php
                            $Ccounter++;
                        endforeach; ?>
                    </table>

                </div>
            </div>


        </div>
        <?php
        if (isset($_GET['id'])){
            ?>
            <a href="/deals-maker/clone-csv?deal_id=<?=$_GET['id']?>" style="float: left;">Download CSV <i data-toggle="tooltip" data-original-title="When the sales team wants
to clone a deal, They will just download the CSV and upload it in a new deal to save time." class="mdi mdi-information"></i></a>
        <?php
        }
        ?>

        <div class="form-group" style="margin-top: 20px;float: right;">

            <?php if ($model->status != 'cancel'): ?>

                <?= Html::submitButton('Submit', ['class' => 'btn btn-success update-deal-approval', 'name' => 'request-btn',
                    'onClick'=> 'ApprovedValidation()']) ?>

            <?php
            if(time() >= strtotime($model->start_date) && time() <= strtotime($model->end_date))
            {
                echo '<button class="btn" style="float: right;margin-left: 10px" id="save-as-draft">Cancel Deal</button>';
                echo '<input type="hidden" name="cancel_deal" value="cancel_deal">';
            }
            endif;
            ?>
            <?= Html::a('<i style="font-size: 25px;float: right;margin-left: 10px" class="fa fa-print"></i>',\yii\helpers\Url::to('/deals-maker/export?did='.$model->id), ['class' => 'btn','title'=>'Export Deal Details', 'name' => 'export-btn',
                'onClick'=> 'exportThis()']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
<script type="text/javascript">
    function ApprovedValidation(){

        var select_dropdowns = $('.select-status');
        var v = [];
        select_dropdowns.each(function(){
            v.push($(this).val());
        })

        if ( v.includes("Approved") ){
            $("#deal-maker-request").submit();
        }else{
            alert('Please Submit atleast one sku row.');
            $("#deal-maker-request").submit(function(e){
                e.preventDefault();
            });
        }

        //console.log(v);

    }

</script>
<style>
    .control-label{
        padding: 0px !important;
    }
</style>