<table style="width: 100%;border: 1px solid black" cellpadding="0" cellspacing="0">
    <thead>
    <tr>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Cust Purchase order</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">(Customer number) Sold to</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Material</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Quantity</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">(Customer number) Ship to</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Requested delivery date</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Cost</td>
        <td style="border: 1px solid black;text-align: center;background-color: #acacac">Sku</td>
    </tr>
    </thead>
    <tbody>
    <?php
    $qtyNum = $csNum =  0;

    if (isset($pod['FOC'])):
        foreach ($pod['FOC'] as $p):
            foreach ( $p as $value1 ):
                if($value1['final_order_qty'] == '0')
                    continue;
                $qty = ($value1['final_order_qty'] != '') ? $value1['final_order_qty'] : $value1['order_qty'];
                $qtyNum = $qtyNum + $qty;
                if ( isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00  ){
                    $csNum = $csNum + ((int)$qty * $value1['bundle_cost']);
                }else{
                    $csNum = $csNum + ((int)$qty * $value1['cost_price']);
                }

                $tp  = (int)$qty * number_format($value1['cost_price'],2,'.','');

                $name = $value1['name'];

                ?>
                <tr>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['po_code'] ?></td>

                    <td style="border: 1px solid black;text-align: center"><?= $value1['sold_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= "&nbsp;".$value1['p_unique_code'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $qty ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['ship_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center">
                        <?php
                        if ($value1['po_finalize_date']!='false'){
                            $date = date('Y-m-d',strtotime($value1['po_finalize_date']));
                            $date = str_replace('-','',$date);
                            echo $date;
                        }
                        ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center">
                        <?= (isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00) ? number_format($value1['bundle_cost'],2) : number_format($value1['cost_price'],2) ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center"><?=$value1['sku']?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php

    if (isset($pod['VB'])):
        foreach ($pod['VB'] as $p):
            foreach ( $p as $value1 ):
                if($value1['final_order_qty'] == '0')
                    continue;
                $qty = ($value1['final_order_qty'] != '') ? $value1['final_order_qty'] : $value1['order_qty'];
                $qtyNum = $qtyNum + $qty;
                if (isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00){
                    $csNum = $csNum + ((int)$qty * $value1['bundle_cost']);
                }else{
                    $csNum = $csNum + ((int)$qty * $value1['cost_price']);
                }
                $tp  = (int)$qty * number_format($value1['cost_price'],2,'.','');

                $name = $value1['name'];

                ?>
                <tr>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['po_code'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['sold_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= "&nbsp;".$value1['p_unique_code'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $qty ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['ship_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center">
                        <?php
                        if ($value1['po_finalize_date']!='false'){
                            $date = date('Y-m-d',strtotime($value1['po_finalize_date']));
                            $date = str_replace('-','',$date);
                            echo $date;
                        }
                        ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center">
                        <?= (isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00) ? number_format($value1['bundle_cost'],2) : number_format($value1['cost_price'],2) ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center"><?=$value1['sku']?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    if (isset($pod['FB'])):
        foreach ($pod['FB'] as $p):
            $counter=0;
            foreach ($p as $value1):
                if($value1['final_order_qty'] == '0')
                    continue;
                $qty = ($value1['final_order_qty'] != '') ? $value1['final_order_qty'] : $value1['order_qty'];
                $qtyNum = $qtyNum + $qty;
                if ($counter==0){
                    if (isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00){
                        $csNum = $csNum + ((int)$qty * $value1['bundle_cost']);
                    }else{
                        $csNum = $csNum + ((int)$qty * $value1['cost_price']);
                    }
                }

                $tp  = (int)$qty * number_format($value1['cost_price'],2,'.','');

                $name = $value1['name'];

                ?>
                <tr>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['po_code'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['sold_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= "&nbsp;".$value1['p_unique_code'] ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $qty ?></td>
                    <td style="border: 1px solid black;text-align: center"><?= $value1['ship_to'] ?></td>
                    <td style="border: 1px solid black;text-align: center">
                        <?php
                        if ($value1['po_finalize_date']!='false'){
                            $date = date('Y-m-d',strtotime($value1['po_finalize_date']));
                            $date = str_replace('-','',$date);
                            echo $date;
                        }
                        ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center">
                        <?= (isset($value1['bundle_cost']) && $value1['cost_price'] != 0.00) ? number_format($value1['bundle_cost'],2) : number_format($value1['cost_price'],2) ?>
                    </td>
                    <td style="border: 1px solid black;text-align: center"><?=$value1['sku']?></td>
                </tr>
                <?php $counter++; endforeach;

            ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <tr><td></td><td></td><td></td><td></td><td style="border: 1px solid black;text-align: center">Sub total</td><td style="border: 1px solid black;text-align: center">Quantity : <?=$qtyNum?></td><td  style="border: 1px solid black;text-align: center"><?= number_format($csNum,2) ?></td></tr>
    <!--<tr><td></td><td></td><td></td><td style="border: 1px solid black;text-align: center">6% GST</td><td></td><td style="border: 1px solid black;text-align: center"><?/*= ($csNum) * 0.06 */?></td></tr>-->
    <!--<tr><td></td><td></td><td></td><td style="border: 1px solid black;text-align: center">Grand total</td><td style="border: 1px solid black;text-align: center"></td><td  style="border: 1px solid black;text-align: center"><?/*= number_format(($csNum) * 0.06 + (float)$csNum,2) */?></td></tr>-->
    <tr><td></td><td></td><td></td><td></td><td style="border: 1px solid black;text-align: center">Grand total</td><td style="border: 1px solid black;text-align: center"></td><td  style="border: 1px solid black;text-align: center"><?= number_format((float)$csNum,2) ?></td></tr>
    </tbody>
</table>