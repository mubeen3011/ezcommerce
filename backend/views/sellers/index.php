<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\SellersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Channel Sellers';
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
    input{
        width: 100% !important;
    }
</style>
<div class="sellers-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Add Sellers', ['create'], ['class' => 'btn btn-success']) ?>
    </p><br/>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['attribute'=>'seller_name','filter'=>false],
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
