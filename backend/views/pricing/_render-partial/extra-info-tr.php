<?php
$channelList = \backend\util\HelpUtil::getChannels();

?>

<tr>
    <td colspan='<?=5* (( count($user_columns) == 1 ) ? 2 : count($user_columns)) ?>' style='background-color: #f5f5f5;'>
        <div class='row'>
            <?php foreach ($skus as $key => $value) : ?>
                <?php foreach ($channelList as $cl): ?>
                <?php if( !in_array($cl['id'],$user_columns) ){ continue; } ?>
                <div class='col-lg-3 col-sm-12'>
                    <div class='card' style='margin-bottom: 3px'>
                        <div class='card-body dt-widgets' style='height: 408px;'>
                            <div class='table-responsive'>
                                <table class='display nowrap table table-hover table-striped table-bordered dataTable no-footer'>
                                    <tr><td class='dl-more-td' colspan="2"><h4><?=$cl['name']?></h4></td></tr>
                                    <tr><td class='dl-more-td'><label  >Margins at Lowest Price %</label></td><td class='dl-more-td'><strong><?=isset($channelSkuList[$cl['id']][$key]['margins_low_price']) ? $channelSkuList[$cl['id']][$key]['margins_low_price'] : ''?></strong></td></tr>
                                    <tr><td class='dl-more-td'><label  >Margins at Sale Price %</label></td><td class='dl-more-td'><strong><?=isset($channelSkuList[$cl['id']][$key]['margin_sale_price']) ? $channelSkuList[$cl['id']][$key]['margin_sale_price'] : ''?></strong></td></tr>
                                    <tr><td class='dl-more-td'><label  >Loss / Profit in RM</label></td><td class='dl-more-td'><strong><?=isset($channelSkuList[$cl['id']][$key]['loss_profit_rm']) ? $channelSkuList[$cl['id']][$key]['loss_profit_rm'] : ''?></strong></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
    <!--<td class="tg-kr94 chl-<?/*= $cl['name'] */?>"><?/*= isset($channelSkuList[$cl['id']][$key]['margins_low_price']) ? $channelSkuList[$cl['id']][$key]['margins_low_price'] : '' */?></td>
    <td class="tg-kr94 chl-<?/*= $cl['name'] */?>"><?/*= isset($channelSkuList[$cl['id']][$key]['margin_sale_price']) ? $channelSkuList[$cl['id']][$key]['margin_sale_price'] : '' */?></td>
    <td class="tg-kr94 chl-<?/*= $cl['name'] */?>"><?/*= isset($channelSkuList[$cl['id']][$key]['loss_profit_rm']) ? $channelSkuList[$cl['id']][$key]['loss_profit_rm'] : '' */?></td>-->
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </td>
</tr>