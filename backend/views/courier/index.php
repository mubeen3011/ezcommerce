<?php

use common\models\Settings;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Courier List', 'url' => ['/courier']];
$this->params['breadcrumbs'][] = 'couriers';
?>
<style>

</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <a class="btn btn-info btn-sm" href="/courier/create"><i class="fa fa-truck"></i>
                            Add new
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <!-----------all list---------->
                <div class="table-responsive mt-2">
                    <table id="myTable" class="table table-bordered ">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Warehouse assigned</th>
                        <th>Desc</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($couriers) && !empty($couriers)){
                        foreach ($couriers as $courier) {
                        ?>
                    <tr>
                        <td><img src="<?= $courier['icon'];?>"  width="80px"> </td>
                        <td><?= $courier['name'];?></td>
                        <td><?= $courier['type'];?></td>
                        <td><?= $courier['warehouse_binded'];?></td>
                        <td><?= $courier['description'];?></td>
                        <td><a title="Edit" href="/courier/update?id=<?= $courier['id'] ?>"><i class="fa fa-edit"></i></a></td>
                    </tr>
                    <?php } }?>
                    </tbody>
                </table>
                <!-----------all list---------->

            </div>
        </div>
    </div>
</div>


