<?php

use common\models\CompetitivePricing;
use kartik\daterange\DateRangePicker;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\PricingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$date = Yii::$app->request->post('date');
$date = ($date == '') ? date('Y-m-d') : $date;
if( isset($_GET['date']) ){
    $date = $_GET['date'];
}

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Suggested Pricing Sheet';

/*for clear filter*/
/*echo count($_GET);
die;*/
$todayDate = date('m/d/Y');
$yesterdayDate = date('m/d/Y', strtotime("-1 days"));
if ( count($_GET) < 3 ){
    $clear_filter=0;
}elseif ( isset($_GET['date']) && ($_GET['date']==$todayDate || $_GET['date']==$yesterdayDate) ){
    $clear_filter=0;
}
else{
    $clear_filter=1;
}
$channelList = \backend\util\HelpUtil::getChannels();
?>

    <style>
        .filters-visible{
            display: none;
        }
        .control-label {
            padding: 0px;
        }
        .form-group-margin{
            margin-bottom: 10px;
        }
        .form-control{
            min-height:24px;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="card">

                <div class="card-body">
                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <h3>Suggested Pricing Sheet</h3>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="alert alert-info" style="margin: 0px;">  <?='Selected Date: '.$date?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">  </button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div id="example23_filter" class="dataTables_filter">
                                <button type="button" id="export-table-csv" class=" btn btn-info" >
                                    <i class="fa fa-download"></i> Export
                                </button>
                                <button type="button" class=" btn btn-info"  data-toggle="modal" data-target="#myModal" data-whatever="@mdo">
                                    <i class="fa fa-filter"></i> Advance Filter
                                </button>
                                <button type="button" class=" btn btn-info" id="filters">
                                    <i class="fa fa-filter"></i>
                                </button>
                                <?php
                                if ($clear_filter)
                                {
                                    ?>
                                    <a href="/pricing/index?show=all&page_no=1" class=" btn btn-info  clear-filters" id="filters">
                                        <i class="fa fa-filter"></i>
                                    </a>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>


                    <div style="padding: 15px;" class="pricing-index">
                        <div class="row grid-container">
                            <div id="example23_wrapper" class="table-parent" style="width:100%;">
                                <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                                    <thead>
                                    <form>
                                        <tr>

                                            <th scope="col" data-tablesaw-priority="persist" class="tg-kr94  ">SKU <br />
                                                <input type="text" name="skuid" value="<?=isset($_GET['skuid']) ? $_GET['skuid'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                            <th scope="col" data-tablesaw-priority="2" class="tg-kr94  ">3'm Avg. Sales<br />
                                                <input type="text" data-filter-type="operator" name="avg_sales" value="<?=isset($_GET['avg_sales']) ? $_GET['avg_sales'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>

                                            <th  data-toggle="tooltip" title="Selling Status"  scope="col" data-tablesaw-priority="3" class="tg-kr94  ">
                                                <?=('Selling Status')?>
                                                <input type="text" id="selling_status" value="<?=isset($_GET['selling_status']) ? $_GET['selling_status'] : '' ?>" name="selling_status" class="min-th-width inputs-margin form-control filters-visible">

                                                <input type="hidden" value="1" name="page_no">
                                                <input type="submit" style="display: none;">
                                                <?php
                                                if( isset($_GET['date']) ){
                                                    ?>
                                                    <input type="hidden" name="date" value="<?=$_GET['date']?>">
                                                    <?php

                                                }
                                                ?>
                                            </th>
                                            <!--<th data-toggle="tooltip" title="Cost Price" scope="col" data-tablesaw-priority="5" class="tg-kr94  "><?/*=('Cost Price')*/?>
                                                <input type="text" data-filter-type="operator" id="cost_price" value="<?/*=isset($_GET['cost_price']) ? $_GET['cost_price'] : '' */?>" name="cost_price" class="min-th-width inputs-margin form-control filters-visible" placeholder="">
                                                <br />
                                            </th>-->

                                            <?php
                                            $periority_counter=5;
                                            ?>
                                            <?php foreach ($channelList as $cl): ?>
                                                <?php if( !in_array($cl['id'],$user_columns) ){ continue; } ?>
                                                <th data-toggle="tooltip" title="<?= $cl['name'] ?> - Suggested Price" scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="tg-kr94   chl-<?= $cl['name'] ?>">
                                                    <?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']).' - Suggested Price'?>
                                                    <input data-filter-type="operator" type="text" name="Channels[<?=$cl["id"]?>][low_price]" class="filters-visible inputs-margin min-th-width form-control" value="<?=isset($_GET['Channels'][$cl['id']]['low_price']) ? $_GET['Channels'][$cl['id']]['low_price'] : '' ?>"/>
                                                </th>

                                                <th data-toggle="tooltip" title="<?= $cl['name'] ?> - Margins at Suggested Price %" scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="tg-kr94   chl-<?= $cl['name'] ?>">
                                                    <?=\backend\util\HelpUtil::ChannelPrefixByName($cl['name']).' - Margins at Suggested Price %'?>
                                                    <input type="text" name="Channels[<?=$cl["id"]?>][margins_low_price]" value="<?=isset($_GET['Channels'][$cl['id']]['margins_low_price']) ? $_GET['Channels'][$cl['id']]['margins_low_price'] : '' ?>" class="inputs-margin filters-visible min-th-width form-control"/>
                                                </th>

                                                <th data-toggle="tooltip" title="<?= $cl['name'] ?> - Sale Price" scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="tg-kr94   chl-<?= $cl['name'] ?>">
                                                    <?=\backend\util\HelpUtil::ChannelPrefixByName($cl['name']).' - Sale Price'?>
                                                    <input data-filter-type="operator" type="text" name="Channels[<?=$cl["id"]?>][sale_price]" class="filters-visible form-control inputs-margin min-th-width" value="<?=isset($_GET['Channels'][$cl['id']]['sale_price']) ? $_GET['Channels'][$cl['id']]['sale_price'] : '' ?>"/>
                                                </th>

                                                <!--<th data-toggle="tooltip" title="<?/*= $cl['name'] */?> - Margins at Sale Price %" scope="col" data-tablesaw-priority="<?/*=++$periority_counter*/?>" class=" tg-kr94   chl-<?/*= $cl['name'] */?>">
                                                    <?/*=\backend\util\HelpUtil::ChannelPrefixByName($cl['name']).'- Margins at Sale Price %'*/?>
                                                    <input type="text" name="Channels[<?/*=$cl["id"]*/?>][margin_sale_price]" class="form-control filters-visible inputs-margin min-th-width" value="<?/*=isset($_GET['Channels'][$cl['id']]['margin_sale_price']) ? $_GET['Channels'][$cl['id']]['margin_sale_price'] : '' */?>"/>
                                                </th>-->

                                                <!--<th data-toggle="tooltip" title="<?/*= $cl['name'] */?> - Loss / Profit in RM" scope="col" data-tablesaw-priority="<?/*=++$periority_counter*/?>" class="tg-kr94   chl-<?/*= $cl['name'] */?>">
                                                    <?/*=\backend\util\HelpUtil::ChannelPrefixByName($cl['name']).'- Loss / Profit in RM'*/?>
                                                    <input type="text" name="Channels[<?/*=$cl["id"]*/?>][loss_profit_rm]" class="form-control filters-visible inputs-margin min-th-width" value="<?/*=isset($_GET['Channels'][$cl['id']]['loss_profit_rm']) ? $_GET['Channels'][$cl['id']]['loss_profit_rm'] : '' */?>"/>
                                                </th>-->
                                            <?php endforeach; ?>
                                            <th scope="col" data-tablesaw-priority="<?=$periority_counter?>" class="tg-kr94  ">
                                                Detail View
                                            </th>
                                        </tr>
                                    </form>
                                    </thead>

                                    <tbody>
                                    <?php
                                    foreach ($skus as $key => $value) :?>
                                        <tr>
                                            <td class="tg-kr94">
                                                <?php
                                                if (isset($crawl_results[$value['sku']])){
                                                    ?>
                                                    <a style="color:blue" href="<?= \yii\helpers\Url::to(['/competitive-pricing/crawl-sku-details', 'sku' => $value['sku']]) ?>"><?= $value['sku'] ?></a>
                                                <?php
                                                }else{
                                                    ?>
                                                    <?= $value['sku'] ?>
                                                <?php
                                                }
                                                ?>

                                            </td>
                                            <td class="tg-kr94"><?= $value['as'] ?></td>
                                            <td class="tg-kr94"><?= $value['ss'] ?></td>
                                            <!--<td class="tg-kr94"><?/*= $value['cp'] */?></td>-->

                                            <?php foreach ($channelList as $cl): ?>
                                                <?php if( !in_array($cl['id'],$user_columns) ){ continue; } ?>
                                                <td class="tg-kr94 chl-<?= $cl['name'] ?>">
                                                    <?= isset($channelSkuList[$cl['id']][$key]['low_price']) ? $channelSkuList[$cl['id']][$key]['low_price'] : '' ?>
                                                </td>
                                                <td class="tg-kr94 chl-<?= $cl['name'] ?>">
                                                    <?=$margins_low_price='';?>
                                                    <?php isset($channelSkuList[$cl['id']][$key]['margins_low_price']) ? $margins_low_price=$channelSkuList[$cl['id']][$key]['margins_low_price'] : $margins_low_price='' ?>
                                                    <?php
                                                    if ($margins_low_price<=-5)
                                                        echo '<span style="color: red;">'.$margins_low_price.'</span>';
                                                    else if( $margins_low_price>-5 && $margins_low_price<5 )
                                                        echo '<span style="color: orange;">'.$margins_low_price.'</span>';
                                                    else if( $margins_low_price > 5 )
                                                        echo '<span style="color: green;">'.$margins_low_price.'</span>';

                                                    ?>
                                                </td>
                                                <td class="tg-kr94 chl-<?= $cl['name'] ?>">
                                                    <?= isset($channelSkuList[$cl['id']][$key]['sale_price']) ? $channelSkuList[$cl['id']][$key]['sale_price'] : '' ?>
                                                </td>
                                                <!--<td class="tg-kr94 chl-<?/*= $cl['name'] */?>"><?/*= isset($channelSkuList[$cl['id']][$key]['margin_sale_price']) ? $channelSkuList[$cl['id']][$key]['margin_sale_price'] : '' */?></td>
                                                <td class="tg-kr94 chl-<?/*= $cl['name'] */?>"><?/*= isset($channelSkuList[$cl['id']][$key]['loss_profit_rm']) ? $channelSkuList[$cl['id']][$key]['loss_profit_rm'] : '' */?></td>-->
                                            <?php endforeach; ?>
                                            <td class="tg-kr94">
                                                <a href="/pricing/detail?sku=<?=$value['sku']?>&date=<?=(isset($_GET['date'])) ? $_GET['date'] : date('Y-m-d') ?>">
                                                    <i class="ti-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-9">
                            <?php
                            if ($_GET['page_no']!='All'){
                                ?>
                                <a href="/pricing/index?<?=http_build_query($_GET).'&page_no=All'?>">Show All Records</a>
                                <?php
                            }
                            ?>

                        </div>
                        <div class="col-md-3 col-sm-12" style="float: right;">

                            <?php
                            if ( $_GET['page_no']!='All' ){
                                ?>
                                <nav aria-label="..." class="remove_when_pagination_used">
                                    <ul class="pagination pull-right">
                                        <?php
                                        $max_page= $total_pages;
                                        $page = $_GET['page_no'];
                                        $reverse = $page - 1;
                                        if( $reverse < 1 ){
                                            $reverse = 1;
                                        }
                                        $forward = $page + 1;
                                        if( $forward > $max_page ){
                                            $forward  = $max_page;
                                        }

                                        if( isset( $_GET['page_no'] ) && $_GET['page_no']>1){
                                            ?>
                                            <li class="page-item paginate_button">
                                                <a class="page-link" href="#" tabindex="-1" onclick="PageNo(<?=$_GET['page_no']-1?>)">Previous</a>
                                            </li>
                                            <?php
                                        }
                                        ?>



                                        <?php
                                        $page_counter_no = $_GET['page_no'];
                                        for ( $i=$reverse;$i<=$forward;$i++ ){
                                            $pagewiselimits=$i*50;
                                            ?>
                                            <li class="page-item <?php if(!isset($_GET['limit']) && $i==1){ echo 'active'; }
                                            else if( isset($_GET['page_no']) && $_GET['page_no']==$i ){ echo 'active'; } ?>">

                                                <form>
                                                    <?php
                                                    /*echo '<pre>';
                                                    print_r($_GET);
                                                    */
                                                    foreach ( $_GET as $key=>$value ){
                                                        if ($key=='filters' || $key=='limit')
                                                            continue;
                                                        ?>
                                                        <?php
                                                        if( $key == 'Channels' ){
                                                            foreach  ( $value as $keyzz=>$valuezz ){
                                                                foreach ( $valuezz as $key2=>$val3 ){
                                                                    ?>
                                                                    <input type="hidden" name="Channels[<?=$keyzz?>][<?=$key2?>]" value="<?=isset( $_GET['Channels'][$keyzz][$key2] ) ? $_GET['Channels'][$keyzz][$key2] : ''?>">
                                                                    <?php
                                                                }
                                                                ?>

                                                                <?php
                                                            }
                                                            continue;
                                                        }
                                                        ?>
                                                        <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
                                                        <?php

                                                    }
                                                    ?>
                                                    <input type="hidden" name="limit" value="<?=$pagewiselimits?>,50" />
                                                    <input type="hidden" value="<?=$i?>" name="page_no">
                                                    <input class="page-link" type="submit" value="<?=$i?>">
                                                </form>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if( $_GET['page_no'] != $max_page ){
                                            ?>
                                            <li class="page-item">
                                                <form>
                                                    <?php
                                                    /*echo '<pre>';
                                                    print_r($_GET);*/
                                                    foreach ( $_GET as $key=>$value ){
                                                        if ($key=='filters' || $key=='limit')
                                                            continue;
                                                        ?>
                                                        <?php
                                                        if( $key == 'Channels' ){
                                                            foreach  ( $value as $keyzz=>$valuezz ){
                                                                foreach ( $valuezz as $key2=>$val3 ){
                                                                    ?>
                                                                    <input type="hidden" name="Channels[<?=$keyzz?>][<?=$key2?>]" value="<?=isset( $_GET['Channels'][$keyzz][$key2] ) ? $_GET['Channels'][$keyzz][$key2] : ''?>">
                                                                    <?php
                                                                }
                                                                ?>

                                                                <?php
                                                            }
                                                            continue;
                                                        }
                                                        ?>
                                                        <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
                                                        <?php

                                                    }
                                                    ?>
                                                    <input type="hidden" name="limit" value="<?=50*($_GET['page_no']-1)?>,50" />
                                                    <input type="hidden" value="<?=$_GET['page_no']+1?>" name="page_no">
                                                    <input class="page-link" type="submit" value="Next">
                                                </form>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>

                                </nav>

                                <?php
                            }
                            ?>

                        </div>
                    </div>




                </div>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="mdi mdi-magnify-minus-outline" style="font-size: 17px;"></i>
                    <h4 class="modal-title" id="myModalLabel">Advanced Search</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="get">
                        <div class="form-body">
                            <h3 class="card-title" style="margin-bottom: -0.25rem;">Sku</h3>
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group form-group-margin" style="margin-right: 5px;">
                                        <label class="control-label">SKU: </label>
                                        <input type="text" name="skuid" value="<?=isset($_GET['skuid']) ? $_GET['skuid'] : '' ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label class="control-label">Cost Price</label>
                                        <input type="text" id="cost_price" value="<?=isset($_GET['cost_price']) ? $_GET['cost_price'] : '' ?>" name="cost_price" class="form-control" placeholder="">
                                        <!--<small class="form-control-feedback"> Select your gender </small>-->
                                    </div>
                                </div>
                                <!--/span-->
                                <!--/span-->
                            </div>
                            <!--/row-->
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label class="control-label">Filter By Date: </label>
                                        <input type="text" name="date" autocomplete="off" value="<?=isset($_GET['date']) ? $_GET['date'] : date('m/d/Y') ?>" class="form-control mydatepicker">
                                        <input type="hidden" name="page_no" value="1"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label class="control-label">Selling Status	</label>
                                        <input type="text" id="selling_status" value="<?=isset($_GET['selling_status']) ? $_GET['selling_status'] : '' ?>" name="selling_status" class="form-control">
                                        <!--<small class="form-control-feedback"> This is inline help </small>-->
                                    </div>
                                </div>
                            </div>
                            <h3 class="box-title m-t-40">Pricing Filter</h3>
                            <hr>
                            <div class="row">
                                <div class="col-md-6 ">
                                    <div class="form-group form-group-margin">
                                        <label>Lowest Price</label>
                                        <input type="text" value="<?=isset($_GET['lowest_price']) ? $_GET['lowest_price'] : '' ?>" name="lowest_price" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label>Margin at lowest price</label>
                                        <input type="text" value="<?=isset($_GET['margin_at_lowest_price']) ? $_GET['margin_at_lowest_price'] : '' ?>" name="margin_at_lowest_price" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <!--/span-->
                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label>Margin at Sale Price </label>
                                        <input type="text" value="<?=isset($_GET['margin_at_sale_price']) ? $_GET['margin_at_sale_price'] : '' ?>" name="margin_at_sale_price" class="form-control">
                                    </div>
                                </div>
                                <!--/span-->
                                <!--/span-->

                                <div class="col-md-6">
                                    <div class="form-group form-group-margin">
                                        <label>Loss/Profit in RM</label>
                                        <input type="text" value="<?=isset($_GET['loss_profit_rm']) ? $_GET['loss_profit_rm'] : '' ?>" name="loss_profit_rm" class="form-control">
                                    </div>
                                </div>
                                <!--/span-->
                            </div>
                            <h3 class="box-title m-t-40">Column Views</h3>
                            <hr>
                            <div class="row">
                                <!--/span-->
                                <?php
                                foreach ( $channelList as $key=>$value ){
                                    ?>
                                    <div class="col-md-2">
                                        <div class="m-b-10">
                                            <label class="custom-control custom-checkbox">
                                                <input type="checkbox" name="filters[column][<?=$value['name']?>]" value="<?=$value['id']?>" <?=(in_array($value['id'],$user_columns)) ? 'checked' : '' ;?> class="custom-control-input">
                                                <span class="custom-control-label"><?=$value['name']?></span>
                                            </label>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>

                                <!--/span-->
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group form-group-margin">
                                        <button type="submit" id="submit-filters" class="form-control btn btn-success pull-right"> <i class="fa fa-check"></i> Submit</button>
                                    </div>
                                </div>
                            </div>
                            <!--/row-->
                        </div>
                        <input type="hidden" name="limit" value="0,50" />
                        <input type="hidden" value="1" name="page_no">
                    </form>
                </div>
                <div class="modal-footer">

                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

<?php
$this->registerJs("
jQuery('.mydatepicker').datepicker({
        todayHighlight: true,
        dateFormat: 'yy-mm-dd'
    });
    $(\"#export\").click(function (event) {
        // var outputFile = 'export'
        var outputFile = window.prompt(\"What do you want to name your output file (Note: This won't have any effect on Safari)\") || 'export';
        outputFile = outputFile.replace('.csv','') + '.csv'

        // CSV
        exportTableToCSV.apply(this, [$('#example23_wrapper > table'), outputFile]);

        // IF CSV, don't do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
    });
    
");

?>
    <script>
        function exportTableToCSV($table, filename) {
            var $headers = $table.find('tr:has(th)')
                ,$rows = $table.find('tr:has(td)')

                // Temporary delimiter characters unlikely to be typed by keyboard
                // This is to avoid accidentally splitting the actual contents
                ,tmpColDelim = String.fromCharCode(11) // vertical tab character
                ,tmpRowDelim = String.fromCharCode(0) // null character

                // actual delimiter characters for CSV format
                ,colDelim = '","'
                ,rowDelim = '"\r\n"';

            // Grab text from table into CSV formatted string
            var csv = '"';
            csv += formatRows($headers.map(grabRow));
            csv += rowDelim;
            csv += formatRows($rows.map(grabRow)) + '"';

            // Data URI
            var csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

            // For IE (tested 10+)
            if (window.navigator.msSaveOrOpenBlob) {
                var blob = new Blob([decodeURIComponent(encodeURI(csv))], {
                    type: "text/csv;charset=utf-8;"
                });
                navigator.msSaveBlob(blob, filename);
            } else {
                $(this)
                    .attr({
                        'download': filename
                        ,'href': csvData
                        //,'target' : '_blank' //if you want it to open in a new window
                    });
            }

            //------------------------------------------------------------
            // Helper Functions
            //------------------------------------------------------------
            // Format the output so it has the appropriate delimiters
            function formatRows(rows){
                return rows.get().join(tmpRowDelim)
                    .split(tmpRowDelim).join(rowDelim)
                    .split(tmpColDelim).join(colDelim);
            }
            // Grab and format a row from the table
            function grabRow(i,row){

                var $row = $(row);
                //for some reason $cols = $row.find('td') || $row.find('th') won't work...
                var $cols = $row.find('td');
                if(!$cols.length) $cols = $row.find('th');

                return $cols.map(grabCol)
                    .get().join(tmpColDelim);
            }
            // Grab and format a column from the table
            function grabCol(j,col){
                var $col = $(col),
                    $text = $col.text();

                return $text.replace('"', '""'); // escape double quotes

            }
        }
    </script>
<?php
$this->registerJs("// Code goes here
$('tbody').scroll(function(e) { //detect a scroll event on the tbody
  	/*
    Setting the thead left value to the negative valule of tbody.scrollLeft will make it track the movement
    of the tbody element. Setting an elements left value to that of the tbody.scrollLeft left makes it maintain 			it's relative position at the left of the table.    
    */
    $('thead').css(\"left\", -$(\"tbody\").scrollLeft()); //fix the thead relative to the body scrolling
    $('thead th:nth-child(1)').css(\"left\", $(\"tbody\").scrollLeft()); //fix the first cell of the header
    $('.tbdyTd').css(\"left\", $(\"tbody\").scrollLeft()); //fix the first column of tdbody
    console.log($('tbody tr .tg-kr94:nth-child(1)'));
  });
$( document ).trigger( \"enhance.tablesaw\" );
$('#submit-filters').click(function(){
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
})
$('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass(\"filters-visible\");
        if( hasClass ){
            $('.inputs-margin').removeClass(\"filters-visible\");
        }else{
            $('.inputs-margin').addClass(\"filters-visible\");
        }

    });
");