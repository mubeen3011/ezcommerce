<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/5/2018
 * Time: 2:27 AM
 */
?>

<div class="row">
    <div class="col-12">
        <?php
        $this->title = 'Channel Sellers';
        $this->params['breadcrumbs'][] = $this->title;
        ?>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h3><?=$this->title?></h3>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <?=$gridview?>
            </div>
        </div>
    </div>

</div>