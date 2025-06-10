<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 9/27/2019
 * Time: 10:42 AM
 */
namespace backend\util;

use backend\controllers\ApiController;
use Codeception\Template\Api;
use common\models\StocksPo;
use common\models\Warehouses;
use yii\web\Controller;

class IsisUtil extends Controller {

    private static $loginUrl       = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
    private static $createErUrl    = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/addErForStorageClientWebApi";
    private static $fetchErUrl     = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/getErForStorageClientWebApi";
    private static $fetchStockList = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsInvEntityBean/doQueryStorageClientInventoryPage.wms";
    private static $getOrders      = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsWebApiOrderBean/doGetWebApiOrder.wms";
    private static $erList         = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/queryErForStorageClientWebApiPage";
    private static $addErDetail    = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ErDetail/addErDetailForStorageClientWebApi";


    public static function Login( $warehouse_id ){

        $warehouse = Warehouses::find()->where(['id'=>$warehouse_id])->one(); // get warehouse
        $configuration = json_decode($warehouse->configuration); // decode json make array

        $postFields = self::postFields($configuration->userNo, $configuration->userPassword); // get the postFields xml for isis login

        $login_attempt = self::_MakeCall(self::$loginUrl,[],'POST',$configuration->port,$postFields); // login attempt
        $login_response = self::_refineResponse($login_attempt); // login response from api


        return ['login_response'=>$login_response,'w_config'=>$configuration];
    }
    public static function fetchER($warehouseId, $erNo)
    {
        $warehouse = Warehouses::find()->where(['id'=>$warehouseId])->one();
        $warehouseConfig = json_decode($warehouse->configuration, true);

        // login ISIS API
        $response = self::Login($warehouseId);
        $authSession = $response['login_response']['returnObject']['BondSession'];


        // fetch ER
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];

        $postFields = $erNo;//json_encode($postFields);
        $response = self::_MakeCall(self::$fetchErUrl, $access, 'POST', $warehouseConfig['port'], $postFields);
        return json_decode($response, true);
    }
    public static function createErc($warehouseId, $poId){

        $podId = StocksPo::find()->where(['id'=>$poId])->one();
        $warehouse = Warehouses::find()->where(['id'=>$warehouseId])->one();
        $warehouseConfig = json_decode($warehouse->configuration, true);
        //self::debug($warehouseConfig);
        //get PO details
        $poDoc = $podId->po_code;
        $remarks = $podId->remarks;
        $poDate = $podId->po_finalize_date;

        $response = self::Login($warehouseId);
        if ( isset($response['login_response']['returnObject']) ){
            $authSession = $response['login_response']['returnObject']['BondSession'];
        }else{
            $updatePo = StocksPo::findOne($poId);
            $updatePo->po_status='Draft';
            $updatePo->update();
            echo '<h3><center>There is an Error Coming from iStore iSend : <span style="color: red;">'.$response['login_response']['BondMsgList']['BondMsg']['msgCode'].'</span><br/>Please resolve the error and then try again.</center></h3>';
            die;
        }
        $authSession = $response['login_response']['returnObject']['BondSession'];

        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $postFields = [
            'erDate' => date("d/m/Y h:i:s", strtotime($poDate)),
            'erType' => strtoupper("Purchase Order"),
            'docNo' => $poDoc,
            'storageClientNo' => $warehouseConfig['userNo'],
            'remark' => $remarks,
            'supplierNo' => "",
        ];
        $postFields = json_encode($postFields);

        //$response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
        $response = self::_MakeCall(self::$createErUrl, $access, 'POST', $warehouseConfig['port'], $postFields);

        $refine = json_decode($response, true);
        if (isset($refine['returnObject'])) {
            $po = StocksPo::findOne(['id' => $poId]);
            $po->er_no = $refine['returnObject'];
            $po->save(false);
            return ['status'=>true,'erNo'=>$refine['returnObject']];
        }else{
            return ['status'=>false];
        }
    }
    public static function addDetailErc($warehouseId, $erNo, $poId){

        $warehouse = Warehouses::find()->where(['id'=>$warehouseId])->one();
        $warehouseConfig = json_decode($warehouse->configuration, true);

        $response = self::Login($warehouseId);
        $authSession = $response['login_response']['returnObject']['BondSession'];

        $poSkus = ApiController::getSkusForErc($poId);

        $apiUrl = self::$addErDetail;
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $port = $warehouseConfig['port'];
        $apiResponse =[];
        foreach ($poSkus as $ps) {


            $Fields = [
                'dataVersion' => 1,
                'erNo' => $erNo,
                'storageClientNo' => $warehouseConfig['userNo'],
                'skuDesc' => $ps['details'],
                'storageClientSkuNo' => $ps['sku'],
                'erQty' => $ps['final_order_qty'],
                'recvPrintLabel' => true,
                'scanSerialNo' => false,
            ];

            $postFields = json_encode($Fields);
            $response = self::_MakeCall($apiUrl, $access, 'POST', $port, $postFields);
            $apiResponse[] = $response;
        }


        return $apiResponse;


    }
    public static function createER($podId)
    {
        $poDetail = StocksPo::find()->where(['id'=>$podId])->one();
        $warehouse = Warehouses::find()->where(['id'=>$poDetail->warehouse_id])->one();
        $warehouseConfig = json_decode($warehouse->configuration, true);

        // add ER
        $refine = self::createErc($poDetail->warehouse_id, $podId);

        if (isset($refine['returnObject'])) {

            $po = StocksPo::findOne(['id' => $podId]);
            $po->er_no = $refine['returnObject'];
            $po->save(false);

            // add ER details

            $poSkus = PurchaseOrderUtil::getSkusForErc($podId);
            $response = self::Login($poDetail->warehouse_id);
            $authSession = $response['login_response']['returnObject']['BondSession'];
            foreach ($poSkus as $ps) {

                $postFields = [
                    'dataVersion' => 1,
                    'erNo' => $refine['returnObject'],
                    'storageClientNo' => $warehouseConfig['userNo'],
                    'skuDesc' => $ps['details'],
                    'storageClientSkuNo' => $ps['sku'],
                    'erQty' => $ps['final_order_qty'],
                    'recvPrintLabel' => true,
                    'scanSerialNo' => false,
                ];
                $postFields = json_encode($postFields);
                //$responsex = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
                $response = self::_MakeCall(self::$createErUrl, $access, 'POST', $warehouseConfig->port, $postFields);
                $refinex[$ps['sku']] = json_decode($response, true);
            }
        }
    }
    public static function GetSkuStocks( $warehouse_id ){

        $login = self::Login($warehouse_id); // Login and get sessionId & Password
       // self::debug($login);
        $AllSkus = [];
        if ( isset($login['login_response']) && isset($login['login_response']['success']) && $login['login_response']['success']=='false' ){
            echo $login['login_response']['BondMsgList']['BondMsg']['msgCode'];
            die;
        }
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($login['login_response']['returnObject']['BondSession']['sessionId'] . ":" . $login['login_response']['returnObject']['BondSession']['sessionPassword'])];
        $postXml = self::GetSkuStocksXml($login['w_config']->userNo);
        $response = self::_MakeCall(self::$fetchStockList, $access, 'POST', $login['w_config']->port, $postXml);
        $response = self::_refineResponse($response);
       // self::debug($response);
        $total_page = $response['returnObject']['WmsPageData']['totalPage'];

        for ( $startPage=1; $startPage<=$total_page; $startPage++ ){

            $postXml = self::GetSkuStocksXml($login['w_config']->userNo,$startPage);
            $response = self::_MakeCall(self::$fetchStockList, $access, 'POST', $login['w_config']->port, $postXml);
            $response = self::_refineResponse($response);
            foreach ( $response['returnObject']['WmsPageData']['currentPageData']['WmsStorageClientInventoryView'] as $val ){

                $val['storageClientSkuNo'] = str_replace('�','',trim($val['storageClientSkuNo']));
                $AllSkus[] = $val;

            }

        }

        return $AllSkus;

    }
    private static function GetSkuStocksXml($userNo, $currentPage=1, $currentLength=1000){

        return "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                            <BondParamList>
                                <BondParam>
                                    <paramName>clientInvQuery</paramName>
                                    <paramValue>
                                        <WmsStorageClientInventoryQuery>
                                            <country>MALAYSIA</country>
                                            <storageClientNo>$userNo</storageClientNo>
                                        </WmsStorageClientInventoryQuery>
                                    </paramValue>
                                </BondParam>
                                <BondParam>
                                    <paramName>pageData</paramName>
                                    <paramValue>
                                        <WmsPageData>
                                            <currentLength>$currentLength</currentLength>
                                            <currentPage>$currentPage</currentPage>
                                            <validateTotalSize>false</validateTotalSize>
                                        </WmsPageData>
                                    </paramValue>
                                </BondParam>
                            </BondParamList>";

    }
    public static function debug($data){

        echo '<pre>';
        print_r($data);
        die;

    }

    public static function postFields( $userNo, $userPassword ){

        $xml="<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n <BondParam> \n <paramName>userNo</paramName> \n <paramValue> \n<BondString><str>$userNo</str></BondString> \n </paramValue> \n </BondParam> \n <BondParam> \n <paramName>userPassword</paramName> \n <paramValue>\n<BondString><str>$userPassword</str></BondString>\n </paramValue> \n </BondParam> \n </BondParamList>";
        return $xml;

    }

    public static function _MakeCall($apiUrl, $authorizeHead = [], $method = 'GET', $curl_port = "", $post_fields = "")
    {
        $curl_url = sprintf("%s", $apiUrl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => $curl_port,
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 6000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $authorizeHead,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER =>0
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err . ' call from:' . $apiUrl;
        } else {
            return $response;
        }
    }
    public static function _refineResponse($response)
    {
        if (trim($response)!='Error Page!!!'){
            $response = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '', $response);
            $searchBadTags = ['ns2:'];
            $response = str_replace($searchBadTags, '', $response);
            $r = simplexml_load_string($response);
            $json = json_encode($r);
            $refine_data = json_decode($json, true);
            return $refine_data;
        }

    }

}