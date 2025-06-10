 <?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\DealsMaker */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Deals', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Create new deal request';
?>
 <style>
     .control-label {
         padding: 0px;
     }
     .select2-dropdown{
         margin:-8px;
     }
 </style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Create new deal request </h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div class="row">
                    <div class="deals-maker-create col-md-12" style="padding: 0px;">
                        <?= $this->render('_form', [
                            'model' => $model,'multiSkus'=>$multiSkus,'category_list'=>$category_list
                        ]) ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

