<?php

namespace backend\controllers;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use backend\util\HelpUtil;
use common\models\CostPrice;
use common\models\PhilipsCostPrice;
use common\models\PoDetails;
use common\models\ProductDetails;
use common\models\ProductStocks;
use common\models\Settings;
use common\models\StocksPo;
use Predis\Command\HashLength;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class GenericGridController extends MainController
{
    public $config=[];
    public $data =[];
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if ($action->id == 'stock-info' || $action->id == 'stock-info-sort' || $action->id == 'stock-info-filter' ||
            $action->id == 'products-info' || $action->id == 'products-info-sort' || $action->id == 'products-info-filter' ||
            $action->id == 'manage-info' || $action->id = 'manage-info-sort' || $action->id = 'manage-info-filter'

        ) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }
    //ajax call for stocks info and filters
    public function actionGenericGrid(){
        $this->config = $this->actionConfigParams();
        $pds = HelpUtil::getGenericData(0,$this->config);
        $pdq = \Yii::$app->request->post('pdqs');

        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('../generic-grid/_GenericInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);

        $this->data['tfoot']=$this->renderAjax('../generic-grid/_GenericInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);

        echo $this->data['tbody']."|".$this->data['tfoot'];
    }
    public function actionGenericInfoFilter()
    {
        Yii::$app->request->post('records_per_page');
        echo  Yii::$app->request->post('records_per_page');

        $this->config = $this->actionConfigParams();
        $pds = HelpUtil::getGenericData(0,$this->config);
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data=[];
        $this->data['tbody']= $this->renderAjax('../generic-grid/_GenericInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('../generic-grid/_GenericInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody']."|".$this->data['tfoot'];
    }

    public function actionGenericInfoSort()
    {
        $this->config = $this->actionConfigParams();
        $pds = HelpUtil::getGenericData(0,$this->config);
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data=[];
        $this->data['tbody']= $this->renderAjax('../generic-grid/_GenericInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('../generic-grid/_GenericInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody']."|".$this->data['tfoot'];
    }
    public function actionGenericInfo()
    {
        $pdq = \Yii::$app->request->post('pdqs');
        $this->config = $this->actionConfigParams();
        $pds = HelpUtil::getGenericData(0,$this->config);

         Yii::$app->request->post('records_per_page');
         $pagination_total_pages = ceil($pds['total_records'] / \Yii::$app->request->post('records_per_page'));
         $this->data['tbody']= $this->renderAjax('../generic-grid/_GenericInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
         $this->data['tfoot']=$this->renderAjax('../generic-grid/_GenericInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);

         echo $this->data['tbody']."|".$this->data['tfoot'];
    }

    public function CsvHeaderFromArray($arr){
        $header= [];
        foreach ($arr[0] as $key=>$value){
            $header[] = $key;
        }
        return $header;
    }
    public function export_csv($data,$filename) {
        // No point in creating the export file on the file-system. We'll stream
        // it straight to the browser. Much nicer.
        // Open the output stream
        $fh = fopen('php://output', 'w');

        // Start output buffering (to capture stream contents)
        ob_start();

        // CSV Header
        //$header = array('Field 1', 'Field 2', 'Field 3', 'Field 4', 'Field 5', 'Etc...');
        $header = $this->CsvHeaderFromArray($data);
        fputcsv($fh, $header);

        // CSV Data
        foreach ($data as $k => $v) {
            /*$line = array($data['field_1'], $data['field_2'], $data['field_3'], $data['field_4'], $data['field_5'], $data['field_etc']);
            fputcsv($fh, $line);*/
            $line = array();
            foreach ($v as $v2){
                $line[] = $v2;
            }
            //$line = array($data['field_1'], $data['field_2'], $data['field_3'], $data['field_4'], $data['field_5'], $data['field_etc']);
            fputcsv($fh, $line);
        }
        // Get the contents of the output buffer
        $string = ob_get_clean();

        // Set the filename of the download


        // Output CSV-specific headers
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv";');
        header('Content-Transfer-Encoding: binary');

        // Stream the CSV data
        exit($string);
    }

}
