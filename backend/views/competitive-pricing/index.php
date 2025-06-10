<?php

use common\models\Channels;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use backend\util;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\CompetitivePricingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$curChannel = Yii::$app->request->get('c');
$curChannel = ($curChannel != '') ? $curChannel : '1';
$channel = Channels::findOne(['id'=>$curChannel,'is_active'=>'1'])->name;


$this->title = 'Competitive Pricing for '.$channel ;
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="competitive-pricing-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <br>
    <p>
        <?= Html::a('Add Competitive Pricing', ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Import Competitive Pricing', ['import','c'=>$curChannel], ['class' => 'btn btn-info']) ?>
    </p>

    <br>
    <hr>
    <ul>
        <?php
        $clist = util\HelpUtil::getChannels();

        foreach ($clist as $c): ?>
            <li style="display:inline-block">
                <a href="<?= Url::to(['competitive-pricing/index', 'c' => $c['id']]) ?>"
                   style="display:block; text-decoration:none; background:#ddd; border:solid 1px #ddd; color:#000; padding:5px 10px;">
                    <?= $c['name'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'channel_id',
                'value' => 'channel.name',
                'filter'=>ArrayHelper::map(Channels::find()->where(['is_active'=>'1'])->asArray()->all(), 'id', 'name'),
            ],
            [
                'attribute' => 'sku_id',
                'value' => 'sku.sku',

            ],
            'seller_name',
            'low_price',
            [
                'attribute' => 'created_by',
                'value' => 'createdBy.full_name',
            ],
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]);
    ?>
</div>
