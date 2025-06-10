<?php
/**
 * Created by PhpStorm.
 * User: Abdullah
 * Date: 5/4/2018
 * Time: 11:43 AM
 */
namespace backend\controllers;

use common\models\Channels;
use common\models\CrawlApiHits;
use common\models\CrawledProductsHistory;
use common\models\CrawlRatings;
use common\models\CompetitivePricing;
use common\models\Products;
use common\models\search\SkusCrawlSearch;
use common\models\Settings;
use common\models\SkusCrawl;
use common\models\StockPriceResponseApi;
use common\models\TempCrawlResults;
use yii\web\Controller;

class CrawlController extends MainController {
    public $enableCsrfValidation = false;

    public function actionRun()
    {
        $cron_job_id=$this->actionLogJob(['URL' => $this->_get_current_url()],'create');
        if (!isset($_GET['channel']))
        {
            echo '<h1>Please use the channel id in URL, Like => channel=1</h1>';
            die;
        }
        /*echo '<pre>';
        $json=$this->actionCrawlLazadaWeb('https://shopee.com.my/api/v2/item/get?itemid=234656574&shopid=10513631');
        print_r(json_decode($json));
        die;*/

        ini_set('max_execution_time', 300000); //300 seconds = 5 minutes
        $start_date_time = date('Y-m-d h:i:s');
        echo "started at " . date('h:i:s') . "<br/>";
        $Skus=new SkusCrawl();

        if( isset($_GET['sku_id']) ){
            $skuid=$_GET['sku_id'];
        }else{
            $skuid='';
        }
        $skus=$Skus->GetSkusProductIds($skuid);

        //$this->debug( $skus );
        /*
         * Now our loop will call the CheckTheLowest method to calculate which seller offering the lowest price
         *
         *  */
        $Result = array();
        include('../library/simplehtmldom_1_5/simple_html_dom.php');

        $counter=0;
        $channel = Channels::find()->where(['id'=>$_GET['channel']])->one();
        //echo $channel->marketplace;die;
        foreach ( $skus as $value ){
            //$this->debug($value);
            $Check_Sku_Already_Crawled = TempCrawlResults::find()->where(['channel_id'=>$_GET['channel'],'sku_id'=>$value[$_GET['channel']]['sku_id'],
                'added_at'=>date('Y-m-d')])->asArray()->all();
            if (!empty($Check_Sku_Already_Crawled)){
                continue;
            }
            $lowestPriceProductIds = array();

            $lowestPriceProductIds[]=$this->CheckTheLowest( $value );
            //$this->debug($lowestPriceProductIds);
            foreach ($lowestPriceProductIds as $value){
                foreach ( $value as $pid ){
                    foreach ( $pid as $values ){
                        if( !isset($values['product_sku']) || !isset($values['channel_id']) || !isset($values['price']) ){
                            $this->debug($values);
                            continue;
                        }

                        $CreateCompetitiveEntry = new CompetitivePricing();
                        $CreateCompetitiveEntry->channel_id = $values['channel_id'];
                        $CreateCompetitiveEntry->sku_id=$values['sku_id'];
                        $CreateCompetitiveEntry->seller_name=trim($values['seller']);
                        $CreateCompetitiveEntry->low_price = trim($values['price']);
                        $CreateCompetitiveEntry->created_at = date('Y-m-d');
                        $CreateCompetitiveEntry->updated_at = date('Y-m-d');
                        $CreateCompetitiveEntry->created_by = 1;
                        $CreateCompetitiveEntry->price_change=1;
                        $CreateCompetitiveEntry->save();
                        if(!empty($CreateCompetitiveEntry->errors)){
                            echo '<pre>';
                            print_r($values);
                            print_r($CreateCompetitiveEntry->errors);
                            die;
                        }
                        $AddInDb=new TempCrawlResults();
                        $AddInDb->sku_id=$values['sku_id'];
                        $AddInDb->product_id=$values['product_sku'];
                        $AddInDb->marketplace = $channel->marketplace;
                        $AddInDb->channel_id=(string)$values['channel_id'];
                        $AddInDb->product_name=trim($values['product_name']);
                        $AddInDb->price=trim($values['price']);
                        $AddInDb->seller_name=trim($values['seller']);
                        $AddInDb->added_at=date('Y-m-d h:i:s');
                        $AddInDb->save();
                        if(!empty($AddInDb->errors)){
                            echo '<pre>';
                            print_r($values);
                            print_r($AddInDb->errors);
                            die;
                        }
                        //}
                        //die;


                    }

                }
            }
            $counter++;
        }


        echo '<h1>Successfully Crawled</h1>';
        echo "finished at " . date('h:i:s');
        $end_date_time = date('Y-m-d h:i:s');
        // make the log into the api stock table

        $SaveLog = new StockPriceResponseApi();
        $SaveLog->type='Crawl - '.$_SERVER['HTTP_HOST'];
        $SaveLog->channel_id=$_GET['channel'];
        $SaveLog->response='Started At : '.$start_date_time.' , End At : '.$end_date_time;
        $SaveLog->create_at=date('Y-m-d H:i:s');
        $SaveLog->save();

        $settings = Settings::find()->where(['name' => 'last_skus_crawl'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }
        $this->actionLogJob(['job_id'=>$cron_job_id],'update');
    }
    public function actionAfterRun(){
        $cron_job_id=$this->actionLogJob(['URL' => $this->_get_current_url()],'create');

        $connection = \Yii::$app->db;
        $command = $connection->createCommand("
        select sku_id,min(price) as price,channel_id,seller_name from temp_crawl_results
where added_at = :added_at
group by sku_id
        ", [':added_at' => date('Y-m-d')]);

        $result = $command->queryAll();


        $include = [5, 6, 9, 10, 11,13,15,14,16];
        $channelList = \backend\util\HelpUtil::getChannels($include);
        //$this->debug($channelList);
        foreach ($result as $list){

            foreach ($channelList as $cl) {

                $cp = CompetitivePricing::findOne(['created_by' => \Yii::$app->user->id, 'created_at' => date('Y-m-d'), 'sku_id' => $list['sku_id'], 'channel_id' => $cl['id']]);
                if ($cp) {
                    $cp->seller_name = $list['seller_name'];
                    $cp->low_price = $list['price'];
                    $cp->updated_at = date('Y-m-d');
                    $cp->updated_by = \Yii::$app->user->id;
                    $cp->price_change = 1;
                    $cp->save(false);
                } else {
                    $cp = new CompetitivePricing();
                    $cp->sku_id = $list['sku_id'];
                    $cp->channel_id = $cl['id'];
                    $cp->seller_name = $list['seller_name'];
                    $cp->low_price = $list['price'];
                    $cp->created_at = date('Y-m-d');
                    $cp->created_by = \Yii::$app->user->id;
                    $cp->price_change = 1;
                    $cp->save(false);
                }
            }
        }

        if( empty($result) ){
            echo '<h1>Please run the crawler first, Then run this link</h1>';
        }else{
            // $RunSkuMarginsForDMUpdate=$this->RunCurlCall('cron/sku-margins-for-dm-update');
            // $RunAutoDealMaker=$this->RunCurlCall('deals-maker/auto-deal-maker');
            echo '<h1>Run successullfy</h1>';

        }

        $this->actionLogJob(['job_id'=>$cron_job_id],'update');
    }
    private function RunCurlCall($method){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://usboxing.ezcommerce.io/".$method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Postman-Token: b9979b24-514f-484e-9bb0-71a20d444039"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }
    private function CheckTheLowest( $Data ){

        //$this->debug($Data);
        //$this->debug($Data);
        $SkuPrice=array();
        foreach ( $Data as $key=>$value ){

            $channel = Channels::find()->where(['id'=>$key])->one();
            //$this->debug($channel);

            if( $channel->marketplace=='lazada' )
            {
                //$this->debug($value);
                foreach ( $value['product_ids'] as $ids ){
                    // check the id, already crawled or not
                    $Already_Crawled_Product_Id = CrawledProductsHistory::find()->where(['product_id'=>$ids,'added_at'=>date('Y-m-d')])->asArray()
                        ->all();
                    if ( !empty($Already_Crawled_Product_Id) )
                        continue;
                    //echo $ids;die;
                    $sku_info=$this->Lazada($ids,$value['sku_id'],$key);

                    //$this->debug($sku_info);
                    if ($sku_info!=null){
                        $sku_info['sku_id']=$value['sku_id'];
                        $SkuPrice[$key][$ids]=$sku_info;
                    }

                }
                //echo 'hello';die;
                //$this->debug($SkuPrice);
            }

            elseif ( $channel->marketplace=='shopee' ){
                foreach ( $value['product_ids'] as $ids ){
                    $Already_Crawled_Product_Id = CrawledProductsHistory::find()->where(['product_id'=>$ids,'added_at'=>date('Y-m-d')])->asArray()
                        ->all();
                    if ( !empty($Already_Crawled_Product_Id) )
                        continue;
                    $sku_info=$this->Shopee($ids,$value['sku_id'],$key);
                    //$this->debug($sku_info);
                    $sku_info['sku_id']=$value['sku_id'];
                    $SkuPrice[$key][$ids]=$sku_info;
                }
            }
        }
        //$this->debug($SkuPrice);

        //echo '<pre>';
        //print_r($SkuPrice);

        $pricelist = array();

        foreach ( $SkuPrice as $channel_id=>$value ){
            foreach ( $value as $key=>$items ){
                if( !isset($items['channel_id']) || !isset($items['price']) ){
                    continue;
                }
                $pricelist[$items['channel_id']][$key] = $items['price'];
            }
        }
        //print_r($pricelist);
        $final_product_list = array();
        foreach ( $pricelist as $key=>$value ){
            $p_key = array_search(min($value), $value);
            $final_product_list[$key][$p_key]=$SkuPrice[$key][$p_key];
        }
        //print_r($final_product_list);
        //die;
        return $final_product_list;
    }

    private function Lazada($id,$sku_id,$channelId){

        $html = $this->actionCrawlLazadaWeb('https://www.lazada.com.my/catalog/?q='.$id.'&_keyori=ss&from=input&spm=a2o4k.pdp.search.go.41a08cc8cZVfRf');
        //echo 'https://www.lazada.com.my/catalog/?q='.$id.'&_keyori=ss&from=input&spm=a2o4k.pdp.search.go.41a08cc8cZVfRf';die;
        $result_data = array();

        // Get the product link
        foreach($html->find('script') as $element){
           $data = (json_decode($element->innertext));
            if( isset($data->itemListElement[0]->url) ){
                $product_link = ($data->itemListElement[0]->url);
            }
        }

        if( isset($product_link) ){
            $html = $this->actionCrawlLazadaWeb($product_link);

            // get product detail json
            foreach($html->find('script') as $element){
                $Json = json_decode($element->innertext);
                if ( isset($Json->offers) ){
                    $Product_Detail = $Json;
                }
            }
            if (!isset($Product_Detail))
                return null;
            //$this->debug($Product_Detail);
// Get the product name
            $result_data['product_name'] = $Product_Detail->name;

// Get the product price
            //$this->debug($result_data);
            $result_data['price'] = number_format($Product_Detail->offers->lowPrice,2);
            $result_data['price'] = (string) $result_data['price'];

                //$result_data['price'] = $price[0];
            // Get the seller name
            $result_data['seller'] = $Product_Detail->offers->seller->name;


// Get the sku
            $result_data['product_sku'] = $Product_Detail->sku;

            $result_data['channel_id'] = $channelId;
            //$this->debug($result_data);
            $this->AddProductCrawlLog([
                'sku_id'=>$sku_id,
                'channel_id'=>$_GET['channel'],
                'product_id'=>$id,
                'price'=>isset($result_data['price']) ? $result_data['price'] : '',
                'link_status'=>'200',
            ]);
            return $result_data;
        }else{
            $this->AddProductCrawlLog([
                'sku_id'=>$sku_id,
                'channel_id'=>$_GET['channel'],
                'product_id'=>$id,
                'price'=>'',
                'link_status'=>'404',
            ]);
        }

    }
    private function AddProductCrawlLog($Product_Info){
        $AddProductCrawlLog=new CrawledProductsHistory();
        $AddProductCrawlLog->sku_id=$Product_Info['sku_id'];
        $AddProductCrawlLog->channel_id=$Product_Info['channel_id'];
        $AddProductCrawlLog->product_id=$Product_Info['product_id'];
        $AddProductCrawlLog->price=$Product_Info['price'];
        $AddProductCrawlLog->link_status=$Product_Info['link_status'];
        $AddProductCrawlLog->added_at=date('Y-m-d');
        $AddProductCrawlLog->save();
        if ( !empty($AddProductCrawlLog->errors) )
            $this->debug($AddProductCrawlLog->errors);
    }
    private function ElevenStreet($id,$sku_id){
        $html = $this->actionCrawlLazadaWeb('http://www.11street.my/productdetail/'.$id);


// Get the product name
        foreach ( $html->find('.product-detail-status') as $element )
            $soldout=$element->innertext;
        //if(!isset($soldout)){ $soldout=''; }
        //if($soldout!='Sold Out'){
        foreach($html->find('h1') as $element)
            $result_data['product_name'] = strip_tags($element->innertext);
// Get the product price
        foreach($html->find('.product-detail-selling-price') as $element)
            $result_data['price'] = str_replace(',','',str_replace('RM ','',strip_tags($element->innertext)));

// Get the seller name
        foreach($html->find('.product-detail-seller-info .aside-section-header h3') as $element)
            $result_data['seller'] =strip_tags($element->innertext);

// Get the sku
        $counter = 1;
        foreach($html->find('.specification-keys li div') as $element){
            if( $counter == 2 ){
                //echo $element;
            }
            $counter++;
        }
        /*foreach($html->find('.product-info-table tbody tr td') as $element)
            $this->debug($element);*/
        $result_data['product_sku'] = $id;
        $result_data['channel_id']='3';
        if ( isset($result_data['price']) ){
            $this->AddProductCrawlLog([
                'sku_id'=>$sku_id,
                'channel_id'=>$_GET['channel'],
                'product_id'=>$id,
                'price'=>isset($result_data['price']) ? $result_data['price'] : '',
                'link_status'=>'200',
            ]);
        }else{
            $this->AddProductCrawlLog([
                'sku_id'=>$sku_id,
                'channel_id'=>$_GET['channel'],
                'product_id'=>$id,
                'price'=>isset($result_data['price']) ? $result_data['price'] : '',
                'link_status'=>'404',
            ]);
        }
        return $result_data;
        //}

    }
    private function ShopeeIShopIdItemId($Shopee_Product_URL){
        $data = $Shopee_Product_URL;
        $whatIWant = explode('.',substr($data, strpos($data, "-i.") + 3));
        $Data['Item_Id']=$whatIWant[1];
        $Data['Shop_Id']=$whatIWant[0];
        return $Data;
    }
    function ShopeePriceConvert($Price){
        $Real_Price=substr($Price, 0, -3);
        $Last_Two_Digits = substr($Real_Price, -2);
        $Final_Amount=substr($Real_Price,0,-2).'.'.$Last_Two_Digits;
        return $Final_Amount;
    }
    private function Shopee($id,$sku_id,$channelId)
    {
        $Get_Item_Shop_Detail = $this->ShopeeIShopIdItemId($id);
        $html = $this->actionCrawlLazadaWeb('https://shopee.com.my/api/v2/item/get?itemid=' . $Get_Item_Shop_Detail['Item_Id'] . '&shopid=' . $Get_Item_Shop_Detail['Shop_Id'] . '');
        //echo 'https://shopee.com.my/api/v2/item/get?itemid=' . $Get_Item_Shop_Detail['Item_Id'] . '&shopid=' . $Get_Item_Shop_Detail['Shop_Id'] . '';die;
        $Product_Json_Decode = json_decode($html);
        //$this->debug($Product_Json_Decode);
        // Set the product price
        /*$result_data['price'] = '';
        $result_data['product_name'] = '';
        $result_data['seller'] = '-';
        $result_data['product_sku'] = $id;
        $result_data['channel_id'] = '2';*/
        //print_r($Product_Json_Decode);
        if (isset($Product_Json_Decode->item->price)) {

            $result_data['price'] = $this->ShopeePriceConvert($Product_Json_Decode->item->price);


            // Set the product name
            $result_data['product_name'] = $Product_Json_Decode->item->name;
            // we don't have the seller name in shopee crawl.
            $result_data['seller'] = '-';

            $result_data['product_sku'] = $id;
            $result_data['channel_id'] = $channelId;
            if (isset($result_data['price'])) {
                $this->AddProductCrawlLog([
                    'sku_id' => $sku_id,
                    'channel_id' => $_GET['channel'],
                    'product_id' => $id,
                    'price' => isset($result_data['price']) ? $result_data['price'] : '',
                    'link_status' => '200',
                ]);
            } else {
                $this->AddProductCrawlLog([
                    'sku_id' => $sku_id,
                    'channel_id' => $_GET['channel'],
                    'product_id' => $id,
                    'price' => isset($result_data['price']) ? $result_data['price'] : '',
                    'link_status' => '404',
                ]);
            }
            return $result_data;
        }



    }

    public function actionCrawlAddSku(){
        $SkuCrawlObj=new SkusCrawl();
        $skulist=$SkuCrawlObj->GetCrawlSkuList();
        $searchModel = new SkusCrawlSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'sku_list' =>$skulist
        ]);
    }
    public function actionView($id)
    {
        $sku=$this->exchange_values('id','sku',$id,'products');
        return $this->render('view', [
            'model' => $this->findModel($id),'sku'=>$sku,
        ]);
    }
    protected function findModel($id)
    {
        if (($model = SkusCrawl::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'Update'=>true
            ]);
        }
    }
    public function actionCreate()
    {
        $model = new SkusCrawl();

        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->ID]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'Update'=>false
            ]);
        }
    }

    public function actionDeleteProductId(){
        $DeletePid=new SkusCrawl();
        $status=$DeletePid->DeleteProductId($_POST['pid']);
        $json = array( 'success'=>$status);
        return json_encode($json);
    }
    public function actionAddNewSkus(){
        $sku_id = $this->exchange_values('sku','id',$_POST['sku_id'],'products');
        //echo $sku_id;die;
        $result_array=array();
        if(isset($_POST['lazada_id']) && $_POST['lazada_id']!=''){
            $GetLazadaPIdDetail = SkusCrawl::find()->where(['sku_id'=>$sku_id,'channel_id'=>1])->asArray()->all();
            //$this->debug($GetLazadaPIdDetail);
            $explode_ids = explode('?',$GetLazadaPIdDetail[0]['product_ids']);
            $explode_ids[] = $_POST['lazada_id'];
            $updateSku_Crawl = SkusCrawl::findOne(['ID'=>$GetLazadaPIdDetail[0]['ID']]);
            $updateSku_Crawl->product_ids=implode('?',$explode_ids);
            $updateSku_Crawl->update();
            if( !empty($updateSku_Crawl->errors) ){
                //$this->debug($updateSku_Crawl->errors);
            }else{
                $result_array['lazada'] = 'success';
            }

        }
        if(isset($_POST['elevenstreet_id']) && $_POST['elevenstreet_id']!='' ){

            $exist=0;
            $GetLazadaPIdDetail = SkusCrawl::find()->where(['sku_id'=>$sku_id,'channel_id'=>3])->asArray()->all();
            //$this->debug($GetLazadaPIdDetail);


            if ( empty($GetLazadaPIdDetail) ){
                $updateSku_Crawl = new SkusCrawl();
                $updateSku_Crawl->channel_id=3;
                $updateSku_Crawl->sku_id=$sku_id;
                $updateSku_Crawl->product_ids=implode('?',$explode_ids);
                $updateSku_Crawl->save();
            }else{
                $updateSku_Crawl = SkusCrawl::findOne(['ID'=>$GetLazadaPIdDetail[0]['ID']]);
                $explode_ids = explode('?',$GetLazadaPIdDetail[0]['product_ids']);
                $explode_ids[] = $_POST['elevenstreet_id'];
                $updateSku_Crawl->product_ids = implode('?',$explode_ids);
                $updateSku_Crawl->update();
            }

            if( !empty($updateSku_Crawl->errors) ){
                $this->debug($updateSku_Crawl->errors);
            }else{
                $result_array['elevenstreet'] = 'success';
            }
        }
        if(isset($_POST['shopee_id']) && $_POST['shopee_id']!='' ){

            $GetLazadaPIdDetail = SkusCrawl::find()->where(['sku_id'=>$sku_id,'channel_id'=>2])->asArray()->all();
            if (empty($GetLazadaPIdDetail)){
                $Create_Shopee_Row=new SkusCrawl();
                $Create_Shopee_Row->sku_id=$sku_id;
                $Create_Shopee_Row->channel_id=2;
                $Create_Shopee_Row->product_ids='';
                $Create_Shopee_Row->save();
                $GetLazadaPIdDetail = SkusCrawl::find()->where(['sku_id'=>$sku_id,'channel_id'=>2])->asArray()->all();
            }
            //$this->debug($GetLazadaPIdDetail);
            $explode_ids = explode('?',$GetLazadaPIdDetail[0]['product_ids']);
            $explode_ids[] = $_POST['shopee_id'];
            $updateSku_Crawl = SkusCrawl::findOne(['ID'=>$GetLazadaPIdDetail[0]['ID']]);
            $product_ids=ltrim(implode('?',$explode_ids),'?');
            $updateSku_Crawl->product_ids=str_replace("'",'',$product_ids);
            $updateSku_Crawl->update();
            if( !empty($updateSku_Crawl->errors) ){
                //$this->debug($updateSku_Crawl->errors);
            }else{
                $result_array['shopee'] = 'success';
            }
        }
        return json_encode($result_array);
    }
    private function LazadaFetchRating($id){

        $product_link = 'https://www.lazada.com.my/products/sharp-air-purifier-grey-fpf30lh-i429001010-s623804201.html?spm=a2o4k.searchlistcategory.list.35.5524555doPhNi9&search=1';
        $html = file_get_html($product_link);

    }
    public function actionCrawlLazadaWeb($url){
        // Get Api key of crawler
        $ApiKey=Settings::find()->where(['name'=>'crawler_api_key'])->asArray()->all();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            "http://api.scraperapi.com/?key=".$ApiKey[0]['value']."&url=".urlencode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        $Add_Api_Hit = new CrawlApiHits();
        $Add_Api_Hit->url = ($url);
        $Add_Api_Hit->datetime = date('Y-m-d h:i:s');
        $Add_Api_Hit->save();
        if (strpos($url, 'shopee.com.my') !== false) {
            return $response;
        }else{
            $html = new \simple_html_dom();
            return $html->load($response);
        }
    }
    private function GetProductsListBySkus($ChannelId,$Sku_Id){
        $GetProductIds=SkusCrawl::find()->where(['channel_id'=>$ChannelId,'sku_id'=>$Sku_Id])->asArray()->all();
        if (!empty($GetProductIds))
            $ProductListArr=explode('?',$GetProductIds[0]['product_ids']);
        else{
            echo 'So Sorry, But this sku don\'t have the product list for crawling.';
            die;
        }
        return $ProductListArr;
    }
    public function actionFetchRatingsLazada(){

        if( isset($_GET['sku_id']) ){
            $Search_Sku= Products::find()->where(['sku'=>$_GET['sku_id'],'is_active'=>1])->asArray()->all();
            $Sku_List = $Search_Sku;
        }else{
            $Sku_List = SkusCrawl::findBySql("SELECT sku_crawl.*,pcp.sku FROM skus_crawl sku_crawl
                                                  INNER JOIN products pcp on
                                                  pcp.id = sku_crawl.sku_id
                                                  WHERE
                                                  pcp.is_active = 1 AND sku_crawl.channel_id = 1 AND sku_crawl.id > 66")->asArray()->all();
        }

        // adding the library of html dom to read the tags of html

        include('../library/simplehtmldom_1_5/simple_html_dom.php');
        foreach ( $Sku_List as $Parent_Key=>$Parent_Value ){

            // Get the product list of the sku from skus_crawl table

            $ProductList=$this->GetProductsListBySkus(1,$Parent_Value['sku_id']);
            foreach ( $ProductList as $key=>$value ){

                $product_link='';
                $result_data=[];
                $html = $this->actionCrawlLazadaWeb('https://www.lazada.com.my/catalog/?q='.$value.'&_keyori=ss&from=input&spm=a2o4k.pdp.search.go.41a08cc8cZVfRf');

                // Get the product link
                foreach($html->find('script') as $element){
                    $data = (json_decode($element->innertext));
                    if( isset($data->itemListElement[0]->url) ){
                        $product_link = ($data->itemListElement[0]->url);
                    }
                }
                if (!isset($product_link) || $product_link=='')
                    continue;
                $Crawl_Product = $this->actionCrawlLazadaWeb($product_link);

                // fetch the over average rating

                foreach($Crawl_Product->find('.mod-rating .content .left .summary .score .score-average') as $element)
                    $result_data['rating_average'] = strip_tags($element->innertext);

                // fetch the rating star wise
                $star_decrement=5;
                foreach($Crawl_Product->find('.mod-rating .content .left .detail ul li .percent') as $element){
                    $result_data[$star_decrement] = strip_tags($element->innertext);
                    $star_decrement--;
                }

                // fetch seller name
                foreach($Crawl_Product->find('.seller-name__detail a') as $element){
                    $result_data['sold_by'] = strip_tags($element->innertext);
                    break;
                }

                $Insert_Rating = new CrawlRatings();
                $Insert_Rating->channel_id= (string) 1;
                $Insert_Rating->sku_id=$Parent_Value['sku_id'];
                $Insert_Rating->sku = (isset($_GET['sku_id']) ? $_GET['sku_id'] : $Parent_Value['sku']);
                $Insert_Rating->product_id=$value;
                $Insert_Rating->seller_name= isset($result_data['sold_by']) ? $result_data['sold_by'] : '';
                $Insert_Rating->five_star= isset($result_data[5]) ? $result_data[5] : '';
                $Insert_Rating->four_star= isset($result_data[4]) ? $result_data[4] : '';
                $Insert_Rating->three_star=isset($result_data[3]) ? $result_data[3] : '';
                $Insert_Rating->two_star=isset($result_data[2]) ? $result_data[2] : '';
                $Insert_Rating->one_star=isset($result_data[1]) ? $result_data[1] : '';
                $Insert_Rating->overall=isset($result_data['rating_average']) ? $result_data['rating_average'] : '';
                $Insert_Rating->rating_created_at=date('Y-m-d H:i:s');
                $Insert_Rating->save();
                if (!empty($Insert_Rating->errors)){
                    $this->debug($Insert_Rating->errors);
                }

            }
        }







    }

}