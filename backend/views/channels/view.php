<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
?>
<div class="channels-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'is_active',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
