<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 7/11/2019
 * Time: 8:28 PM
 */
if ($_GET['shop']==1){
    $channelname = 'Lazada Blip';
}else if ( $_GET['shop'] == 2 ){
    $channelname = 'Shopee Blip';
}else if ( $_GET['shop'] == 15 ){
    $channelname = 'Lazada Avent';
}
/*echo '<pre>';
print_r($stats);
die;*/
?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="button-group">
                        <form action="" id="shop-dropdown">
                            <select name="shop" class="form-control custom-select shops">
                                <option value="15" <?=($_GET['shop']==15) ? 'selected' : ''?>>Lazada Avent</option>
                                <option value="1" <?=($_GET['shop']==1) ? 'selected' : ''?>>Lazada</option>
                                <option value="2" <?=($_GET['shop']==2) ? 'selected' : ''?>>Shopee</option>
                            </select>
                        </form>
                    </div>

                    <br />
                    <!-- .row -->
                    <div class="row">

                        <div class="col-md-12">
                            <h3 class="box-title">Campaign Stats - <?=$channelname?></h3>
                                <p>
                                    Last Sync ISIS   : <span style="font-weight: bold"><?=\backend\util\DealsUtil::TimeAgo(date('M-d H:i:s',$isis_sync))?></span><br />
                                    Last Sync Shopee : <span style="font-weight: bold"><?=\backend\util\DealsUtil::TimeAgo($shopee->end_time)?><br /></span>
                                    Last Sync Lazada : <span style="font-weight: bold"><?=\backend\util\DealsUtil::TimeAgo($lazada->end_time)?><br /></span>
                                    Last Sync Avent  : <span style="font-weight: bold"><?=\backend\util\DealsUtil::TimeAgo($Avent->end_time)?></span>
                                </p>

                            <br/><br/><br/><br/><br/>
                            <table id="campaign-stats" class="table table-bordered">
                                <thead>
                                <th>Sku</th>
                                <th>Isis Available</th>
                                <th>Shop Availble (ISIS)</th>
                                <th>Difference</th>
                                <th>Selling status</th>
                                <th>Orders</th>
                                <td>Total Orders (All Statuses)</td>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($stats as $key=>$Value){

                                    if (isset($Value['shop_available']) && isset($Value['isis_available']) && isset($Value['orders'])){

                                        ?>
                                        <tr>
                                            <td><?=$Value['sku']?></td>
                                            <td><?=$Value['isis_available']?></td>
                                            <td><?=$Value['shop_available']?></td>
                                            <td><?=(integer) $Value['isis_available']- (integer) $Value['shop_available']?></td>
                                            <td><?=$Value['selling_status']?></td>
                                            <td>
                                                <?php
                                                $orders = json_decode($Value['orders']);
                                                foreach ( $orders as $k=>$v ){
                                                    echo $k.' : '.$v;
                                                    echo '<br />';
                                                    ?>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $total_orders = 0;
                                                foreach ( $orders as $no ){
                                                    $total_orders += $no;
                                                }
                                                echo $total_orders;
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
            </div>
        </div>
    </div>

<?php
$this->registerJs('
$("#campaign-stats").DataTable( {
} );
$(".shops").change(function(){
    $("#shop-dropdown").submit();
})', \yii\web\View::POS_END);