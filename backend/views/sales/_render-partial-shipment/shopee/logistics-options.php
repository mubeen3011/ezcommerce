<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/31/2020
 * Time: 11:22 AM
 */
?>
<?php
if (!isset($availableLogisticsOptions->pickup)){
    ?>
    <style>
        .shopee-order-dropoff:hover {
             opacity: 1;
             filter: alpha(opacity=50); /* For IE8 and earlier */
         }
        .shopee-order-dropoff {
            cursor: unset !important; /* For IE8 and earlier */
        }
    </style>
    <?php
}
?>
<div class="row el-element-overlay">
    <div class="col-lg-3" ></div>
    <?php
    if ( isset($availableLogisticsOptions->dropoff) ){

        if (isset($availableLogisticsOptions->pickup)){
            $col = 'col-lg-3';
        }else{
            $col = 'col-lg-6';
        }
        ?>
        <div class="<?=$col?> col-md-6 shopee-order-dropoff" style="cursor: pointer;background-color: #FAFAFA;padding: 20px;height: <?=(isset($availableLogisticsOptions->pickup)) ? '220px' : 'auto'?>;;margin-right: 10px;">
            <div class="el-card-item">
                <center>
                    <i class="fa fa-shopping-cart" style="font-size: 50px;color: #78C257;margin-bottom: 15%;"></i>
                </center>
                <div class="el-card-content">
                    <h4 class="box-title">I will dropoff</h4>
                    <p style="color: #8c8c8c;">You can drop off your parcel at any Poslaju branch</p>
                    <br>

                </div>
                <div style="text-align: center;" class="show-list-of-states <?=(isset($availableLogisticsOptions->pickup)) ? 'hide' : ''?>">
                    <a href="javascript: void 0" data-channel-id="<?=$channel_id?>" data-order-id="<?=$order_id?>" class="shopee-states-list" style="font-size: 10px;">
                        View list of Poslaju branches
                    </a>
                </div>
                <div style="text-align:center;margin-top: 15px;" class="dropoff-confirm-div <?=(isset($availableLogisticsOptions->pickup)) ? 'hide' : ''?>">
                    <button type="button" class=" btn btn-warning shopee-confirm-dropoff-button" style="text-align:center;">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    <?php
    }
    if ( isset($availableLogisticsOptions->pickup) ){
        ?>
        <div class="col-lg-3 col-md-6 shopee-order-pickup" data-order-id="<?=$order_id?>" data-channel-id="<?=$channel_id?>" style="background-color: #FAFAFA;padding: 20px;height: 220px;margin-left: 10px;cursor: pointer;">
            <div class="el-card-item">
                <center>
                    <i class="fa fa-truck" style="font-size: 50px;color: darkorange;margin-bottom: 15%;"></i>
                </center>
                <div class="el-card-content">
                    <h4 class="box-title">I will arrange pickup</h4>
                    <p  style="color: #8c8c8c;">Poslaju will collect parcel from your pickup address</p>
                    <br>
                </div>
            </div>
        </div>
    <?php
    }
    ?>
</div>

<?php
if (!isset($availableLogisticsOptions->pickup)){
    ?>
    <script>
        $(document).ready(function () {
            $('.shopee-order-dropoff:hover').css({'opacity' : '1'})
        });
        </script>
<?php
}