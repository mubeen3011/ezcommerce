<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/4/2018
 * Time: 11:58 PM
 */
?>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator ', 'url' => ['/user/generic']];
        $this->params['breadcrumbs'][] = 'Users ';
        ?>
    </div>
    <div class="col-12">
        <div class="card">

            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?='Users   '?></h3>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

                <?=$gridview?>
            </div>
        </div>
    </div>

</div>
