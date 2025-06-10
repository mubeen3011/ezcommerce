<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\SettingsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'App Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="settings-index">


    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

   <!-- <p>
        <?/*= Html::a('Create Settings', ['create'], ['class' => 'btn btn-success']) */?>
    </p>
-->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['attribute'=>'name','value'=>
                function($data){
                    $name = str_replace('_',' ',$data->name);
                    $name = ucwords($name);
                    return $name;
                }
            ],
            ['class' => 'yii\grid\ActionColumn','template'=>'{update}',],
        ],
    ]); ?>
</div>
