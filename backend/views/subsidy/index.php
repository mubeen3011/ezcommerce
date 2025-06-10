<?php

use common\models\Channels;
use common\models\CostPrice;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use backend\util;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\SubsidySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Subsidies And Margins';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subsidy-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Add Subsidy/Margin', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
<hr>
    <ul>
    <?php
        $clist = util\HelpUtil::getChannels();

        foreach ($clist as $c):
            if($c['id'] == 1 || $c['id'] == 7 )
                continue;
        ?>
         <li style="display:inline-block">
             <a href="<?=Url::to(['subsidy/index','c'=>$c['id']])?>" style="display:block; text-decoration:none; background:#ddd; border:solid 1px #ddd; color:#000; padding:5px 10px;">
             <?=$c['name']?>
             </a>
         </li>
        <?php endforeach; ?>
    </ul>

    <br>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'sku_id',
                'value' => 'sku.sku',
                'filter'=>ArrayHelper::map(CostPrice::find()->asArray()->all(), 'id', 'name'),

            ],
            [
                'attribute' => 'channel_id',
                'value' => 'channel.name',
                'filter'=>ArrayHelper::map(Channels::find()->where(['is_active'=>'1'])->asArray()->all(), 'id', 'name'),
            ],
            'subsidy',
            'margins',
            //'created_at',
            // 'updated_at',
            [
                'attribute' => 'updated_by',
                'value' => 'updatedBy.full_name',
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
