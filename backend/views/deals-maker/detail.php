<?php

use common\models\Channels;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\DealsMakerSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Deals Detail';
/*echo '<pre>';
print_r($filterStatuses);
die;*/
if ( count($_GET) > 1 ){
    $clear_filter=1;
}
else{
    $clear_filter=0;
}
?>
<style>
    .filters-visible{
        display: none;
    }
    th .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da !important;
        min-height: 38px;
        margin-top: 15px;
        display: none;
    }
</style>
<div class="row">

    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='Deals Detail'?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <div id="example23_filter" class="dataTables_filter">
                    <button type="button" id="export-table-csv" class=" btn btn-info" >
                        <i class="fa fa-download"></i> Export
                    </button>
                    <button type="button" class=" btn btn-info" id="filters">
                        <i class="fa fa-filter"></i>
                    </button>
                    <?php
                    if ($clear_filter)
                    {
                        ?>
                        <a href="/deals-maker/detail?page=1" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                        <?php
                    }
                    ?>
                    <!--<i class="mdi mdi-filter margin-right-filters-section" onclick="showfilters()" style="color: #009efb;font-size: 20px;float: left;cursor: pointer;" title="Click Here to show filters"></i>-->
                    <div class="dt-buttons margin-right-filters-section" style="padding-top: 3px;float: right;margin-right: 0px;">
                        <!--<a href="javasccript:;" class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23"><i class="mdi mdi-download"></i> Export</a>-->
                    </div>
                </div>
                <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                    <thead>
                        <form autocomplete="off">
                            <tr>
                                <th scope="col" data-tablesaw-priority="persist" class="footable-sortable sort sorting">SKU<br />
                                    <select name="sku" class="multiple select2 select2-multiple select2-form-control form-control inputs-margin  filters-visible" >
                                        <option></option>
                                        <?php
                                        foreach ($filterSkuIds as $key=>$value){
                                            ?>
                                            <option value="<?=$value['sku']?>"
                                                <?php
                                                if ( isset($_GET['sku']) && $value['sku']==$_GET['sku'] )
                                                    echo 'selected';

                                                ?>
                                            ><?=$value['sku']?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </th>
                                <th scope="col" data-tablesaw-priority="2" class="footable-sortable sort sorting">Shop<br />
                                    <select class=" select2-multiple select2-form-control form-control inputs-margin  filters-visible" name="shop">
                                        <option></option>
                                        <?php
                                        foreach ($filterChannels as $key=>$value){
                                            ?>
                                            <option value="<?=$value['id']?>"
                                                <?php
                                                if (  isset($_GET['shop']) &&  $value['id'] == $_GET['shop'] )
                                                    echo 'selected';
                                                ?>
                                            ><?=$value['name']?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </th>
                                <th scope="col" data-tablesaw-priority="3" class="footable-sortable sort sorting">Deal Name<br />
                                    <input type="text" name="deal_name" value="<?=isset($_GET['deal_name']) ? $_GET['deal_name'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="4" class="footable-sortable sort sorting">Lowest Price<br />
                                    <input type="text" data-filter-type="operator" name="lowest_price" value="<?=isset($_GET['lowest_price']) ? $_GET['lowest_price'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="4" class="footable-sortable sort sorting">Deal Price<br />
                                    <input type="text" data-filter-type="operator" name="deal_price" value="<?=isset($_GET['deal_price']) ? $_GET['deal_price'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="5" class="footable-sortable sort sorting">Margins %<br />
                                    <input type="text" data-filter-type="operator" name="margins_percentage" value="<?=isset($_GET['margins_percentage']) ? $_GET['margins_percentage'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="6" class="footable-sortable sort sorting">Margins USD<br />
                                    <input type="text" data-filter-type="operator" name="margins_rm" value="<?=isset($_GET['margins_rm']) ? $_GET['margins_rm'] : '' ?>" class="min-th-width inputs-margin form-control filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="7" class="footable-sortable sort sorting">Status<br />
                                    <select name="status" class="form-control inputs-margin  filters-visible">
                                        <option></option>
                                        <?php
                                        foreach ( $filterStatuses as $key=>$value ){
                                            if ($value['status']=='')
                                                continue;
                                            ?>
                                            <option value="<?=$value['status']?>"
                                                <?php
                                                if ( isset($_GET['status']) && $value['status']==$_GET['status'] ){
                                                    echo 'selected';
                                                }
                                                ?>><?=$value['status']?></option>
                                            <?php
                                        }
                                        ?>
                                    </select></th>
                                <th scope="col" data-tablesaw-priority="8" class="footable-sortable sort sorting">Start Date<br />
                                    <input type="text" name="start_date" value="<?=isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>" class="min-th-width inputs-margin filter form-control mydatepicker filters-visible"></th>
                                <th scope="col" data-tablesaw-priority="9" class="footable-sortable sort sorting">End Date<br />
                                    <input type="text" name="end_date" value="<?=isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>" class="min-th-width inputs-margin filter form-control mydatepicker filters-visible">
                                    <input type="hidden" name="page" value="1" />
                                    <input type="submit" style="display: none;">

                                </th>
                                <th scope="col" data-tablesaw-priority="10" class="footable-sortable sort sorting">Deal Status<br />
                                    <select name="deal_status" class="form-control filters-visible min-th-width inputs-margin">
                                        <option></option>
                                        <option value="pending" <?=(isset($_GET['deal_status']) && $_GET['deal_status']=='pending') ? 'selected' : ''?>>Pending</option>
                                        <option value="expired" <?=(isset($_GET['deal_status']) && $_GET['deal_status']=='expired') ? 'selected' : ''?>>Expired</option>
                                    </select>
                                </th>
                            </tr>
                        </form>
                    </thead>
                    <tbody>
                    <?php foreach ($skus as $r): ?>
                        <tr>
                            <td><?=$r['sku']?></td>
                            <td><?=$r['channel_name']?></td>
                            <td class="tg-kr94" title="<?=$r['name']?>">
                                <div class=" iffyTip wd100">
                                    <?=$r['name']?>
                                </div>
                            </td>
                            <td>RM <?=$r['deal_price']?></td>
                            <td>RM <?=$r['low_price']?></td>
                            <td><?=$r['deal_margin']?> %</td>
                            <td><?=$r['deal_margin_rm']?></td>
                            <td><?=$r['status']?></td>
                            <td><?=$r['start_date']?></td>
                            <td><?=$r['end_date']?></td>
                            <td><?=$r['deal_status']?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="pagination-footer">
                    <div class="dataTables_info remove_when_pagination_used" id="example23_info" role="status" aria-live="polite" style="float: left;">
                        <?php
                        $Totalrec = ceil($total_records/10);
                        //echo $total_records;die;
                        $page = $_GET['page'];
                        $reverse = $page - 1;
                        $previsou=0;
                        if( $reverse < 1 ){
                            $reverse = 1;
                        }
                        if( $_GET['page']>=2 ){
                            $prevId=$reverse;
                            $prevId = $_GET['page']-1;
                            $previsou = 1;
                        }
                        $forward = $page + 1;
                        if( $forward > $Totalrec ){
                            $forward  = $Totalrec-1;
                        }
                        if( $_GET['page']<$Totalrec-1 ){
                            $forId=$forward;
                            $forId = $_GET['page'] + 1;
                            $forwardou=1;
                        }
                        else{
                            $forwardou=0;
                        }
                        ?>
                    </div>
                    <?php
                    if ($_GET['page']!='All')
                    {
                        ?>
                        <nav aria-label="..." class="remove_when_pagination_used">
                            <ul class="pagination pull-right">
                                <?php
                                if($page>1){
                                    ?>
                                    <li class="page-item">
                                        <a class="page-link" href="/deals-maker/detail?<?=http_build_query($_GET).'&page='.$prevId?>'" tabindex="-1">Previous</a>
                                    </li>
                                    <?php
                                }
                                ?>
                                <?php
                                $page_counter_no = $page;
                                for ( $i=$reverse;$i<=$forward;$i++ ){
                                    ?>
                                    <li class="page-item <?php if( $i==$page ){echo 'active';} ?>"><a class="page-link" href="/deals-maker/detail?<?=http_build_query($_GET).'&page='.$i?>"><?=$i?></a></li>
                                    <?php
                                }
                                ?>
                                <?php
                                if($forwardou){
                                    ?>
                                    <li class="page-item">
                                        <a class="page-link" href="/deals-maker/detail?<?=http_build_query($_GET).'&page='.$forId?>'">Next</a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <a href="/deals-maker/detail?<?=http_build_query($_GET)?>&page=All">Show All Records</a>
                        </nav>
                        <?php
                    }
                    ?>

                </div>
            </div>

        </div>

    </div>

</div>
<?php
$this->registerJs("$( document ).trigger( \"enhance.tablesaw\" );");
$this->registerJs("
$('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass(\"filters-visible\");
        if( hasClass ){
            $('.inputs-margin').removeClass(\"filters-visible\");
            $('th .select2-selection--single').css('display','block');
        }else{
        $('th .select2-selection--single').css('display','none');
            $('.inputs-margin').addClass(\"filters-visible\");
        }

    });
    
    
");
$this->registerJs('
$("#export").click(function (event) {
        // var outputFile = \'export\'
        var outputFile = window.prompt("What do you want to name your output file (Note: This won\'t have any effect on Safari)") || \'export\';
        outputFile = outputFile.replace(\'.csv\',\'\') + \'.csv\'

        // CSV
        exportTableToCSV.apply(this, [$(\'.grid-view > table\'), outputFile]);

        // IF CSV, don\'t do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
    });
    function exportTableToCSV($table, filename) {
        var $headers = $table.find(\'tr:has(th)\')
            ,$rows = $table.find(\'tr:has(td)\')

            // Temporary delimiter characters unlikely to be typed by keyboard
            // This is to avoid accidentally splitting the actual contents
            ,tmpColDelim = String.fromCharCode(11) // vertical tab character
            ,tmpRowDelim = String.fromCharCode(0) // null character

            // actual delimiter characters for CSV format
            ,colDelim = \'","\'
            ,rowDelim = \'"\r\n"\';

        // Grab text from table into CSV formatted string
        var csv = \'"\';
        csv += formatRows($headers.map(grabRow));
        csv += rowDelim;
        csv += formatRows($rows.map(grabRow)) + \'"\';

        // Data URI
        var csvData = \'data:application/csv;charset=utf-8,\' + encodeURIComponent(csv);

        // For IE (tested 10+)
        if (window.navigator.msSaveOrOpenBlob) {
            var blob = new Blob([decodeURIComponent(encodeURI(csv))], {
                type: "text/csv;charset=utf-8;"
            });
            navigator.msSaveBlob(blob, filename);
        } else {
            $(this)
                .attr({
                    \'download\': filename
                    ,\'href\': csvData
                    //,\'target\' : \'_blank\' //if you want it to open in a new window
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
            //for some reason $cols = $row.find(\'td\') || $row.find(\'th\') won\'t work...
            var $cols = $row.find(\'td\');
            if(!$cols.length) $cols = $row.find(\'th\');

            return $cols.map(grabCol)
                .get().join(tmpColDelim);
        }
        // Grab and format a column from the table
        function grabCol(j,col){
            var $col = $(col),
                $text = $col.text();

            return $text.replace(\'"\', \'""\'); // escape double quotes

        }
    }
    $(".select2").select2();
    if($( ".mydatepicker" ).length){
        $(\'.mydatepicker, #datepicker\').datepicker();
    }
');