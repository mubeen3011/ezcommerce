<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/10/2020
 * Time: 3:23 PM
 */
namespace backend\controllers;

use backend\util\HelpUtil;
use common\models\DistributorZipcodes;
use common\models\OrdFulfilledByWhZipcodes;
use common\models\User;
use common\models\Warehouses;
use common\models\Zipcodes;

class DistributorController extends \backend\controllers\MainController{

    public function actionAssignZipCodes(){

        $Warehouses = [];
        $AllZipCodes = json_encode([]);
        $PreSelected = [];
        HelpUtil::GetRole();

        $Warehouses = self::GetWarehouses();
        $WhIds = self::GetWarehouseIds($Warehouses);

        if (!empty($_POST)){
            $this->SaveZipCodes($_POST);
        }

        if ( isset($_GET['warehouse']) && $_GET['warehouse']!='' && in_array($_GET['warehouse'],$WhIds) ){
            $AllZipCodes = $this->GetZipCodes();
            $PreSelected = $this->GetPreSelectedZipCodes($_GET['warehouse']);
            $AllZipCodes = json_encode($AllZipCodes);
        }
        return $this->render('assign-orders-areas/assign-zipcodes',[/*'zipcodes'=>$ZipCodes,*/'warehouses'=>$Warehouses,
            'allZipCodes'=>$AllZipCodes,'pre_selected_zipcodes'=>$PreSelected]);

    }
    private function GetPreSelectedZipCodes($warehouseId){
        $PreSelected = [];
        $GetZipCodes = OrdFulfilledByWhZipcodes::find()->where(['warehouse_id'=>$warehouseId])->asArray()->all();
        foreach ( $GetZipCodes as $v ){
            $PreSelected[] = $v['zipcode'];
        }
        return $PreSelected;
    }
    private function GetWarehouseIds($warehouses){
        $wid = [];
        foreach ($warehouses as $val){
            $wid[]=$val['id'];
        }
        return $wid;
    }
    private function GetWarehouses(){
        if ( HelpUtil::GetRole()=='distributor' ){
            $Warehouses = Warehouses::find()->where(['is_active'=>1,'user_id'=>\Yii::$app->user->identity->getId()])->asArray()->all();

        }else{
            $Warehouses = Warehouses::find()->where(['is_active'=>1])->asArray()->all();
        }
        return $Warehouses;
    }
    private function GetZipCodes(  ){
        $AllZipCodes = Zipcodes::find()->groupBy('state_id')->asArray()->all();
        $zip = [];
        foreach ( $AllZipCodes as $key=>$value ){
            $zip[$key]['id'] = $value['zipcode'];
            $zip[$key]['title'] = $value['state_id'];

            $Cities = Zipcodes::find()->where(['state_id'=>$value['state_id']])->asArray()->all();
            foreach ( $Cities as $val ){
                $cities = [];
                $cities['id'] = $val['zipcode'];
                $cities['title'] = $val['city_name'] . ' - '.$val['zipcode'];
                $zip[$key]['subs'][]=$cities;
            }
        }
        return $zip;
    }
    private function SaveZipCodes($data){
        OrdFulfilledByWhZipcodes::deleteAll(['warehouse_id'=>$data['warehouse']]);
        $zipcodes = explode(',',$data['zip_codes']);
        foreach ( $zipcodes as $value ){
            $zip = explode(' - ',$value);

            if (count($zip)==1)
                continue;

            $addZip = new OrdFulfilledByWhZipcodes();
            $addZip->warehouse_id=$data['warehouse'];
            $addZip->zipcode=$zip[1];
            $addZip->save();
        }

    }

}