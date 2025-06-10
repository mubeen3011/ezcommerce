<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/28/2020
 * Time: 11:55 AM
 */
?>
<tr class="more-information-<?=$skuId?>">
    <td colspan="11">
        <div class="row">
            <div class="col-lg-4 col-sm-12">
                <div class="card" style="margin-bottom: 3px">
                    <div class="card-body dt-widgets" style="height: 408px;">

                        <div class="table-responsive">
                            <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                <tr>
                                    <td class='dl-more-td'><label>Actual Cost</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['actual_cost']?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='dl-more-td'><label>Extra Cost</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['extra_cost']?></strong>
                                    </td>
                                </tr>
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
                                <tr>
                                    <td class='dl-more-td'><label>Commission</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['commission']?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='dl-more-td'><label>Shipping</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['shipping']?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='dl-more-td'><label>Gross Profit</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['gross_profit']?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='dl-more-td'><label>Price w/ subsidy</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['price_after_subsidy']?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='dl-more-td'><label>Customer Pays</label></td>
                                    <td class='dl-more-td'>
                                        <strong><?=$data['customer_pays']?></strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-12">
                <div class="card" style="margin-bottom: 3px">
                    <div class="card-body dt-widgets" style="height: 408px;">
                        <div class="table-responsive" style="height: 100%;overflow-x: auto;">
                            <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                <tbody>
                                <?php
                                foreach ( $data['stocks'] as $wname=>$stocks ){
                                    ?>
                                    <tr>
                                        <td class="dl-more-td">
                                            <label><?=ucwords(str_replace('_',' ',$wname))?></label>
                                        </td>
                                        <td class="dl-more-td">
                                            <strong>
                                                <?php
                                                if ( isset($stocks['message']) && $stocks['message']!='' )
                                                {
                                                    echo $stocks['message'];
                                                }else{
                                                    echo $stocks;
                                                }
                                                ?>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>


    </td>
</tr>
