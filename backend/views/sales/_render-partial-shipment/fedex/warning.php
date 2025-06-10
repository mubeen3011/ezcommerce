<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/12/2020
 * Time: 10:43 AM
 */
?>
<div class="col-md-12" style="background-color: whitesmoke;margin-top: 10px;">
    <div style="text-align: center;margin: 8px;" class="fedex-warning">
        <i class="fa fa-warning" style="font-size: 50px;color: darkorange;margin-top: 5px;"></i>
        <h2 class="sweet-alert-custom">Warning</h2>
        <?php
        foreach ( $error_response as $message ){
            ?>
            <p style="display: block;" class="sweet-alert-p-custom">Error Message : <?=$message?></p>
        <?php
        }
        ?>
    </div>
</div>