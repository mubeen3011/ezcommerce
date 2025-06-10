<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 3/27/2019
 * Time: 5:03 PM
 */
namespace backend\controllers;

use backend\util\HelpUtil;
use common\models\Category;
use common\models\CronJobsLog;
use common\models\GeneralReferenceKeys;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class MainController extends Controller {

    public $Cron_Log_Id = '';
    public function __construct($id, $module, array $config = [])
    {
        $module_name= ($id==$module->defaultRoute) ? $module->defaultRoute:$module->requestedRoute;
        if(!Yii::$app->user->isGuest)
        {
           $authorized= Yii::$app->permissionCheck->check_logged_in_user_authorization($module_name);
           if(!$authorized){
                  throw new ForbiddenHttpException('YOU ARE NOT ALLOWED TO PERFORM ACTION AND VIEW THIS PAGE');
           }
        }

        /**
         * Overlapping cron jobs condition, Even cron runs it inserts the log by calling this constructor.
         * Now I'm putting a condition that don't log some perticular cron jobs from constructor. We will log it
         * from the method we are calling it.
         * */
        if ( isset($module->requestedRoute) && ($module->requestedRoute == "cron/save-product-stock-isis-info" || $module->requestedRoute == "cron/fetch-product-stock-fbl-info") ){

        }
        else{

            /**
             * Log cron job
             * */

            if ( isset($module->requestedRoute) && in_array($module->requestedRoute,$this->Log_Cron_URL_List) ){
                $this->Cron_Log_Id = $this->actionLogJob(['URL'=>$this->_get_current_url()],'create');
            }
        }

        parent::__construct($id, $module, $config);

    }

    public $Log_Cron_URL_List = [
        'api/call-channels-products',
        'cron/sales-fetch',
        'api/sync-stocks',
        'api/sync-prices',
        'api/delete-logs-data',
        'cron/subsidy-price-update',
        'cron/sync-warehouse-products',
        'cron/warehouse-stock-sync',
        'cron/sync-sales-to-skuvault',
        'cron/update-threshold',
        'cron/fetch-po-recieved-qty'
    ];

    public function __destruct(){

        /**
         * Update the cron log
         * */
        if ( isset($this->Cron_Log_Id) && $this->Cron_Log_Id!='' ){
            $this->actionLogJob(['job_id'=>$this->Cron_Log_Id],'update');
        }

    }
    public function actionLogJob($Data,$Method){

        if ( $Method=='create' ){
            $Job_Log = new CronJobsLog();
            $start_datetime = date('Y-m-d H:i:s');
            $Job_Log->job_link = $Data['URL'];
            $Job_Log->start_datetime = $start_datetime;
            $Job_Log->save();
            return $Job_Log->id;
        }
        else if ( $Method == 'update' ){
            $Update_Job = CronJobsLog::findOne($Data['job_id']);
            $end_datetime = date('Y-m-d H:i:s');
            $minutes = ( strtotime($end_datetime) - strtotime(self::exchange_values('id','start_datetime',$Data['job_id'],'cron_jobs_log')) ) / 60;
            $status = '';
            if ($minutes>=0 && $minutes<=15)
                $status = 'Green';
            elseif ($minutes>15 && $minutes<=45)
                $status = 'Yellow';
            elseif ($minutes>45)
                $status = 'Red';
            $Update_Job->end_datetime = $end_datetime;
            $Update_Job->time_status = $status;
            $Update_Job->completed_in_minutes = (string) number_format($minutes,2);
            $Update_Job->update();
        }
    }

    public function _get_current_url(){
        return $_SERVER['SERVER_NAME'].\Yii::$app->request->url;
    }

    public function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        //return $result_data[0][$to];
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }

    /*
     * Return readable format to print array.
     *
     * $param Array $data will be print in readable format
     * */

    public function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    /*
     * Return the table data
     *
     * @param string $Index the Array index name.
     * @param string $Table The table name.
     * @param string $Value Sets the where clause of $Index
     * @param boolean $Multi Sets the array multiple array inside an index.
     */

    public function getList($Index,$Table,$Multi=0,$Where=""){

        $query = " SELECT * FROM ".$Table.$Where;
        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand($query);

        $result = $command->queryAll();
        $response = [];

        foreach ( $result as $value ){
            if ($Multi==0){
                $response[$value[$Index]]=$value;
            }else{
                $response[$value[$Index]][]=$value;
            }
        }
        return $response;

    }
    /*
     * Return Threshold T1
     *
     * @param integer $Price Decides what the threshold will be
     * */
    public function ManualWeeklyThresholdSetByUnitPrice($Price){

        if ($Price > 0 && $Price <= 150)
            $Threshold = 5;
        elseif ($Price > 150 && $Price < 300)
            $Threshold = 3;
        else
            $Threshold = 1;

        return $Threshold;
    }
    /*
     * Return Threshold T2
     *
     * @param
     * */
    public function GetThresholdCritical($T1,$Status){

        if ($T1 == 3)
            $T2 = 2;
        else if ($T1 == 2)
            $T2 = 1;
        else if ($T1 == 1)
            $T2 = 1;
        else{
            $Daily = $T1 / 7;
            $T2 = ceil($Daily) * 3;
        }
        if ($Status=='Not Moving' || $Status=='New')
            $T2=0;

        return $T2;
    }
    public function GetCronLog($start_datatime,$end_datetime,$job_link,$limit=1,$running=0){

        return HelpUtil::GetCronLogQuery($start_datatime,$end_datetime,$job_link,$limit,$running);

    }
    public function RemoveDirectories(Array $directories_links){
        $result = [];
        foreach ( $directories_links as $directory ){
            $result[] = FileHelper::removeDirectory($directory);
        }
        return $result;
    }
    public function AddGeneralReferenceKey($Table_Name,$Table_PK,$Key,$Channel_id,$Value){
        $AddStockAvailablefield = new GeneralReferenceKeys();
        $AddStockAvailablefield->table_name = $Table_Name;
        $AddStockAvailablefield->table_pk = $Table_PK;
        $AddStockAvailablefield->key = $Key;
        $AddStockAvailablefield->channel_id = $Channel_id;
        $AddStockAvailablefield->value = $Value;
        $AddStockAvailablefield->added_at = date('Y-m-d H:i:s');
        $AddStockAvailablefield->save();
        if (!empty($AddStockAvailablefield->errors))
            $this->debug($AddStockAvailablefield->errors);
        else
            return $AddStockAvailablefield;
    }
    public function CsvToJson($feed,$json=true){
        header('Content-type: application/json');
// Set your CSV feed
// Arrays we'll use later
        $keys = array();
        $newArray = array();
// Function to convert CSV into associative array
        function csvToArray($file, $delimiter) {
            if (($handle = fopen($file, 'r')) !== FALSE) {
                $i = 0;
                while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
                    for ($j = 0; $j < count($lineArray); $j++) {
                        $arr[$i][$j] = $lineArray[$j];
                    }
                    $i++;
                }
                fclose($handle);
            }
            return $arr;
        }
// Do it
        $data = csvToArray($feed, ',');
       // self::debug($data);
// Set number of elements (minus 1 because we shift off the first row)
        $count = count($data) - 1;

//Use first row for names
        $labels = array_shift($data);
        foreach ($labels as $label) {
            $keys[] = trim($label);
        }
// Add Ids, just in case we want them later
        $keys[] = 'id';
        for ($i = 0; $i < $count; $i++) {
            $data[$i][] = $i;
        }

// Bring it all together
        for ($j = 0; $j < $count; $j++) {
            $d = array_combine($keys, $data[$j]);
            $newArray[$j] = $d;
        }
// Print it out as JSON
        if(!$json)  // if json format not required
            return $newArray;

        return json_encode($newArray);
    }
    function get_categories_array($parent = 0)
    {
        $html = '';
        $query = Category::find()->where(['parent_id'=>$parent])->asArray()->all();
        foreach( $query as $value )
        {
            $current_id = $value['id'];
            $html .= ',' . $value['id'];
            $has_sub = NULL;
            $has_sub = Category::find()->where(['parent_id'=>$current_id])->asArray()->all();
            if($has_sub)
            {
                $html .= $this->get_categories_array($current_id);
            }
            $html .= '';
        }
        $html .= '';
        return $html;
    }
    function GetAllChildCategories($parent=0){
        $result=$this->get_categories_array($parent);
        $result = ltrim($result,',');
        $categories = explode(',',$result);
        if ($categories[0]==''){
            return [];
        }else{
            return $categories;
        }

    }
    public function _MakeDropDownList($data,$option_value,$option_text){
        $options = [];
        foreach ($data as $value  ){
            $options[$value[$option_value]] = $value[$option_text];
        }
        return $options;
    }
    public function _GetIndexFromQueryData( $data, $index ){
        $indexes = [];
        foreach ( $data as $value ){
            $indexes[] = $value[$index];
        }
        return $indexes;
    }
    public function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    public function ChildCategoryToParent($cat_id){
        $sql = "SELECT * FROM category where `id`='".$cat_id."'";
        $category = Category::findBySql($sql)->one();
        if ( $category->parent_id==null || $category->parent_id == '' ){
            return $category->id;
        }else{
            return self::ChildCategoryToParent($category->parent_id);
        }
    }
}