<?php

use common\models\Category;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use kartik\daterange\DateRangePicker;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\CostPriceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Cost Prices';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cost-price-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Add Cost Price', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <br>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'sku',
            'name:ntext',
            'cost',
            'rccp_cost',
            [
                'attribute' => 'sub_category',
                'value' => 'subCategory.name',
                'filter'=>ArrayHelper::map(Category::find()->asArray()->all(), 'id', 'name'),
            ],
            [
                'attribute' => 'sub_category',
                'value' => 'subCategory.name',
                'filter'=>ArrayHelper::map(Category::find()->asArray()->all(), 'id', 'name'),
            ],
            // 'created_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
