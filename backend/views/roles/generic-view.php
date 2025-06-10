<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/5/2018
 * Time: 1:04 AM
 */
?>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator ', 'url' => ['/user/generic']];
        $this->params['breadcrumbs'][] = 'User Roles';
        ?>
    </div>
    <div class="col-12">
        <div class="card">

            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='User Roles'?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

                <?=$gridview?>
            </div>
        </div>
    </div>

</div>
