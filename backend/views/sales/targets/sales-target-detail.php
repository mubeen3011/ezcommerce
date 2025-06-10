<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales target', 'url' => ['/sales/targets']];
$this->params['breadcrumbs'][] = 'SKU Sales Targets';
$month=['january','february','march','april','may','june','july','august','september','october','november','december'];
if(isset($_GET['channel_id']) && !empty($_GET['channel_id'])){  // for channel drop down
    $channel_index=array_search($_GET['channel_id'],array_column($channels,'id'));
    $channel_name=$channels[$channel_index]->name;
    unset($channels[$channel_index]); // unset to not show in drop down if selected
}
// if main filter change , then clear sub filters or search , remove search variables
$get_to_keep= preg_grep("/search-/", array_keys($_GET) , PREG_GREP_INVERT); // get variables to keep for main filter changed
$filtered_get=$_GET;
foreach($filtered_get as $key=>$val)
{
    if(!in_array($key,$get_to_keep)) // remove extra sub filter or search
        unset($filtered_get[$key]);
}

?>
<!---css file----->
<link href="/../css/sales_v1.css" rel="stylesheet">
<style>

   .title a{

       font-weight:bold;
   }
    hr{
        margin-top:6px;
        margin-bottom:6px;
    }
    .static
    {
        background: #F2F7F8;
    }
    /*tr
    {
        border-bottom: 2px solid lightgray;
    }*/
   .child-row
   {
       border-bottom: 2px solid lightgray;
   }
   .tablesaw td
   {
       line-height: 2.5em;
   }
    /*.scale
    {
        box-shadow:-3px -18px 20px lightgray;
    }*/
    .pagination-total-record-span
   {
       display:none;
   }
    .page-item.active .page-link{
       background: #ABB0B6;
       border-color:#ABB0B6;
   }
    .record-per-page-label
   {
       display:none;
   }
   .dropdown-item
   {
       padding:1% 1% 4% 8%;
       font-size:12px;
   }
    .no_style_btn{
        background: transparent !important;
        color: black !important;
        border: none;
    }
    .header-filter-inputs
    {
        display:none;
        font-size:12px;
    }
    </style>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Sales Targets</h3>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Row -->
<!----======================-->
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['channel_id']) && !empty($_GET['channel_id'])) ? '':'active'?>" href="/sales/target-detail?id=<?= $_GET['id']; ?>" aria-expanded="true">
                <span >ALL</span>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= (isset($_GET['channel_id']) && !empty($_GET['channel_id'])) ? 'active':''?>" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                 <span ><?= isset($channel_name) ? $channel_name:'Channels' ?></span>
            </a>
            <div class="dropdown-menu">
                <?php foreach($channels as $channel) {
                    $channel_query= array_merge( $_GET, array( 'channel_id' => $channel['id'] ) );
                    ?>
                    <a class="dropdown-item"  href="?<?= http_build_query($channel_query);?>" >
                        <?= $channel['name'];?>
                    </a>
                <?php } ?>
            </div>
        </li>
        </ul>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <?php if(count($_GET) > 1): ?>
            <a data-toggle="tooltip" title="Clear filter" href="/sales/target-detail?id=<?= $_GET['id']; ?>" type="button" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"> </i>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
            <i class="fa fa-filter"></i>
        </button>
        <?php if(isset($target)){ ?>
        <?php if($target->status=="pending" && !$already_approved_exists){ ?>
        <button  class="btn btn-sm btn-secondary pull-right mr-2 approve_target_btn" data-target-id="<?= $target->id;?>" >Approve Target
            <i class="fa fa-notes"> </i>
        </button>
        <?php } elseif($target->status=="approved") { ?>
        <button disabled class="btn btn-sm btn-success pull-right mr-2"><span class="fa fa-check">Approved</span></button>
        <?php } else { ?>
            <button disabled class="btn btn-sm btn-warning pull-right mr-2"><span class="fa fa-trash"> Draft Target</span></button>
        <?php }} ?>
        <a  class=" mr-2 text-black p-1"  >Records
            <?= isset($total_records) ? $total_records:"x";?>
        </a>

        <div class="btn-group">
            <?php
                $stock_view= array_merge( $filtered_get, array( 'view_type' => 'stock' ) );
                $sales_view= array_merge( $filtered_get, array( 'view_type' => 'sales' ) );
                $record_view_selected="Sales View";
                if(isset($_GET['view_type']) && $_GET['view_type']=="stock")
                    $record_view_selected="Stock View";
            ?>
            <button type="button" class=" pull-right btn btn-secondary btn-sm dropdown-toggle no_style_btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <?= $record_view_selected;?>
            </button>
            <div class="dropdown-menu animated slideInUp" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 37px, 0px); top: 0px; left: 0px; will-change: transform;">
               <?php if($record_view_selected=="Sales View") {  ?>
                    <a class="dropdown-item" href="?<?= http_build_query($stock_view);?>">Stock View</a>
                <?php } else { ?>
                <a class="dropdown-item" href="?<?= http_build_query($sales_view);?>">Sales View</a>
                <?php } ?>
            </div>
        </div>

        <div class="btn-group">
            <?php
                $quarter_view= array_merge( $filtered_get, array( 'display_view' => 'quarterly' ) );
                $month_view= array_merge( $filtered_get, array( 'display_view' => 'monthly' ) );
                $display_view_selected="Monthly";
                if(isset($_GET['display_view']) && $_GET['display_view']=="quarterly")
                    $display_view_selected = "Quarterly";
            ?>
            <button type="button" class=" pull-right btn btn-secondary btn-sm dropdown-toggle no_style_btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?= $display_view_selected; ?>
            </button>
            <div class="dropdown-menu animated slideInUp" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 37px, 0px); top: 0px; left: 0px; will-change: transform;">
                <?php if($display_view_selected=="Monthly") {  ?>
                    <a class="dropdown-item" href="?<?= http_build_query($quarter_view);?>">Quarterly</a>
                <?php } else { ?>
                    <a class="dropdown-item" href="?<?= http_build_query($month_view);?>">Monthly</a>
                <?php } ?>
            </div>
        </div>

    </div>
</div>
<div class="row">
  <div class="col-12">
                        <!-- Column -->
   <div class="card">

    <div class="card-body table-responsive">

        <!--<button class="btn btn-info btn-sm"><span class="fa fa-plus"></span> Target</button>-->
        <?php
        if(!isset($_GET['search-scale'])) {
            if(isset($_GET['view_type']) && $_GET['view_type']=='stock') { ?>
            <h6 class="card-subtitle pull-right">Total Target : <?= isset($overall_total[0]['total_qty_target']) ? $overall_total[0]['total_qty_target']:'x' ;?> </h6>
            <h6 class="card-subtitle pull-left">Total Prior :<?= isset($overall_total[0]['total_prior_qty_sold']) ? $overall_total[0]['total_prior_qty_sold']:'x'  ?> </h6>

        <?php } else { ?>
            <h6 class="card-subtitle pull-right">Total Target : <?= isset($overall_total[0]['total_sales_target']) ? $overall_total[0]['total_sales_target']:'x' ;?> </h6>
            <h6 class="card-subtitle pull-left">Total Prior :<?= isset($overall_total[0]['total_prior_sales']) ? $overall_total[0]['total_prior_sales']:'x'  ?> </h6>
        <?php }} ?>


        <br/>
        <div class="table-wrapper">
            <?php if(isset($_GET['display_view']) && $_GET['display_view']=='quarterly' ) { ?>
            <?= Yii::$app->controller->renderPartial('targets/sales-target-detail-quarterly',$_params_); ?>
            <?php } else { ?>
            <?= Yii::$app->controller->renderPartial('targets/sales-target-detail-monthly',$_params_); ?>
            <?php } ?>
         </div>
    </div>
</div>

</div>
</div>
<?php


$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJs(<<< EOT_JS_CODE

$(function(){
/*$.each($('table tr'), function() {
     var sku=$(this).attr('data-id-pk');
     if(sku)
     {
        $('#td_prior_'+sku).html($('#input_prior_'+sku).val());
        $('#td_target_'+sku).html($('#input_target_'+sku).val());
     }
        
});*/


 $('.show_items').on('click',function(){
        let id_pk=$(this).attr('data-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('#child_row_' + id_pk).toggle();
    });
    
    ////filter btn 
    $('#filter-btn').on('click',function(){
    $('.header-filter-inputs').toggle();
    });
});

$('.approve_target_btn').on('click',function(){
var  target_id=$(this).attr('data-target-id');
var btn=$(this);
if(confirm('Are you sure') && target_id)
{
      $.ajax({
        type: "POST",
        url: '/sales/target-approval',
        data: {target_id:target_id},
        dataType: 'json',
        beforeSend: function(){
            $(btn).html("<span class='fa fa-spinner fa-spin'></span>");
        },
        success: function(msg){
            if(msg.status=="success") {
                $(btn).html("<span class='fa fa-check'> Approved</span>");
                $(btn).addClass('btn btn-success');
                $(btn).removeClass('btn btn-secondary');
            } 
            display_notice(msg.status,msg.msg);
            

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            $(btn).html('Approve Target');
            display_notice('failure',errorThrown);
        }
    });
}
return;
});
EOT_JS_CODE
);
?>
<!----======================->