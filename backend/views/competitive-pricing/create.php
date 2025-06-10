<?php


use common\models\Sellers;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\typeahead\Typeahead;
use yii\jui\AutoComplete;


/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */
$include = [1, 2, 3];
$channelList = \backend\util\HelpUtil::getChannels($include);

$disable = '';
$insertDate = Yii::$app->request->post('insert_date');
$insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
if ($insertDate != date('Y-m-d'))
    $disable = 'disabled="disabled"';


$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Competitive Pricing';

$refineBits = ['0' => 'No', '1' => 'Yes'];

$sellers = Sellers::find()->orderBy('id')->asArray()->all();
$sellersList = ArrayHelper::map($sellers, 'seller_name', 'seller_name');
$sl = [];
$data = Sellers::find()
    ->select(['seller_name as value', 'seller_name as  label', 'seller_name as id'])
    ->asArray()
    ->all();


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
        #w0,#w2,#w1{
            max-widows:120px;
            min-widows:120px;
        }
        .numc{
            max-widows:120px;
            min-widows:120px;
        }
        pre{
            display:none;
        }
        .wd20 {
            width:20px;
        }
        .control-label {
            padding: 0px;
        }
    </style>


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class=" row">
                        <div id="displayBox" style="display: none;">
                            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <h3>Competitive Pricing - <?= $insertDate?></h3>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <!--<div class="input-group">
                                <input type="text" class="form-control mydatepicker" placeholder="yyyy-mm-dd" value="<?/*=$insertDate*/?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="icon-calender"></i></span>
                                </div>
                            </div>-->
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>
                    <!--<div id="example23_filter" class="dataTables_filter">
                        <label class="form-inline hidden-sm-down">
                            <form method="get" id="per_page_form" action="">
                                <select name="records_per_page" id="records_per_page" class="form-control input-sm">
                                    <option value="25" <?php /*if( isset($_GET['records_per_page']) && $_GET['records_per_page']==25 ){echo 'selected';} */?>>25</option>
                                    <option value="50" <?php /*if( isset($_GET['records_per_page']) && $_GET['records_per_page']==50 ){echo 'selected';} */?> >50</option>
                                    <option value="75" <?php /*if( isset($_GET['records_per_page']) && $_GET['records_per_page']==75 ){echo 'selected';} */?>>75</option>
                                    <option value="100" <?php /*if( isset($_GET['records_per_page']) && $_GET['records_per_page']==100 ){echo 'selected';} */?>>100</option>
                                </select>
                            </form>
                        </label>
                    </div>-->

                    <div id="example23_wrapper" class="dataTables_wrapper">
                        <div id="example23_filter" class="dataTables_filter">
                            <!--<i class="mdi mdi-filter margin-right-filters-section" onclick="showfilters()" style="color: #009efb;font-size: 20px;float: left;cursor: pointer;" title="Click Here to show filters"></i>-->
                            <!--<i class="mdi mdi-filter margin-right-filters-section" onclick="showfilters()" style="color: #009efb;font-size: 20px;float: left;cursor: pointer;" title="Click Here to show filters"></i>-->

                            <button type="button" id="export-table-csv" class=" btn btn-info" >
                                <i class="fa fa-download"></i> Export
                            </button>
                            <!--<button type="button" class=" btn btn-info" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">
                                <i class="fa fa-filter"></i> Advance Filter
                            </button>-->
                            <button type="button" class=" btn btn-info" id="filters">
                                <i class="fa fa-filter"></i>
                            </button>
                            <?php
                            if ($clear_filter)
                            {
                                ?>
                                <a href="/competitive-pricing/create?page=1" class=" btn btn-info  clear-filters" id="filters">
                                    <i class="fa fa-filter"></i>
                                </a>
                                <?php
                            }
                            ?>
                        </div>


                        <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                            <thead>
                            <form>

                                <tr>
                                    <th class="tg-kr94  min-th-width" scope="col" data-tablesaw-priority="persist">SKU
                                        <br />
                                        <input type="text" name="sku_id_search" value="<?=(isset($_GET['sku_id_search'])) ? $_GET['sku_id_search'] : ''?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th class="tg-kr94  min-th-width" scope="col" data-tablesaw-priority="2">Category
                                        <br />
                                        <select name="sub_category_search" class="filters-visible form-control inputs-margin"  >
                                            <option></option>
                                            <?php
                                            foreach ( $categories as $value ){
                                                ?>
                                                <option value="<?=$value['id']?>" <?=(isset($_GET['sub_category_search']) && $value['id']==$_GET['sub_category_search'] ) ? 'selected' : ''?>><?=$value['name']?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </th>
                                    <th data-toggle="tooltip" title="Selling Status" class="tg-kr94 min-th-width" scope="col" data-tablesaw-priority="3">
                                        <?=\backend\util\HelpUtil::getThShorthand('Selling Status')?>
                                        <br />
                                        <input type="text" name="selling_status_search" value="<?=isset($_GET['selling_status_search']) ? $_GET['selling_status_search'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th class="tg-kr94 pname  " scope="col" data-tablesaw-priority="4">Name
                                        <br />
                                        <input type="text" name="name_search" value="<?=isset($_GET['name_search']) ? $_GET['name_search'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th class="tg-kr94  " scope="col" data-tablesaw-priority="5">Keywords
                                        <br />
                                        <input type="text" name="keywords" value="<?=isset($_GET['keywords']) ? $_GET['keywords'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('Lazada').' - Seller'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="6">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('Lazada').' - Seller')?>
                                        <br />
                                        <input type="text" name="channel[1][seller_name]" value="<?=isset($_GET['channel'][1]['seller_name']) ? $_GET['channel'][1]['seller_name'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>

                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('Lazada').' - L.p'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="7">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('Lazada').' - L.p')?>
                                        <br />
                                        <input type="text" name="channel[1][low_price]" data-filter-type="operator" value="<?=isset($_GET['channel'][1]['low_price']) ? $_GET['channel'][1]['low_price'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('Shopee').' - Seller'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="8">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('Shopee').' - Seller')?>
                                        <br />
                                        <input type="text" name="channel[2][seller_name]" value="<?=isset($_GET['channel'][2]['seller_name']) ? $_GET['channel'][2]['seller_name'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('Shopee').' - L.p'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="9">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('Shopee').' - L.p')?>
                                        <br />
                                        <input type="text" name="channel[2][low_price]" data-filter-type="operator" value="<?=isset($_GET['channel'][2]['low_price']) ? $_GET['channel'][2]['low_price'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('11Street').'- Seller'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="10">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('11Street').'- Seller')?>
                                        <br />
                                        <input type="text" name="channel[3][seller_name]" value="<?=isset($_GET['channel'][3]['seller_name']) ? $_GET['channel'][3]['seller_name'] : '' ?>"  class="filters-visible inputs-margin form-control">
                                    </th>
                                    <th data-toggle="tooltip" title="<?=\backend\util\HelpUtil::ChannelPrefixByName('11Street').' - L.p'?>" class="tg-kr94  " scope="col" data-tablesaw-priority="11">
                                        <?=\backend\util\HelpUtil::getThShorthand(\backend\util\HelpUtil::ChannelPrefixByName('11Street').' - L.p')?>
                                        <br />
                                        <input type="text" name="channel[3][low_price]" data-filter-type="operator" value="<?=isset($_GET['channel'][3]['low_price']) ? $_GET['channel'][3]['low_price'] : '' ?>"  class="filters-visible inputs-margin form-control">

                                    </th>
                                    <!--<th class="tg-kr94  " scope="col" data-tablesaw-priority="12">Save</th>-->
                                    <!--<th class="tg-kr94" scope="col" data-tablesaw-priority="18">Force fetch</th>-->
                                    <?php
                                    $counter=18;
                                    ?>
                                    <?php /*foreach ($channelList as $cl): */?><!--
                                                <th scope="col" data-tablesaw-priority="<?/*=++$counter*/?>" class="tg-kr94 pchange  "><?/*= $cl['name'] */?></th>
                                            --><?php /*endforeach; */?>
                                    <!--<th class="tg-kr94  " scope="col" data-tablesaw-priority="<?/*=++$counter*/?>">Save Button</th>-->
                                </tr>
                                <input type="hidden" name="page" value="1"/>
                                <input type="submit" style="display:none" />
                            </form>
                            </thead>


                            <tbody>

                            <?php $i = 1;
                            $csrfToken=Yii::$app->request->csrfToken;
                            foreach ($skuList as $sl): ?>
                                <?php
                                /*echo '<pre>';
                                print_r($sl);
                                die;*/
                                ?>
                                <tr>

                                    <td class="tg-kr94  " data-sku-id="<?= $sl['id'] ?> ">
                                        <form id="sku_<?= $sl['id'] ?>">
                                            <input type="hidden" name="insert_date" value="<?= $insertDate ?>">
                                            <input id="form-token" type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                                   value="<?= $csrfToken ?>"/>
                                            <input type="hidden" name="sku_id" value="<?= $sl['sku_id'] ?>">
                                            <a   style="color:blue"
                                                 href="<?= \yii\helpers\Url::to(['crawl-sku-details', 'sku' => ($sl['sku'])]) ?>"><?= $sl['sku'] ?></a>

                                    </td>
                                    <td class="tg-kr94">
                                        <?= $sl['subCategory']['name']; ?>
                                    </td>
                                    <td class="tg-kr94">
                                        <?= $sl['selling_status']; ?>
                                    </td>
                                    <td class="tg-kr94 pname ">
                                        <div class="iffyTip wd100">
                                            <?= $sl['name'] ?>
                                        </div>
                                    </td>
                                    <td class="tg-kr94">
                                        <?php
                                        $kw = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['1'])) ? $archiveList[$sl['sku_id']]['1']['keywords'] : '';
                                        ?>
                                        <input <?= $disable ?> type="text" name="kw_1" value="<?= $kw ?>"
                                                               class="form-control">
                                        <p style="display: none;"><?= $kw ?></p>
                                    </td>

                                    <td class="tg-kr94">
                                        <?php
                                        $sellerName = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['1'])) ? $archiveList[$sl['sku_id']]['1']['seller_name'] : '';
                                        $sellerName = ucwords($sellerName);

                                        if ($disable) {
                                            echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                                            echo '<p style="display: none;">'.$sellerName.'</p>';
                                        } else {
                                            echo AutoComplete::widget([
                                                'name' => 'seller_1',
                                                'value' => $sellerName,
                                                'clientOptions' => [
                                                    'source' => $data,
                                                ],
                                                'options' => [
                                                    'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                                                ],
                                            ]);
                                            echo '<p style="display: none;">'.$sellerName.'</p>';
                                        }

                                        ?>
                                    </td>
                                    <td class="tg-kr94">
                                        <?php
                                        $lowPrice = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['1'])) ? $archiveList[$sl['sku_id']]['1']['low_price'] : '';
                                        ?>
                                        <input <?= $disable ?> type="text" name="low_price_1" value="<?= $lowPrice ?>"
                                                               class="form-control numc">
                                        <p style="display: none;"><?= $lowPrice ?></p>
                                    </td>


                                    <td class="tg-kr94">
                                        <?php
                                        $sellerName = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['2'])) ? $archiveList[$sl['sku_id']]['2']['seller_name'] : '';
                                        $sellerName = ucwords($sellerName);
                                        if ($disable) {
                                            echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                                            echo '<p style="display: none;">'.$sellerName.'</p>';
                                        } else {
                                            echo AutoComplete::widget([
                                                'name' => 'seller_2',
                                                'value' => $sellerName,
                                                'clientOptions' => [
                                                    'source' => $data,
                                                ],
                                                'options' => [
                                                    'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                                                ],
                                            ]);
                                            echo '<p style="display: none;">'.$sellerName.'</p>';
                                        }

                                        ?>
                                    </td>
                                    <td class="tg-kr94">
                                        <?php
                                        $lowPrice = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['2'])) ? $archiveList[$sl['sku_id']]['2']['low_price'] : '';
                                        ?>
                                        <input <?= $disable ?> type="text" name="low_price_2" value="<?= $lowPrice ?>"
                                                               class="form-control numc">
                                        <p style="display: none"><?= $lowPrice ?></p>
                                    </td>

                                    <td class="tg-kr94">
                                        <?php
                                        $sellerName = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['3'])) ? $archiveList[$sl['sku_id']]['3']['seller_name'] : '';
                                        $sellerName = ucwords($sellerName);
                                        if ($disable) {
                                            echo '<input type="text" class="form-control" disabled="disabled" value="' . $sellerName . '">';
                                        } else {
                                            echo AutoComplete::widget([
                                                'name' => 'seller_3',
                                                'value' => $sellerName,
                                                'clientOptions' => [
                                                    'source' => $data,
                                                ],
                                                'options' => [
                                                    'placeholder' => 'Type seller name', 'class' => 'only_alphanumric form-control typeh', 'maxlength' => '3'
                                                ],
                                            ]);
                                            echo '<p style="display: none;">'.$sellerName.'</p>';
                                        }
                                        ?>
                                    </td>
                                    <td class="tg-kr94">
                                        <?php
                                        $lowPrice = (isset($archiveList[$sl['sku_id']]) && isset($archiveList[$sl['sku_id']]['3'])) ? $archiveList[$sl['sku_id']]['3']['low_price'] : '';
                                        ?>
                                        <input <?= $disable ?> type="text" name="low_price_3" value="<?= $lowPrice ?>"
                                                               class="form-control numc">
                                        <p style="display: none;"><?= $lowPrice ?></p>
                                        <input style="display: none;" type="button" class="cp-save" name="save" value="save" class="btn btn-info btn-xs" />
                                        </form>
                                    </td>


                                    <!--<td><a href="/crawl/run?sku_id=<?/*=$sl['sku']*/?>"  >Force fetch</a></td>
                                                    <td class="tg-kr94 pchange ch1_txt">
                                                        <?php
                                    /*                                                        echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $refineBits[$archiveList[$sl['id']]['1']['change_price']] : '';
                                                                                            */?>
                                                    </td>
                                                    <td class="tg-kr94 pchange ch2_txt">
                                                        <?php
                                    /*                                                        echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['2'])) ? $refineBits[$archiveList[$sl['id']]['2']['change_price']] : '';
                                                                                            */?>
                                                    </td>
                                                    <td class="tg-kr94 pchange ch3_txt">
                                                        <?php
                                    /*                                                        echo (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $refineBits[$archiveList[$sl['id']]['3']['change_price']] : '';
                                                                                            */?>
                                                    </td>
                                                    <td class="tg-kr94" style="display: none" >
                                                    <td class="tg-kr94">
                                                        <input <?/*= $disable */?> type="button" class="cp-save" name="save" value="save"
                                                                               class="btn btn-info btn-xs">
                                                    </td>-->


                                </tr>
                                <?php $i++; endforeach; ?>
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
                                }else{
                                    $forwardou=0;
                                }
                                ?>
                            </div>
                            <?php
                            if ($_GET['page']!='All'){
                                ?>
                                <a href="/competitive-pricing/create?<?=http_build_query($_GET)?>&page=All">Show All Records</a>
                                <nav aria-label="..." class="remove_when_pagination_used">
                                    <ul class="pagination pull-right">
                                        <?php
                                        if($page>1){
                                            ?>
                                            <li class="page-item">
                                                <a class="page-link" href="/competitive-pricing/create?<?=http_build_query($_GET).'&page='.$prevId?>'" tabindex="-1">Previous</a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        $page_counter_no = $page;
                                        for ( $i=$reverse;$i<=$forward;$i++ ){
                                            ?>
                                            <li class="page-item <?php if( $i==$page ){echo 'active';} ?>"><a class="page-link" href="/competitive-pricing/create?<?=http_build_query($_GET).'&page='.$i?>"><?=$i?></a></li>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if($forwardou){
                                            ?>
                                            <li class="page-item">
                                                <a class="page-link" href="/competitive-pricing/create?<?=http_build_query($_GET).'&page='.$forId?>'">Next</a>
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
    <style>



        /*thead*/



        /*tbody*/


        .tbdyTd {  /*the first cell in each tr*/
            position: relative;
            display: block; /*seperates the first column from the tbody*/
            height: 65px;
            background-color: white;
        }

    </style>
<?php
$this->registerJsFile('/monster-admin/js/competitive-pricing.js', [\yii\web\View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
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