<?php

use common\models\Settings;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops List', 'url' => ['/channels']];
$this->params['breadcrumbs'][] = 'Shops';
?>
<style>
    .filters-visible{
        display: none;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <a class="btn btn-info" href="/channels/create">Create Shop</a>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div class="search-table-outter table-cont pricing-index stk-tbl" id='table-cont'>
                    <div id="example23_filter" class="dataTables_filter">
                        <button type="button" id="export-table-csv" class=" btn btn-info" >
                            <i class="fa fa-download"></i> Export
                        </button>

                        <button type="button" class=" btn btn-info" id="filters">
                            <i class="fa fa-filter"></i>
                        </button>
                        <?php if($_GET) { ?>
                            <a href="/channels" class=" btn btn-info clear-filters" id="filters">
                                <i class="fa fa-filter"></i>
                            </a>
                        <?php } ?>
                        <!--<i class="mdi mdi-filter margin-right-filters-section" onclick="showfilters()" style="color: #009efb;font-size: 20px;float: left;cursor: pointer;" title="Click Here to show filters"></i>-->
                        <div class="dt-buttons margin-right-filters-section" style="float: left;">
                            <!--<a class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23" href="javasccript:;">
                                <span>Download CSV</span>
                            </a>-->
                            <!--<a href="javasccript:;" class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23"><i class="mdi mdi-download"></i> Export</a>-->
                        </div>
                        <label class="form-inline hidden-sm-down" style="float: right;display: none;">

                            <select id="records_per_page" style="height: 25px;" class=" input-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="75">75</option>
                                <option value="100">100</option>
                            </select>

                        </label>


                    </div>
                    <div class="table-responsive">

                        <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable"
                               data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable=""
                               data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                            <thead>
                            <form id="ci-search-form" method="get" action="">
                                <tr>

                                     <th>
                                         Name
                                        <input type="text" value="<?= isset($_GET['name']) ? $_GET['name']:""; ?>" name="name" data-filter-type="like"  class="filters-visible inputs-margin filter form-control ">
                                     </th>
                                    <th>
                                        Active
                                        <select name="is_active" class="form-control filters-visible inputs-margin filter">
                                            <option >SELECT</option>
                                            <option value="1">Yes</option>
                                            <option value="0">NO</option>
                                        </select>
                                    </th>
                                    <th>
                                        Prefix
                                        <input type="text" value="<?= isset($_GET['prefix']) ? $_GET['prefix']:""; ?>" name="prefix" data-filter-type="like"  class="filters-visible inputs-margin filter form-control ">
                                    </th>
                                    <th>
                                        Marketplace
                                        <input type="text" value="<?= isset($_GET['prefix']) ? $_GET['prefix']:""; ?>" name="prefix" data-filter-type="like"  class="filters-visible inputs-margin filter form-control ">
                                    </th>
                                   <th>Action</th>

                                </tr>
                                <input type="submit" style="display: none;">
                            </form>
                            </thead>

                            <tbody class="gridData">

                            <?php foreach($data as $key=>$val) { ?>
                                <tr>
                                        <td> <?=$val['name'] ?> </td>
                                        <td>

                                            <div class="onoffswitch">
                                                <input type="checkbox" data-channel-id="<?=$val['id'] ?>" name="current_state" class="onoffswitch-checkbox" id="myonoffswitch<?=$val['id'] ?>" <?=$val['is_active']==1 ? "checked":""; ?> >
                                                <label class="onoffswitch-label" for="myonoffswitch<?=$val['id'] ?>">
                                                    <span class="onoffswitch-inner"></span>
                                                    <span class="onoffswitch-switch"></span>
                                                </label>
                                            </div>

                                        </td>
                                        <td> <?=$val['prefix'] ?></td>
                                        <td> <?=$val['marketplace'] ?></td>
                                        <td><a data-toggle="tooltip" title="Edit" class="fa fa-edit" href="/channels/update?id=<?=$val['id'] ?>" ></a>
                                        </td>

                                </tr>
                            <?php  } if(empty($data)): ?>
                                <tr>
                                    <td> NO Record Found</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>

                    </div>

                    <?php //Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>count($data),
                        //'route'=>\Yii::$app->controller->module->requestedRoute])?>
                </div>
            </div>
        </div>
    </div>
</div>


<style type="text/css">
    .control-label{
        padding:0px;
    }
    pre{
        display: none;
    }
    a.sort {
        color: #0b93d5;
        text-decoration: underline;
    }
    .blockPage{
        border:0px !important;
        background-color: transparent !important;
    }

    input.filter {
        text-align: center;
        font-size: 12px !important;
        font-weight: normal !important;
        color: #007fff;

    }

    .tg-kr94 > select {
        width:93px;
    }
    .tg-kr94 > input {
        width:93px;
    }

</style>

<script type="text/javascript">
    var defaultUrl = '/stocks/manage-info';
    var sortUrl = '/stocks/manage-info-sort';
    var filterUrl = '/stocks/manage-info-filter';
    var pdqs = '<?= isset($_GET['pdqs']) ? $_GET['pdqs'] : '0' ?>';
</script>
<?php
$this->registerJs( <<< EOT_JS_CODE
$('.onoffswitch-checkbox').on('change',function(){
let channel_id=$(this).attr('data-channel-id');
//alert(channel_id); return;
let current_state=$(this).prop("checked");
$.ajax({
        type: "POST",
        url: '/channels/toggle-active',
        data: {channel_id:channel_id,current_state:current_state ? 1:0},
        dataType: 'json',
        beforeSend: function(){
                                $('.onoffswitch-checkbox').prop("disabled", true);					
                            },
         success: function(msg){
         
                display_notice(msg.status,msg.msg);
                $('.onoffswitch-checkbox').prop("disabled", false);
            
         },
        error: function(XMLHttpRequest, textStatus, errorThrown) 
        { 
                $('.onoffswitch-checkbox').prop("disabled", false);
                display_notice('failure',errorThrown);
        } 		
       });
});
EOT_JS_CODE
);

/*$this->registerJsFile(
    '@web/ao-js/table-filters.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]
);*/
$this->registerJs("
$( document ).trigger( \"enhance.tablesaw\" );
$('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass(\"filters-visible\");
        if( hasClass ){
            $('.inputs-margin').removeClass(\"filters-visible\");
        }else{
            $('.inputs-margin').addClass(\"filters-visible\");
        }

    });
");
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.full.js', [\yii\web\View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJs("$(\".select2\").select2();");
$this->registerJs('
$("#AddToThreshold").click(function(){
 var skuid = $("#skuid").val();
$.ajax({
   
                    type: "GET",
                    url: "/stocks/add-sku-threshold",
                    data:  "skuid="+skuid,
                    dataType: "html",
    
                    success: function(msg)
                    {
                    if (msg==1){
                        $(".sku-add-threshold-status").text("Sku Added Successfuly");
                        $(".sku-add-threshold-status").css("color","green");
                    }
                    else if(msg==0){
                    $(".sku-add-threshold-status").text("Error occuring while adding");
                    $(".sku-add-threshold-status").css("color","red");
                    }
                    else if ( msg==3 ){
                    $(".sku-add-threshold-status").text("Sku id is not set in the product_details table, First set it then add it in threshold");
                    $(".sku-add-threshold-status").css("color","red");
                     }
                        $.unblockUI({
                onUnblock: function(){ //alert(\'onUnblock\');
                }
            });
                    },
                    beforeSend: function()
                    {
                    $.blockUI({
                message: $(\'#displayBox\')
            });
                    }
                }); 
})
    
');
?>
