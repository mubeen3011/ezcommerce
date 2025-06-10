<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/7/2020
 * Time: 12:54 PM
 */
?>
<style>
    .daterangepicker{
        z-index: 1200 !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <div id="displayBox" style="display: none;">
            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div id="displayBox" style="display: none;">
                <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
            </div>
            <div class="card-body" style="overflow-x:auto; overflow-y: auto">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='Finance Report'?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <form action="">
                    <div class="row">

                        <table>
                            <tbody>
                            <tr style="margin-left: 50%;float: left;">
                                <td>
                                    <select name="channel" class="form-control-lg">
                                        <?php foreach ( $channels as $value ):?>
                                            <option <?=(isset($_GET['channel']) && $value['id']==$_GET['channel']) ? 'selected' : ''?> value="<?=$value['id']?>">
                                                <?=$value['name']?>
                                            </option>
                                        <?php endforeach;?>
                                    </select>
                                </td>
                                <td>
                                    <input readonly class="form-control-lg input-daterange-datepicker" autocomplete="off" value="<?=isset($_GET['Date_range']) ? $_GET['Date_range'] : '' ?>" type="text" name="Date_range" />
                                    <input type="hidden" name="page" value="1" />
                                </td>
                                <td>
                                    <input type="submit" style="line-height: 1.1;" class="btn btn-lg btn-success" id="submit_report" value="Get Report">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <!--<div class="col-md-2 col-sm-12"></div>

                        <div class="col-md-2 col-sm-12">
                            <select name="channel" class="form-control-lg">
                                <?php /*foreach ( $channels as $value ):*/?>
                                    <option <?/*=(isset($_GET['channel']) && $value['id']==$_GET['channel']) ? 'selected' : ''*/?> value="<?/*=$value['id']*/?>">
                                        <?/*=$value['name']*/?>
                                    </option>
                                <?php /*endforeach;*/?>
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-12">
                            <input readonly class="form-control-lg input-daterange-datepicker" autocomplete="off" value="<?/*=isset($_GET['Date_range']) ? $_GET['Date_range'] : '' */?>" type="text" name="Date_range" />
                            <input type="hidden" name="page" value="1" />
                        </div>

                        <div class="col-md-2 col-sm-12">
                            <input type="submit" style="line-height: 1.1;" class="btn btn-lg btn-success" id="submit_report" value="Get Report">
                        </div>

                        <div class="col-md-3 col-sm-12"></div>
-->
                    </div>
                </form>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <div id="example23_filter" class="dataTables_filter">
                    <a class=" btn btn-info" style="color: white;" href="/finance/finance-export?<?=http_build_query($_GET)?>"><i class="fa fa-download"></i> Export</a>
                </div>
                <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe">
                    <?php
                    if ( empty($report) ){
                        ?>
                        <thead>
                        <tr>
                            <th style="text-align: center; vertical-align: middle;">Sorry No record found</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">Sorry No record found</td>
                        </tr>
                        </tbody>
                        <?php
                    }
                    else{
                        ?>
                        <thead>
                        <tr>
                            <?php
                            foreach ( $report[0] as $colHeading=>$v ):
                                if ( $colHeading=='order_create_date' || $colHeading=='order_item_id' ): ?>

                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist"><?=str_replace('_',' ',$colHeading)?></th>

                                <?php elseif ($colHeading=='commission_amount'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Expected Commission (5%)"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php elseif ($colHeading=='transaction_fee'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Expected Transaction Fee ( 2% )"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php elseif ($colHeading=='fbl_fee'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Expected Fbl Fee ( 2 MYR )"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php elseif ($colHeading=='expected_receive_amount'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Paid Price - ( Commission + TransactionFee + FBL Fee )"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php elseif ($colHeading=='total_feeses'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Sum of all feeses"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php elseif ($colHeading=='receiving_difference'):?>
                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <i class="mdi mdi-information" title="Expected Recieving - Total feeses"></i>
                                        <!--<abbr title="(Expected Commission 5%)">Rating</abbr>-->
                                    </th>
                                <?php else: ?>

                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">
                                        <?=str_replace('_',' ',$colHeading)?>
                                        <!--<abbr title="Rotten Tomato Rating">Rating</abbr>-->
                                    </th>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ( $report as $key=>$detail ): ?>
                            <tr>
                                <?php foreach ( $detail as $colName=>$value ): ?>
                                    <td><?=$value?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                        <?php
                    }
                    ?>
                </table>
                <?php if ($_GET['page']!='All'):?>
                    <nav aria-label="..." class="remove_when_pagination_used">

                        <ul class="pagination pull-right">
                            <?php
                            $Totalrec = ceil($total_records/10);

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
                            if($previsou){
                                ?>

                                <li class="page-item paginate_button">
                                    <a class="page-link" href="/finance/audit?<?=http_build_query($_GET).'&page='.$prevId?>'" tabindex="-1">Previous</a>
                                </li>
                                <?php
                            }
                            for ( $i=$reverse;$i<=$forward;$i++ ){
                                ?>
                                <li class="page-item <?php if( $i==$_GET['page'] ){echo 'active';} ?>">
                                    <a class="page-link" href="/finance/audit?<?=http_build_query($_GET).'&page='.$i?>">
                                        <?=$i?>
                                    </a>
                                </li>
                                <?php
                            }
                            if($forwardou){
                                ?>
                                <li class="page-item">
                                    <a class="page-link" href="/finance/audit?<?=http_build_query($_GET).'&page='.$forId?>">Next</a>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                        if ( $total_records < 2000 ){
                            unset($_GET['page']);
                            ?>
                            <a href="/finance/audit?<?=http_build_query($_GET).'&page=All'?>">Show All <?=$total_records?> Records</a>
                            <?php
                        }else{
                            ?>
                            <a>Total Records <?=$total_records?></a>
                            <?php
                        }
                        ?>

                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

