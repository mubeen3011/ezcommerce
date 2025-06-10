<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/21/2018
 * Time: 3:38 PM
 */

/*$arrayLabels = ['T1'=>'T1','T2'=>'T2','selling_status'=>'Status','cost_price'=>'Cost','tnc'=>'12 NC','name'=>'Deal','target'=>'Target','total_deal_target'=>'Total Deal Target','Current_Stocks'=>'Current Stocks','OverAllTarget'=>'$(T1 - currentStock) + T2 + total deal'];*/
?>
<div class="row">
    <div class="col-md-4">
        <h3>Basic Info</h3>
        <table class="table-striped table">
            <tbody>
            <?php
            foreach ($info['basicInfo'] as $key=>$value){
                ?>
                <tr>
                    <td><?=ucwords(str_replace('_',' ',$key))?></td>
                    <td><?=$value?></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <h3>Deal Info</h3>
        <table class="table-striped table">
            <tbody>
            <?php
            if ( isset($info['dealInfo']) ){
                foreach ( $info['dealInfo'] as $key=>$value){
                    echo "<tr>";

                    foreach ( $value as $key1=>$value1 ){
                        ?>

                            <td><?=ucwords(str_replace('_',' ',$key1))?></td>
                            <td><?=$value1?></td>

                        <?php
                    }
                    ?>
                    </tr>
                    <?php
                }
            }else{
                ?>
                <tr>
                    <td>No Deal Found</td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <h3>Calculations</h3>

        <table class="table-striped table">
            <tbody>
            <?php
            foreach ( $info['thresholds'] as $key=>$value ) {
                ?>
                <tr>
                    <td><?=ucwords(str_replace('_',' ',$key))?></td>
                    <td><?=$value?></td>
                </tr>
            <?php
            }
            ?>
            <?php
            foreach ( $info['calculationInfo'] as $key=>$value){
                ?>
                <tr>
                    <td><?=ucwords(str_replace('_',' ',$key))?></td>
                    <td><?=$value?></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
