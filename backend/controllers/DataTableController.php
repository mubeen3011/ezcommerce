<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/22/2018
 * Time: 11:57 AM
 */
namespace backend\controllers;
use yii\web\Controller;

class DataTableController extends Controller {
    public function actionIndex(){
        return $this->render('index');
    }
}