<?php

use common\models\Category;
use common\models\Channels;
use common\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\ChannelsDetailsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Channel Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="channels-details-index">

    <?php  // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Shops Details', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <br>
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
                'attribute' => 'category_id',
                'value' => 'category.name',
                'filter'=>ArrayHelper::map(Category::find()->where(['is_main'=>'1'])->asArray()->all(), 'id', 'name'),
            ],
            'commission',
            'shipping',
            // 'created_at',
            // 'updated_at',
            [
                'attribute' => 'updated_by',
                'value' => 'updatedBy.full_name',
                'filter'=>ArrayHelper::map(User::find()->asArray()->all(), 'id', 'full_name'),
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
