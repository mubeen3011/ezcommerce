<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Modules';
$this->params['breadcrumbs'][] = $this->title;
//$user_menu=Yii::$app->permissionCheck->getSidebarMenu();
//print_r($user_menu); die();
?>
<style>

    tr {
        line-height: 2px !important;
        min-height: 2px !important;
        height: 2px !important;
    }
    td
    {
        font-size: 11px;
    }
</style>
<div class="modules-index">


    <p>
        <?= Html::a('Create Module', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <div class="table-responsive" >
        <table class="table">
            <thead>
            <tr>
                <th>Module</th>
                <th>Position</th>
                <th>Controller</th>
                <th>View method</th>
                <th>Create method</th>
                <th>Update method</th>
                <th>Delete method</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody class="reorder-gallery">

            <?php if(isset($data) && !empty($data)) :
                foreach ($data as $module) {
                    ?>
                    <tr>
                        <td>
                            <a href="javascript:void(0)">
                                <?= $module['parent_id'] ? "&nbsp;&nbsp;&nbsp;----> " . $module['name']:"<b style='color: #E67E22'>".strtoupper($module['name'])."</b>";?>

                            </a>
                        </td>
                        <td><?= $module['menu_position'];?></td>
                        <td><?= $module['controller_id'];?></td>
                        <td><?= $module['view_id'];?></td>
                        <td><?= $module['create_id'];?></td>
                        <td><?= $module['update_id'];?></td>
                        <td><?= $module['delete_id'];?></td>
                        <td><?= Html::a('', ['delete', 'id' => $module['id']], ['class' => 'fa fa-trash','style'=>'color:red','data' => [
                                'confirm' => 'Are you sure you want to delete this item?',
                                'method' => 'post',
                            ],]) ?></td>
                    </tr>
                    <?php
                    if(isset($module['children']) && is_array($module['children'])) {  //second level
                        foreach($module['children'] as $child) { //second level for ?>

                            <tr>
                                <td>
                                    <a href="javascript:void(0)">
                                        <?= $child['parent_id'] ? "&nbsp;&nbsp;&nbsp;----> " . $child['name']:"<b style='color: #E67E22'>".strtoupper($child['name'])."</b>";?>

                                    </a>
                                </td>
                                <td><?= $child['menu_position'];?></td>
                                <td><?= $child['controller_id'];?></td>
                                <td><?= $child['view_id'];?></td>
                                <td><?= $child['create_id'];?></td>
                                <td><?= $child['update_id'];?></td>
                                <td><?= $child['delete_id'];?></td>
                                <td><?= Html::a('', ['delete', 'id' => $child['id']], ['class' => 'fa fa-trash','style'=>'color:red','data' => [
                                        'confirm' => 'Are you sure you want to delete this item?',
                                        'method' => 'post',
                                    ],]) ?></td>
                            </tr>

                            <?php   //3rd level
                            if(isset($child['children']) && is_array($child['children'])) {  //3rd level if
                                foreach($child['children'] as $child_third) { //3rd level for ?>
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)">
                                                <?= $child_third['parent_id'] ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----> " . $child_third['name']:"<b style='color: #E67E22'>".strtoupper($child_third['name'])."</b>";?>


                                            </a>
                                        </td>
                                        <td><?= $child_third['menu_position'];?></td>
                                        <td><?= $child_third['controller_id'];?></td>
                                        <td><?= $child_third['view_id'];?></td>
                                        <td><?= $child_third['create_id'];?></td>
                                        <td><?= $child_third['update_id'];?></td>
                                        <td><?= $child_third['delete_id'];?></td>
                                        <td><?= Html::a('', ['delete', 'id' => $child_third['id']], ['class' => 'fa fa-trash','style'=>'color:red','data' => [
                                                'confirm' => 'Are you sure you want to delete this item?',
                                                'method' => 'post',
                                            ],]) ?></td>
                                    </tr>
                                <?php          }  } // 3rd levelend

                        } } //second level end ?>
                <?php } endif; // first level end ?>
            </tbody>
        </table>
    </div>

</div>
