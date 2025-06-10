<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/5/2018
 * Time: 3:08 AM
 */
?>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['user/generic']];
        $this->params['breadcrumbs'][] = 'Shop Settings';
        ?>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?='Shop Settings'?></h3>
                <a style="color: white;" href="/channels-details/create" class="btn btn-info">Click Here to Add Comissions</a>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

                <?= $gridview?>
            </div>
        </div>
    </div>

</div>
