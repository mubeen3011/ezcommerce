<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 1/9/2019
 * Time: 5:39 PM
 */
$reasons = ['Competitor Top' => 'Competitor Top', 'Focus SKUs' => 'Focus SKUs', 'Philips Campaign' => 'Philips Campaign',
    'Flash Sale' => 'Flash Sale', 'Shocking Deal' => 'Shocking Deal', 'Aging Stocks' => 'Aging Stocks', 'EOL' => 'EOL',
    'Competitive Pricing' => 'Competitive Pricing', 'Outright' => 'Outright', 'Others' => 'Others'];
foreach ($skus as $sku):
    $k = $sku['sku_id'];
    $trClass='';
    if ((in_array( str_replace('s_', '', $k),$additionalSkus ))){
        $trClass='line-item-row';
    }else{
        $trClass='category-added category-row-';
        if (isset($sku['form_cat'])){
            $trClass.=$sku['form_cat'];
        }
    }
    ?>
    <tr class='row-<?= str_replace('s_', '', $k) ?> <?=$trClass?>'>
        <td class="footable-first-column">
            <a style="color: #54667a;" href='javascript:;'
               data-sku-id='<?= str_replace('s_', '', $k) ?>' class='dm-more up'>
                <i class='mdi mdi-plus-circle-outline' data-toggle="tooltip" title="More info"
                   style="font-size: 20px"></i>
            </a>
        </td>
        <td class="tbody-td-padding td-sku-id"><input style="width: auto" type='text'
                                            data-sku-id='<?= str_replace('s_', '', $k) ?>'
                                            class='skunames form-control form-control-sm' name='DM[s_<?= $k ?>][sku]'
                                            readonly value='<?=$sku['sku']?>'>
            <?php
            if (isset($sku['form_cat'])){
                ?>
                <input type="hidden" name='DM[s_<?= $k ?>][form_cat]' value="<?=$sku['form_cat']?>">
                <?php
            }
            ?>
        </td>

        <td class="tbody-td-padding td-sku-price input-text"><input type='text' data-sku-id='<?= $k ?>'
                                                       <?=($discount_type=='Percentage') ? 'readonly' : ''?>
                                                       class='form-control list-sku-price form-control-sm deal-td-width' required
                                                       name='DM[s_<?= $k ?>][price]'
                                                       value='<?=(isset($sku['deal_price'])) ? $sku['deal_price'] : ''?>'></td>

        <td class="tbody-td-padding td-sku-subsidy input-text"><input type='text' data-sku-id='<?= $k ?>'
                                                       class=' form-control list-sku-subsidy form-control-sm deal-td-width' 
                                                       name='DM[s_<?= $k ?>][subsidy]'
                                                       value='<?=(isset($sku['subsidy'])) ? $sku['subsidy'] : ''?>'></td>
        <td class="td-sku-stock"><input readonly type='text' class='form-control form-control-sm deal-td-width'
                                        name='DM[s_<?= $k ?>][stock]'
                   value="<?=(isset($sku['total_stocks'])) ? $sku['total_stocks'] : ''?>" ></td>
        <td class="tbody-td-padding input-text td-sku-target"><input type='text' data-sku-id='<?= $k ?>'
                                                       class='form-control form-control-sm deal-td-width' 
                                                       name='DM[s_<?= $k ?>][qty]'
                                                       value='<?=(isset($sku['sales_target'])) ? $sku['sales_target'] : ''?>'></td>

        <td class="tbody-td-padding input-text td-sku-margin-percentage"><input type='text' data-sku-id='<?= $k ?>' readonly
                                                       class='form-control form-control-sm deal-td-width'
                                                       name='DM[s_<?= $k ?>][margin_per]' 
                                                       value='<?=(isset($sku['margin_percentage'])) ? $sku['margin_percentage'] : ''?>'></td>
        <td class="tbody-td-padding input-text td-sku-margin-amount"><input type='text' data-sku-id='<?= $k ?>' readonly
                                                       class='form-control form-control-sm deal-td-width'
                                                       name='DM[s_<?= $k ?>][margin_rm]' 
                                                       value='<?=(isset($sku['sales_margins_rm'])) ? $sku['sales_margins_rm'] : ''?>'></td>
        <td class="tbody-td-padding"><select class='form-control form-control-sm' style="width: 150px;"
                                             name='DM[s_<?= $k ?>][reason]'>
                <?php foreach ($reasons as $kx => $r):?>
                    <option value="<?= $kx ?>"><?= $r ?></option>
                <?php endforeach; ?>
            </select></td>


        <td class="tbody-td-padding">
            <a href='javascript:;' data-sku-id='<?= str_replace('s_', '', $k) ?>'
               class='dm-delete'>
                <i class='glyph-icon icon-trash' style='font-size:20px;color: red;'></i></a>
        </td>
    </tr>
<?php endforeach; ?>