<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

//$this->title = 'Categories';
$this->params['breadcrumbs'][] = 'Categories';
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">

                        <p>
                            <?= Html::a('Create Category', ['create'], ['class' => 'btn btn-success']) ?>
                        </p>
                    </div>
                    <div class="col-md-2">
                        <a href="/category/download-categries-csv" class="btn btn-sm btn-info fa fa-download" > Download List</a>
                    </div>
                    <div class="col-md-2">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div class="row">
                <div class="col-12">

                    <!------------------------>
                    <div class="table-responsive" >
                        <table class="table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Parent ID</th>
                                <th>Is Active</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?= isset($data) ? $data :NULL;?>
                            </tbody>
                        </table>
                    </div>
                    <!------------------------>

                </div>
                </div>

            </div>
        </div>
    </div>
</div>

