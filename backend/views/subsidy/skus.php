<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 10/19/2017
 * Time: 10:02 AM
 */

use common\models\User;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['user/generic']];
$this->params['breadcrumbs'][] = 'SKU Subsidy';
$channelList = \backend\util\HelpUtil::getChannels();
$users = User::find()->select(['id', 'full_name'])->where(['role_id' => 2])->asArray()->all();
$usersList = ArrayHelper::map($users, 'id', 'full_name');
$channelList = \backend\util\HelpUtil::getChannels();
$users = User::find()->select(['id', 'full_name'])->where(['role_id' => 2])->asArray()->all();
$usersList = ArrayHelper::map($users, 'id', 'full_name');
/*echo '<pre>';
print_r($channelList);
die;*/
if (  count($_GET) > 1 ){
    $clear_filter=1;
}else{
    $clear_filter=0;
}
?>
<style>
    .filters-visible{
        display: none;
    }
    #w0,#w2,#w1{
        width:150px;
    }
    .numc{
        width:100px;
    }
    pre{
        display:none;
    }


</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3>
                    SKU Subsidy
                </h3>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <div class="dropdown pull-right m-r-10 hidden-sm-down" style="margin-bottom: 10px;">
                    <!--<button style="margin-right: 5px" class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Add Columns
                    </button>-->
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 37px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <!--<table>
                            <tbody>
                                <tr>
                                    <td>Lazada</td>
                                    <td><input type="checkbox"></td>
                                </tr>
                                <tr>
                                    <td>11Street</td>
                                    <td><input type="checkbox"></td>
                                </tr>
                            </tbody>
                        </table>-->
                        <?php foreach ($channelList as $cl): if ($cl['id'] == 1) continue; ?>
                                <label for="default-<?= $cl['id'] ?>" style="    margin: 2px;
    width: 200px;" class=" col-md-12">
                                    <?= $cl['name'] ?>
                                    <input type="checkbox" id="default-<?= $cl['id'] ?>" name="<?= $cl['name'] ?>" class="badgebox " data-class="chl-<?= $cl['name'] ?>" value="1">
                                    <span class="badge">&check;</span>
                                </label>
                        <?php endforeach; ?>
                        <!--<a class="dropdown-item" href="#">February 2017</a>
                        <a class="dropdown-item" href="#">March 2017</a>
                        <a class="dropdown-item" href="#">April 2017</a>-->
                    </div>
                    <!--<div style="font-size: 20px;color: #009efb;" class="pull-right">
                        <i class="fa fa-filter model_img img-responsive" alt="default" data-toggle="modal" data-target="#myModal"></i>
                    </div>-->
                    <button type="button" id="export-table-csv" class=" btn btn-info">
                        <i class="fa fa-download"></i> Export
                    </button>
                    <button type="button" class=" btn btn-info" id="filters">
                        <i class="fa fa-filter"></i>
                    </button>
                    <?php
                    if ($clear_filter)
                    {
                        ?>
                        <a href="/subsidy/skus?&page=1" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                        <?php
                    }
                    ?>
                    <!--<button type="button" class="btn btn-info pull-right" style="margin-right: -9px;margin-bottom: 10px" alt="default" data-toggle="modal" data-target="#myModal">
                        <i class="fa fa-filter"></i> Advance Filter
                    </button>-->
                </div>

                <form id="filter" class="subsidy-filter" action="/subsidy/assign" method="post">
                    <input name="_csrf-backend"
                           value="NX5TpdNbDeLIieDVIOfJFmfYCIxGvPY1V5219dRunhqi0e03h82bfnbrJjL0Gx18eC76NfuPK1TSD8yoG_BYLA=="
                           type="hidden">
                    <input name="users_skus" class="users_skus" type="hidden">
                    <!--<div class="form-group field-sku_id required has-success">
                        <?/*= Html::dropDownList('user', '', $usersList, ['prompt' => 'Assign to User', 'class' => 'form-control']) */?>

                    </div>-->
                </form>
                <div class="pricing-index">

                    <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                        <!--<tr>
                            <th class="tg-kr94  " style="border-left: 0px solid black !important;border-top: 0px solid black !important" colspan="6">SKUs</th>
                            <?php /*foreach ($channelList as $cl): */?>
                                <th class="tg-kr94  chl-<?/*= $cl['name'] */?>" colspan="5" id="<?/*= $cl['name'] */?>">
                                    <?/*= $cl['name'] */?>
                                </th>
                            <?php /*endforeach; */?>

                        </tr>-->
                        <form>
                        <tr>
                            <!--<th class="tg-kr94" style="width: 10%"><input type="checkbox" name="all" value="all" class="chall"> ALL</th>-->
                            <th class="min-th-width tg-kr94  " data-tablesaw-priority="persist" style="border-left: 0px !important;">SKU <br />
                                <input type="text" value="<?=isset($_GET['sku']) ? $_GET['sku'] : '' ?>" class="form-control filters-visible inputs-margin " name="sku">
                                <input type="hidden" value="1" style="display: none;" name="page">
                                <input type="submit" style="display: none;" />
                            </th>
                            <th class="min-th-width tg-kr94  " scope="col" data-tablesaw-priority="1">Category <br />
                                <select name="category" class="form-control filters-visible inputs-margin " id="category_list">
                                    <option></option>
                                    <?php
                                    foreach ( $categories as $cat_val ){
                                        ?>
                                        <option value="<?=$cat_val['id']?>" <?php if( isset($_GET['category']) && $cat_val['id'] == $_GET['category']) { echo 'selected'; } ?>><?=$cat_val['name']?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </th>

                            <th class="tg-kr94 min-th-width " scope="col" data-tablesaw-priority="2">Sub Category <br />
                                <select name="sub_category" class="form-control filters-visible inputs-margin " id="sub_categories">

                                </select></th>
                            <th class="tg-kr94 min-th-width " scope="col" data-tablesaw-priority="3">Selling Status <br />
                                <input type="text" value="<?=isset($_GET['selling_status']) ? $_GET['selling_status'] : '' ?>" class="form-control filters-visible inputs-margin " name="selling_status"></th>
                            <th class="tg-kr94  " scope="col" data-tablesaw-priority="4">Assigned</th>
                            <?php
                            $i=4;
                            foreach ($channelList as $cl):
                                ?>
                                <th data-toggle="tooltip" title="<?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) ?> - Subsidy" scope="col" data-tablesaw-priority="<?=++$i?>" class="tg-kr94 min-th-width   chl-<?= $cl['name'] ?>"   id="<?= $cl['name'] ?>">
                                    <?=\backend\util\HelpUtil::getThShorthand( \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) . '- Subsidy')?><br />
                                    <input type="text" data-filter-type="operator" value="<?=isset($_GET['Channels'][$cl['id']]['subsidy']) ? $_GET['Channels'][$cl['id']]['subsidy']: '' ?>" class="form-control filters-visible inputs-margin " name="Channels[<?= $cl['id'] ?>][subsidy]">
                                </th>
                                <th data-toggle="tooltip" title="<?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) ?> - Approved Margins" scope="col" data-tablesaw-priority="<?=++$i?>" class="tg-kr94 min-th-width   chl-<?= $cl['name'] ?>"   id="<?= $cl['name'] ?>">
                                    <?=\backend\util\HelpUtil::getThShorthand(  \backend\util\HelpUtil::ChannelPrefixByName($cl['name'])  . '- Approved Margins')?><br />
                                    <input type="text" data-filter-type="operator" value="<?=isset($_GET['Channels'][$cl['id']]['ao_margins']) ? $_GET['Channels'][$cl['id']]['ao_margins']: '' ?>" class="form-control filters-visible inputs-margin " name="Channels[<?= $cl['id'] ?>][ao_margins]"></th>
                                <th data-toggle="tooltip" title="<?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) ?> - Base Margin" scope="col" data-tablesaw-priority="<?=++$i?>" class="tg-kr94 min-th-width   chl-<?= $cl['name'] ?>"   id="<?= $cl['name'] ?>">
                                    <?=\backend\util\HelpUtil::getThShorthand(  \backend\util\HelpUtil::ChannelPrefixByName($cl['name'])  . '- Base Margin')?><br />
                                    <input type="text" data-filter-type="operator" value="<?=isset($_GET['Channels'][$cl['id']]['margins']) ? $_GET['Channels'][$cl['id']]['margins']: '' ?>" class="form-control filters-visible inputs-margin " name="Channels[<?= $cl['id'] ?>][margins]"></th>
                                <th data-toggle="tooltip" title="<?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) ?> - Start Date" scope="col" data-tablesaw-priority="<?=++$i?>" class="tg-kr94 min-th-width   chl-<?= $cl['name'] ?>"   id="<?= $cl['name'] ?>" >
                                    <?=\backend\util\HelpUtil::getThShorthand(  \backend\util\HelpUtil::ChannelPrefixByName($cl['name'])  . ' - Start Date')?><br />
                                    <input type="text"  class="form-control filters-visible inputs-margin " value="<?=isset($_GET['Channels'][$cl['id']]['start_date']) ? $_GET['Channels'][$cl['id']]['start_date']: '' ?>" name="Channels[<?= $cl['id'] ?>][start_date]"></th>
                                <th data-toggle="tooltip" title="<?= \backend\util\HelpUtil::ChannelPrefixByName($cl['name']) ?> - End Date" scope="col" data-tablesaw-priority="<?=++$i?>" class="tg-kr94 min-th-width   chl-<?= $cl['name'] ?>"   id="<?= $cl['name'] ?>">
                                    <?=\backend\util\HelpUtil::getThShorthand(  \backend\util\HelpUtil::ChannelPrefixByName($cl['name'])  . ' - End Date')?><br />
                                    <input type="text" value="<?=isset($_GET['Channels'][$cl['id']]['end_date']) ? $_GET['Channels'][$cl['id']]['end_date']: '' ?>" class="form-control filters-visible inputs-margin " name="Channels[<?= $cl['id'] ?>][end_date]"></th>
                            <?php endforeach; ?>
                        </tr>
                        </form>
                        </thead>
                        <!--<tfoot>
                        <tr>

                            <th class="tg-kr94">SKU</th>
                            <th class="tg-kr94">Category</th>
                            <th class="tg-kr94">Sub Category</th>
                            <th class="tg-kr94">Selling Status</th>
                            <th class="tg-kr94">Assigned</th>
                            <?php /*foreach ($channelList as $cl): */?>
                                <th class="tg-kr94 chl-<?/*= $cl['name'] */?>" id="<?/*=$cl['name']*/?>">Subsidy</th>
                                <th class="tg-kr94 chl-<?/*= $cl['name'] */?>" id="<?/*=$cl['name']*/?>">Approved<br/>Margins</th>
                                <th class="tg-kr94 chl-<?/*= $cl['name'] */?>" id="<?/*=$cl['name']*/?>">Base<br/>Margins</th>
                                <th class="tg-kr94 chl-<?/*= $cl['name'] */?>" id="<?/*=$cl['name']*/?>"></th>
                                <th class="tg-kr94 chl-<?/*= $cl['name'] */?>" id="<?/*=$cl['name']*/?>"></th>

                            <?php /*endforeach; */?>
                        </tr>
                        </tfoot>-->
                        <tbody>
                        <?php

                        foreach ($skus as $key => $value) :
                            if(isset($value['sku'])):
                                ?>
                                <tr>

                                        <td class="tg-kr94" style="border-left: 0px;">
                                            <a style="color:blue"
                                               href="<?= \yii\helpers\Url::to(['/competitive-pricing/sku-details', 'sku' => $value['sku']]) ?>">
                                                <?= $value['sku'] ?>
                                            </a>
                                        </td>
                                        <td class="tg-kr94"><?= ucwords($value['mc']) ?></td>
                                        <td class="tg-kr94"><?= ucwords($value['c']) ?></td>
                                        <td class="tg-kr94"><?= ucwords($value['ss']) ?></td>
                                        <td class="tg-kr94"><?= isset($value['name']) ? ucwords($value['name']) : 'Not assign' ?></td>
                                        <?php foreach ($channelList as $cl): ?>
                                            <td class="tg-kr944 chl-<?= $cl['name'] ?>" id="<?=$cl['name']?>">
                                                <?= isset($subsidy[$cl['id']][$key]['subsidy']) ? $subsidy[$cl['id']][$key]['subsidy'] : '' ?>
                                            </td>
                                            <td class="tg-kr944 chl-<?= $cl['name'] ?>" id="<?=$cl['name']?>">
                                                <?= isset($subsidy[$cl['id']][$key]['ao_margins']) ? $subsidy[$cl['id']][$key]['ao_margins'] : '' ?>
                                            </td>
                                            <td class="tg-kr944 chl-<?= $cl['name'] ?>" id="<?=$cl['name']?>">
                                                <?= isset($subsidy[$cl['id']][$key]['margins']) ? $subsidy[$cl['id']][$key]['margins'] : '' ?>
                                            </td>
                                            <td class="tg-kr944 chl-<?= $cl['name'] ?>" id="<?=$cl['name']?>">
                                                <?= isset($subsidy[$cl['id']][$key]['start_date']) ? $subsidy[$cl['id']][$key]['start_date'] : '' ?>
                                            </td>
                                            <td class="tg-kr944 chl-<?= $cl['name'] ?>" id="<?=$cl['name']?>">
                                                <?= isset($subsidy[$cl['id']][$key]['end_date']) ? $subsidy[$cl['id']][$key]['end_date'] : '' ?>
                                            </td>

                                        <?php endforeach; ?>
                                </tr>
                                <?php
                            endif;
                        endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <nav aria-label="..." class="remove_when_pagination_used">
                    <ul class="pagination pull-right">
                        <?php
                        $Totalrec = ceil($total_records/100);
                        //echo $total_records;die;
                            $page = $_GET['page'];
                            $reverse = $page - 3;
                            $previsou=0;
                            if( $reverse < 1 ){
                                $reverse = 1;
                            }
                            if( $_GET['page']>=2 ){
                                $prevId=$reverse;
                                $prevId = $_GET['page']-1;
                                $previsou = 1;
                            }
                            $forward = $page + 3;
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

                            //echo $previsou;die;
                        if($previsou){
                                ?>
                            <!--<a href="/subsidy/skus?<?/*=http_build_query($_GET).'&page='.$prevId*/?>'" class="paginate_button next" aria-controls="example23" data-dt-idx="7" tabindex="0" id="example23_next">
                                Prev
                            </a>-->
                            <li class="page-item paginate_button">
                                <a class="page-link" href="/subsidy/skus?<?=http_build_query($_GET).'&page='.$prevId?>'" tabindex="-1">Previous</a>
                            </li>
                            <?php
                        }
                            for ( $i=$reverse;$i<=$forward;$i++ ){
                                //echo $i;
                                //echo '<br />';
                                ?>
                                <li class="page-item <?php if( $i==$_GET['page'] ){echo 'active';} ?>">
                                    <a class="page-link" href="/subsidy/skus?<?=http_build_query($_GET).'&page='.$i?>">
                                        <?=$i?>
                                    </a>
                                </li>
                        <?php
                            }
                        if($forwardou){
                            ?>
                            <li class="page-item">
                                <a class="page-link" href="/subsidy/skus?<?=http_build_query($_GET).'&page='.$forId?>'">Next</a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </nav>


                </div>
            </div>
        </div>
    </div>
</div>

    <div class="form-group Columns required has-success"><div class="help-block"></div>
    </div>
<?php
$this->registerJs("
    $('#category_list').change(function(){
        var category_id=$('#category_list').val();
        $.ajax({
                type: 'GET',
                url: '/subsidy/get-all-sub-category',
                data:  'catid='+category_id,
                dataType: 'html',
                success: function(msg)
                {
                                 $('#sub_categories').html(msg);
                },
                beforeSend: function()
                {
                }
            });
    });
    /*! Tablesaw - v2.0.3 - 2016-05-02
* https://github.com/filamentgroup/tablesaw
* Copyright (c) 2016 Filament Group; Licensed MIT */
;(function( $ ) {

	// DOM-ready auto-init of plugins.
	// Many plugins bind to an \"enhance\" event to init themselves on dom ready, or when new markup is inserted into the DOM
	$( function(){
		$( document ).trigger( \"enhance.tablesaw\" );
	});

})( jQuery );
$('#submit-filters').click(function(){
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
})
");
$this->registerJs("
$('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass(\"filters-visible\");
        if( hasClass ){
            $('.inputs-margin').removeClass(\"filters-visible\");
        }else{
            $('.inputs-margin').addClass(\"filters-visible\");
        }

    });
");
