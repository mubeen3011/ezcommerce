<?php

namespace backend\controllers;
use backend\util\BlueExUtil;
use backend\util\CourierUtil;
use backend\util\FedExUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\LCSUtil;
use backend\util\MagentoUtil;
use backend\util\OrderUtil;
use backend\util\PrestashopUtil;
use backend\util\Product360Util;
use backend\util\ProductsUtil;
use backend\util\ShopeeUtil;
use backend\util\TCSUtil;
use backend\util\UspsUtil;
use common\models\BulkOrderShipment;
use common\models\Channels;
use common\models\Couriers;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrderShipment;
use common\models\Products;
use common\models\search\OrderItemsSearch;
use common\models\Settings;
use common\models\WarehouseCouriers;
use common\models\Warehouses;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii;
use backend\util\UpsUtil;
use yii\web\UploadedFile;
class CourierController extends Controller
{

    /*
     * list all couriers
     */
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
            ]
        ];
    }

    public function actionIndex()
    {
          $sql="SELECT c.*,GROUP_CONCAT(w.name) AS warehouse_binded
                FROM `couriers` c 
                LEFT JOIN
                  `warehouse_couriers` wc
                ON
                  wc.courier_id=c.id
                LEFT JOIN
                  `warehouses` w
                ON 
                  w.id=wc.warehouse_id
                  GROUP BY `c`.`id`";
          $couriers=Couriers::findBySql($sql)->asArray()->all();
          return   $this->render('index',['couriers'=>$couriers]);
    }

    /*
     * create/add courier
     */
    public function actionCreate()
    {
        $model = new Couriers();
        if(Yii::$app->request->post()) {

            $model->configuration = isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']) : NULL;
            $model->configuration_test = isset($_POST['configuration_test']) && !empty($_POST['configuration_test']) ? json_encode($_POST['configuration_test']) : NULL;
        }
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $this->courier_warehouses_mapping($model->id); // assign warehouses
            $model->icon= UploadedFile::getInstance($model, 'icon');
            if($model->icon){
                $model->icon->saveAs('logos/' . $model->icon->baseName . '.' . $model->icon->extension);
                $model->icon='/logos/' . $model->icon->baseName . '.' . $model->icon->extension;
                $model->save();
            }

            yii::$app->session->setFlash('success','created');
            return $this->redirect(['courier/index']);
        }

        $warehouses = Warehouses::find()->where(['is_active'=>1])->all();
        return $this->render('create', [
            'model' => $model,
            'warehouses' => $warehouses,
        ]);
    }

    /*
     * update courier
     */

    public function actionUpdate($id)
    {
        $model =  Couriers::findone(['id' =>$id]);//$this->findModel($id);
        if(!$model)
            return $this->redirect('/courier');

        if(Yii::$app->request->post()){
            $model->configuration=isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']):NULL;
            $model->configuration_test=isset($_POST['configuration_test']) && !empty($_POST['configuration_test']) ? json_encode($_POST['configuration_test']):NULL;
            if ( $_FILES['Couriers']['tmp_name']['icon']=='' ){
                $old_icon = $model->icon;
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $this->courier_warehouses_mapping($model->id); // assign warehouses
            $model->icon= UploadedFile::getInstance($model, 'icon');

            if($model->icon){
                $model->icon->saveAs('logos/' . $model->icon->baseName . '.' . $model->icon->extension);
                $model->icon='/logos/' . $model->icon->baseName . '.' . $model->icon->extension;
                $model->save();
            }
            $model =  Couriers::findone(['id' =>$id]);//$this->findModel($id);
            if ($model->icon=='' && isset($old_icon)){
                $model->icon = $old_icon;
                $model->update();
            }
            Yii::$app->session->setFlash('success', "Record updated");
        }
        $warehouses = Warehouses::find()->where(['is_active'=>1])->all();
        $attached_Warehouses=WarehouseCouriers::find()->where(['courier_id'=>$model->id])->all();
        return $this->render('update', [
            'model' => $model,
            'warehouses' => $warehouses,
            'attached_warehouses' => array_column($attached_Warehouses,'warehouse_id'),
        ]);

    }

    /**
     * @param $courier_id
     * assign warehouses to courier
     */

    private function courier_warehouses_mapping($courier_id)
    {

        WarehouseCouriers::deleteAll("courier_id=$courier_id");
        $post_data = Yii::$app->request->post();
        if (isset($post_data['Couriers']['warehouses']))
        {
            for($w=0;$w<count($post_data['Couriers']['warehouses']);$w++)
                $batch[]=[$post_data['Couriers']['warehouses'][$w],$courier_id,'1'];

            yii::$app->db->createCommand()->batchInsert('warehouse_couriers', ['warehouse_id', 'courier_id','is_active'], $batch)->execute();

        }
        return;
    }

    /****
     * couriers list for bulk shipment
     */
    public function actionBulkCourierList()
    {
        $order_ids = yii::$app->request->post('order_ids');
        if(!$order_ids && !is_array($order_ids))
            return $this->asJson(['status'=>'failure','msg'=>'No order selected','data'=>'No order selected']);

      //  $order_ids_implode=implode(",",$order_ids);
        $warehouses=[];
        foreach($order_ids as $order_id)
        {
            $order = Orders::findone(['id' => $order_id]);
            $items = OrderItems::find()->where(['order_id' => $order_id, 'item_status' => 'pending'])
                ->andWhere(['IS NOT', 'fulfilled_by_warehouse', null])
                ->andWhere(['IS', 'tracking_number', null])
                ->asArray()->all();

            if (empty($items))
                return $this->asJson(['status' => 'failure', 'data'=>self::render_partial('popups/bulk_couriers_list',['error'=>'No item eligible to ship in Order #'.$order->order_number])]);

            $order_warehouses=array_column($items,'fulfilled_by_warehouse'); // get warehouses of that order
            $order_warehouses=array_unique($order_warehouses);
           // array_merge($order_warehouses,$warehouses);
            //$warehouses[]=array_unique($order_warehouses);
            if(count(array_unique($order_warehouses))!==1)
                return $this->asJson(['status'=>'failure','data'=>self::render_partial('popups/bulk_couriers_list',['error'=>'All Items should have same warehouse  in Order #'.$order->order_number])]);

            foreach($items as $item){ // get warehouses
                if(!in_array($item['fulfilled_by_warehouse'],$warehouses) )
                    $warehouses[]=$item['fulfilled_by_warehouse'];
            }

        }
        if($warehouses) // if warehouses attached
        {
            $warehouses=implode(",",$warehouses);
            $couriers=CourierUtil::getWarehouseCouriers($warehouses);
            $modal_content= self::render_partial('popups/bulk_couriers_list',
                [
                    'order'=>$order,
                    'order_items'=>$items,
                    'couriers'=>$couriers,
                    'error'=>$couriers ? '':'No courier attached with warehouse'
                ]
            );
            return $this->asJson(['status'=>'success','address'=>'','msg'=>'record found','data'=>$modal_content]);
        }
        return $this->asJson(['status' => 'failure', 'data'=>self::render_partial('popups/bulk_couriers_list',['error'=>'No couriers found'])]);
    }

    /***
     * bulk courier selected
     */
    public function actionCourierSelectedForBulkShip()
    {
        $order_ids = yii::$app->request->post('order_ids');
        $courier_id = yii::$app->request->post('courier_id');
        if(!$order_ids && !is_array($order_ids))
            return $this->asJson(['status'=>'failure','msg'=>'No order selected','data'=>'No order selected']);
        if(!$courier_id)
            return $this->asJson(['status'=>'failure','msg'=>'Select Courier','data'=>'Select Courier']);
        $count=0;
        foreach($order_ids as $order_id){
                $already_exists=BulkOrderShipment::findOne(['order_id'=>$order_id]);
                if($already_exists)
                        continue;

                $ship=new BulkOrderShipment();
                $ship->order_id=$order_id;
                $ship->courier_id=$courier_id;
                $ship->added_at=date('Y-m-d H:i:s');
                $ship->updated_at=date('Y-m-d H:i:s');
                $ship->created_by=Yii::$app->user->identity->role_id;
                $ship->save();
                $count++;
        }
        yii::$app->session->setFlash('success',$count .' Orders processed for shipping');
        return $this->asJson(['status'=>'success','msg'=>$count .' Orders processed for shipping',]);
    }


    /*
     * ship now option selected
     */
    public function actionCourierSelection()
    {
        $order_id = yii::$app->request->post('order_id'); //table pk
        $selected_type = yii::$app->request->post('selected_type'); // full order selected or single item selected
        $order_item_pk = yii::$app->request->post('order_item_pk');

        $andwhere = $selected_type == "order_item" ? ['id' => $order_item_pk] : [];
        $order = Orders::find()->where(['id' => $order_id])->one();
        $items = OrderItems::find()->where(['order_id' => $order_id, 'item_status' => 'pending'])
                ->andWhere(['IS NOT', 'fulfilled_by_warehouse', null])
                ->andWhere(['IS', 'tracking_number', null])
                ->andwhere($andwhere)
                ->asArray()->all();
        if (empty($items))
            return $this->asJson(['status' => 'failure', 'data'=>self::render_partial('popups/courier_selection',['error'=>'No item eligible to ship'])]);

        $customer = OrderUtil::GetCustomerDetailByPk($order_id);
        //$address=self::address_input_validate($customer); // match state and country abbrevation
        // check if all items attached to same warehouse in one package
        if ($selected_type=="order")
        {
            $uniform_warehouse_check= self::_check_items_same_warehouse($items);
            if(!$uniform_warehouse_check) {
                return $this->asJson(['status'=>'failure','data'=>self::render_partial('popups/courier_selection',['error'=>'All Items should have same warehouse in 1 package'])]);
            }
        }
        $couriers=CourierUtil::getWarehouseCouriers($items[0]['fulfilled_by_warehouse']);
        // For marketplace own shipping method
        $marketPlaceCouriers = CourierUtil::GetMarketPlaceCourier($order_id);
        $couriers = array_merge($couriers,$marketPlaceCouriers);

        $modal_content= self::render_partial('popups/courier_selection',
            [
                'order'=>$order,
                'order_items'=>$items,
                'selected_type'=>$selected_type,
                'order_item_pk'=>$order_item_pk,
                'couriers'=>$couriers,
                'error'=>$couriers ? '':'No courier attached with warehouse'
            ]
        );
        return $this->asJson(['status'=>'success','address'=>$customer,'msg'=>'record found','data'=>$modal_content]);

    }

    private function render_partial($view,$inputs)
    {
        return $this->renderPartial($view,$inputs);
    }

    /*
     * check if all order items from same warehouse or not
     */

    public function _check_items_same_warehouse($items)
    {
        $items=array_column($items,'fulfilled_by_warehouse');
        if(count(array_unique($items))===1)
               return true;

        return false;

    }



    /*
     * courier selected after options
     */
    public function actionCourierSelected()
    {

        $courier_id = yii::$app->request->post('courier_id');
        $order_id = yii::$app->request->post('order_id'); //table pk
        $selected_type = yii::$app->request->post('selected_type'); // full order selected or single item selected
        $order_item_pk = yii::$app->request->post('order_item_pk');  // if single item selected order items table primary key
        $warehouse_address=null;
        $dimensions=""; // only used if package has 1 item to ship
        $courier=Couriers::findone(['id'=>$courier_id]);
        $order = Orders::findone(['id' => $order_id]);
        $andwhere = $selected_type == "order_item" ? ['id' => $order_item_pk] : [];
        $items = OrderItems::find()->where(['order_id' => $order_id, 'item_status' => 'pending'])
                ->andWhere(['IS NOT', 'fulfilled_by_warehouse', null])
               ->andWhere(['IS', 'tracking_number', null])
                ->andwhere($andwhere)
                ->asArray()->all();
        $customer = OrderUtil::GetCustomerDetailByPk($order_id);
        //self::debug($customer);
        $channel=Channels::findone(['id'=>$order->channel_id]);
        if ($items) {
            $warehouse_address=Warehouses::findone(['id',$items[0]['fulfilled_by_warehouse']]);
        }

        if(count($items)==1 && $items[0]['quantity']==1 ) // if 1 item in package and its qty is 1 get dimensions and weight
            $dimensions=ProductsUtil::getProductDimensionsAndWeight($items[0]['item_sku']);

        if(isset($_POST['customer_address']) && !empty($_POST['customer_address'])) // if already input by admin validate that
        {
            $address=$_POST['customer_address'];
        } else if(in_array($courier->type,['internal','lcs','tcs','blueex'])){ // if company own curier
            $address=self::address_input_validate_internal_courier($customer); // match state and country abbrevation
        }
        else  // if not input by admin then pick order address
        {
            $address=self::address_input_validate($customer); // match state and country abbrevation
        }
        //self::debug($address);

        if(!$address) {
            $popup = self::render_partial($courier->type.'/courier_selected', ['status' => 'failure', 'error' => 'cust_address_inputs_invalid', 'msg' => 'Address Inputs Invalid/Incomplete', 'order' => $order, 'items' => $items, 'cust_address' => $address, 'warehouse_address' => $warehouse_address,'courier'=>$courier]);
            return $this->asJson(['status' => 'failure','warehouse'=>$warehouse_address,'msg'=>'address inputs failure', 'data' => $popup]);
        }

        if($courier)
        {
            if(strtolower($courier->type)=="ups")
            {

                $response=UpsUtil::ValidateAddress($courier,$address);
                return $this->asJson(['status'=>$response['status'],
                                'warehouse'=>$warehouse_address,
                                'msg'=>$response['msg'],
                                'data'=>self::render_partial('ups/courier_selected',
                                    ['status'=>$response['status'],
                                        'error'=>$response['error'],
                                        'msg'=>$response['msg'],
                                        'order'=>$order,
                                        'items'=>$items,
                                        'dimensions'=>$dimensions,
                                        'cust_address'=>$address,
                                        'warehouse_address'=>$warehouse_address,
                                        'channel'=>$channel,'suggestions'=>isset($response['suggestions']) ? $response['suggestions']:NULL])]);

            }
            elseif(strtolower($courier->type)=="fedex")
            {
                if (!extension_loaded('soap')) {
                    $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',['error_response'=>'Soap Extension is not enabled in your php.ini , Please enable it by going into php.ini and un comment ;extension=php_soap.dll then restart your apache2 service. And then try again. Thank you']);
                    return json_encode( [ 'status'=>'success','data' => $Failed ] );
                }
                $CustomerInfo = OrderUtil::GetCustomerDetail($order->order_number);
                if ( isset($_POST['customer_address']['address']) ){
                    $CustomerInfo[0]['customer_address']=$_POST['customer_address']['address'];
                    $CustomerInfo[0]['customer_city']=$_POST['customer_address']['city'];
                    $CustomerInfo[0]['customer_state']=$_POST['customer_address']['state'];
                    $CustomerInfo[0]['customer_postcode']=$_POST['customer_address']['zip'];
                    $CustomerInfo[0]['customer_country']=$_POST['customer_address']['country'];
                }
                //echo '<pre>';print_r($CustomerInfo);die;
                //echo '<pre>';print_r($CustomerInfo);
                $AddressValidationFedex = FedExUtil::AddressValidation($courier_id,$CustomerInfo);
                //echo '<pre>';print_r($AddressValidationFedex);die;

                if ($AddressValidationFedex->HighestSeverity=='ERROR' && $AddressValidationFedex->Notifications->Code=='1000'){
                    $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',['error_response'=>$AddressValidationFedex->Notifications->Message]);
                    return json_encode( [ 'status'=>'success','data' => $Failed ] );
                }
                /*if ($AddressValidationFedex->HighestSeverity=='ERROR' && $AddressValidationFedex->Notifications->Code=='1000'){
                    $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',['error_response'=>$AddressValidationFedex->Notifications->Message]);
                    return json_encode( [ 'status'=>'success','data' => $Failed ] );
                }else if( $AddressValidationFedex->HighestSeverity=='SUCCESS' && isset($AddressValidationFedex->AddressResults->State) && $AddressValidationFedex->AddressResults->State == 'RAW'  ){
                    $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',['error_response'=>'Unfortunately FedEx does not recognize the address of this customer.']);
                    return json_encode( [ 'status'=>'success','data' => $Failed ] );
                }else if ($AddressValidationFedex->HighestSeverity=='SUCCESS' && isset($AddressValidationFedex->AddressResults->State) && $AddressValidationFedex->AddressResults->State == 'NORMALIZED'){
                    $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',['error_response'=>'FedEx recognize the customer country but not city or postal code.']);
                    return json_encode( [ 'status'=>'success','data' => $Failed ] );
                }*/

                /*echo '<pre>';
                print_r($AddressValidationFedex);
                die;*/
                $modal_content = $this->renderPartial('../sales/_render-partial-shipment/fedex/content',[
                    'OrderItems'=>$items,
                    'OrderId'=>$order->order_number,
                    'WarehouseId'=>$warehouse_address->id,
                    'CourierId' => $courier_id,
                    'order_number'=>$order->order_number,
                    'channel_id'=>$channel->id,
                    'customerInfo'=>$CustomerInfo,
                    'warehouseInfo'=>$warehouse_address,
                    'marketplace' => $channel->marketplace,
                    'AddressValidation'=>$AddressValidationFedex,
                    'OrderDetails' => $order,
                    'OrderItemIds'=>implode(',',array_column($items,'id')),//$_GET['order_item_id']
                ]);
                return $this->asJson(['status'=>'success','data'=>$modal_content]);
            }
            elseif ( strtolower($courier->type)=="lazada-fbl" ){
                $OrderItems = LazadaUtil::GetOrderItems($order->order_number,$channel->id);
                $OrderDetails = LazadaUtil::GetOrderDetail($order->order_number,$channel->id);
                $modal_header = $this->renderPartial('/sales/_render-partial-shipment/lazada/header',['order_number'=>$order_id]);
                $modal_content = $this->renderPartial('/sales/_render-partial-shipment/lazada/content',['OrderItems'=>$OrderItems,'OrderDetails'=>$OrderDetails
                    ,'order_number'=>$order->order_number,'ShippingProviders'=>LazadaUtil::GetShipmentProviders($channel->id,$order_id),'channel_id'=>$channel->id]);
                return json_encode(['status'=>'success','header'=>$modal_header,'data'=>$modal_content]);
            }
            elseif (strtolower($courier->type)=="shopee-fbs"){

                $shopOrderId = HelpUtil::exchange_values('id','order_number',$order_id,'orders');
                $channel = Channels::find()->where(['id'=>$channel->id])->one();
                $response = json_decode(ShopeeUtil::GetParameterForInit($channel,$shopOrderId));
                $modal_header = $this->renderPartial('/sales/_render-partial-shipment/shopee/header',['order_number'=>$order_id]);
                $modal_content = $this->renderPartial('/sales/_render-partial-shipment/shopee/logistics-options',
                    [
                    'channel_id'=>$channel->id,
                    'order_id'=>$order_id,
                    'availableLogisticsOptions'=>$response
                    ]
                );


                return json_encode(['status'=>'success','header'=>$modal_header,'data'=>$modal_content]);
            }
            elseif(strtolower($courier->type)=="usps")
            {
                $response=UspsUtil::ValidateAddress($courier,$address);
                return $this->asJson(['status'=>$response['status'],
                                    'warehouse'=>$warehouse_address,
                                    'msg'=>$response['msg'],
                                     'data'=>self::render_partial('usps/courier_selected',
                                                ['status'=>$response['status'],
                                                 'error'=>$response['error'],
                                                 'msg'=>$response['msg'],
                                                 'order'=>$order,
                                                    'items'=>$items,
                                                    'dimensions'=>$dimensions,
                                                    'cust_address'=>$address,
                                                    'warehouse_address'=>$warehouse_address,
                                                    'channel'=>$channel,
                                                    'suggestions'=>isset($response['suggestions']) ? $response['suggestions']:NULL])]);

            }
            elseif(strtolower($courier->type)=="tcs")
            {
                //$response=UspsUtil::ValidateAddress($courier,$address);

                $response=TCSUtil::ValidateAddress($courier,$address);
               // echo "<pre>";print_r($address);exit;


                $cities= Settings::findOne(['name'=>'tcs_cities']);
               // echo "<pre>";print_r(json_decode($cities->value));exit;
                // $response=['status'=>'success','msg'=>'Self attested address','error'=>'city is not matching'];
                return $this->asJson(['status'=>$response['status'],
                    'warehouse'=>$warehouse_address,
                    'msg'=>$response['msg'],
                    'data'=>self::render_partial('tcs/courier_selected',
                        ['status'=>$response['status'],
                            'error'=>$response['error'],
                            'msg'=>$response['msg'],
                            'order'=>$order,
                            'items'=>$items,
                            'dimensions'=>$dimensions,
                            'cust_address'=>$address,
                            'warehouse_address'=>$warehouse_address,
                            'cities'=>json_decode($cities->value),
                            'courier'=>$courier,
                            'channel'=>$channel])]);

            }
            elseif(strtolower($courier->type)=="lcs")
            {
                //$response=UspsUtil::ValidateAddress($courier,$address);

                $response=LCSUtil::ValidateAddress($courier,$address);
                $cities= Settings::findOne(['name'=>'LCS_cities']);
               // $response=['status'=>'success','msg'=>'Self attested address','error'=>'city is not matching'];
                return $this->asJson(['status'=>$response['status'],
                    'warehouse'=>$warehouse_address,
                    'msg'=>$response['msg'],
                    'data'=>self::render_partial('lcs/courier_selected',
                        ['status'=>$response['status'],
                            'error'=>$response['error'],
                            'msg'=>$response['msg'],
                            'order'=>$order,
                            'items'=>$items,
                            'dimensions'=>$dimensions,
                            'cust_address'=>$address,
                            'warehouse_address'=>$warehouse_address,
                            'cities'=>json_decode($cities->value),
                            'courier'=>$courier,
                            'channel'=>$channel])]);

            }
            elseif(strtolower($courier->type)=="blueex")
            {
                $response=BlueExUtil::ValidateAddress($courier,$address);
                $cities= Settings::findOne(['name'=>'blueex_cities']);
                return $this->asJson(['status'=>$response['status'],
                    'warehouse'=>$warehouse_address,
                    'msg'=>$response['msg'],
                    'data'=>self::render_partial('blueex/courier_selected',
                        ['status'=>$response['status'],
                            'error'=>$response['error'],
                            'msg'=>$response['msg'],
                            'order'=>$order,
                            'items'=>$items,
                            'dimensions'=>$dimensions,
                            'cust_address'=>$address,
                            'warehouse_address'=>$warehouse_address,
                            'cities'=>json_decode($cities->value),
                            'courier'=>$courier,
                            'channel'=>$channel])]);
            }
            elseif(strtolower($courier->type)=="internal")
            {
                $response=['status'=>'success','msg'=>'Self attested address','error'=>''];
                return $this->asJson(['status'=>$response['status'],
                    'warehouse'=>$warehouse_address,
                    'msg'=>$response['msg'],
                    'data'=>self::render_partial('internal/courier_selected',
                        ['status'=>$response['status'],
                            'error'=>$response['error'],
                            'msg'=>$response['msg'],
                            'order'=>$order,
                            'items'=>$items,
                            'dimensions'=>$dimensions,
                            'cust_address'=>$address,
                            'warehouse_address'=>$warehouse_address,
                            'channel'=>$channel,
                            'courier'=>$courier
                        ])]);

            }

        }
    }
    private function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }
    /*
     * check if address inputs completed
     */
    private function address_input_validate($address=null)
    {
            $address_return=[];
            if(isset($address['customer_state']) && !empty($address['customer_state']) && isset($address['customer_city']) && !empty($address['customer_city']) && isset($address['customer_postcode']) && !empty($address['customer_postcode']) && isset($address['customer_country']) && !empty($address['customer_country']))
            {
                $address_return['state']=HelpUtil::getCountryStateShortCode($address['customer_state']);
                $address_return['address']=$address['customer_address'];
                $address_return['city']=trim($address['customer_city']);
                $address_return['zip']=$address['customer_postcode'];
                $address_return['fname']=$address['customer_fname'];
                $address_return['lname']=$address['customer_lname'];
                $address_return['phone']=$address['customer_number'];
                $address_return['country']=HelpUtil::getCountryStateShortCode($address['customer_country']);
                if(isset($address['shipping_email']) && $address['shipping_email'])
                    $address_return['email']=$address['shipping_email'];
                elseif(isset($address['billing_email']) && $address['billing_email'])
                    $address_return['email']=$address['billing_email'];
                else
                    $address_return['email']="";
            }
            return  $address_return;

    }
    private function address_input_validate_internal_courier($address=null)
    {
        $address_return=[];
            $address_return['state']=$address['customer_state'];
            $address_return['address']=$address['customer_address'];
            $address_return['city']=$address['customer_city'];
            $address_return['zip']=$address['customer_postcode'];
            $address_return['fname']=$address['customer_fname'];
            $address_return['lname']=$address['customer_lname'];
            $address_return['phone']=$address['customer_number'];
            $address_return['country']=$address['customer_country'];
            if(isset($address['shipping_email']) && $address['shipping_email'])
                $address_return['email']=$address['shipping_email'];
            elseif(isset($address['billing_email']) && $address['billing_email'])
                $address_return['email']=$address['billing_email'];
            else
                $address_return['email']="";

        return  $address_return;

    }



    public function actionGetShippingRates_old()
    {
        $courier_id=yii::$app->request->post('courier_id');
        $dimensions=yii::$app->request->post('dimensions');  // height , width ,length ,weight
        $service=yii::$app->request->post('service');  // service id , name ,
        $package_type=yii::$app->request->post('package_type');  // package type ,
        $customer_address=yii::$app->request->post('customer_address');
        $warehouse=yii::$app->request->post('warehouse');
        //$customer_address=$this->address_input_validate($customer_address);
        $courier=Couriers::findone(['id'=>$courier_id]);
        if($courier){
            $shipper_account=json_decode($courier->configuration);
            $warehouse['shipper_number']=isset($shipper_account->account) ? $shipper_account->account:"";
        }


        $params=[
            'service'=>$service,
            'dimensions'=>$dimensions,
            'customer_address'=>$customer_address,
            'package_type'=>$package_type,
            'warehouse'=>$warehouse
        ];
        //print_r($params); die();
        if($courier && $params['service'] && $dimensions && isset($dimensions['pkg_weight']) && !empty($dimensions['pkg_weight'])) :
            $response=UpsUtil::getShippingRates($courier,$params);
            $popup= self::render_partial('ups/service_rates',['status'=>$response['status'],'service'=>isset($service['service_name'])? $service['service_name']:"",'charges'=>isset($response['charges']) ? $response['charges']:"" ,'msg'=>isset($response['msg']) ? $response['msg']:"" ,'courier'=>$courier]);
            return $this->asJson(['status'=>$response['status'],'msg'=>$response['msg'],'data'=>$popup]);
        endif;

        return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch inputs are invalid','data'=>self::render_partial('ups/service_rates',['status'=>'failure','msg'=>'Failed to fetch inputs are invalid','courier'=>$courier])]);

    }

    public function actionGetShippingRates()
    {
        $courier_id=yii::$app->request->post('courier_id');
        $order_id=yii::$app->request->post('order_id');
        $dimensions=yii::$app->request->post('dimensions');  // height , width ,length ,weight
        $package_type=yii::$app->request->post('package_type');  // package type ,
        $customer_address=yii::$app->request->post('customer_address');
        $warehouse=yii::$app->request->post('warehouse');
        //$customer_address=$this->address_input_validate($customer_address);
        $courier=Couriers::findone(['id'=>$courier_id]);
        $params=[
            'dimensions'=>$dimensions,
            'customer_address'=>$customer_address,
            'package_type'=>$package_type,
            'warehouse'=>$warehouse
        ];

        if ($courier)
        {
            if(strtolower($courier->type) == "usps") {
                 $ship_date=yii::$app->request->post('ship_date'); // shipping date
                 if(!$ship_date)
                     return $this->asJson(['status'=>'failure','msg'=>'Ship Date required','data'=>self::render_partial(strtolower($courier->type).'/service_rates',['status'=>'failure','msg'=>'Ship date required field','courier'=>$courier])]);

                 if($ship_date < date('Y-m-d'))
                     return $this->asJson(['status'=>'failure','msg'=>'Ship Date should be greater','data'=>self::render_partial(strtolower($courier->type).'/service_rates',['status'=>'failure','msg'=>'Ship date should be greater','courier'=>$courier])]);

                 $zip_codes=['from'=>$warehouse['zip'],'to'=>$customer_address['zip']];
                 $response=UspsUtil::getShippingRates($courier,$zip_codes,$dimensions,$package_type,$ship_date);
               // print_r($response); die();
                $popup= self::render_partial('usps/service_rates',['status'=>$response['status'],'services'=>isset($response['services'])? $response['services']:"" ,'msg'=>isset($response['msg']) ? $response['msg']:"" ,'courier'=>$courier]);
                return $this->asJson(['status'=>$response['status'],'services'=>isset($response['services'])? $response['services']:"" ,'msg'=>$response['msg'],'data'=>$popup]);

            } elseif(strtolower($courier->type) =="ups") {

                $shipper_account=json_decode($courier->configuration);
                $warehouse['shipper_number']=isset($shipper_account->account) ? $shipper_account->account:"";
                $params['service']=yii::$app->request->post('service');

                if($courier && $params['service'] && $dimensions && isset($dimensions['pkg_weight']) && !empty($dimensions['pkg_weight'])) :
                    $response=UpsUtil::getShippingRates($courier,$params);
                    $popup= self::render_partial('ups/service_rates',['status'=>$response['status'],'service'=>isset($service['service_name'])? $service['service_name']:"",'charges'=>isset($response['charges']) ? $response['charges']:"" ,'msg'=>isset($response['msg']) ? $response['msg']:"" ,'courier'=>$courier]);
                    return $this->asJson(['status'=>$response['status'],'msg'=>$response['msg'],'data'=>$popup]);
                endif;

            } elseif(strtolower($courier->type) =="lcs"){
              //  $params['order']=Orders::findone($order_id);
                $order_total=yii::$app->request->post('order_total');
                $params['order_total']=$order_total ? $order_total:0;
                $response=LCSUtil::getShippingRates($courier,$params);
                $popup= self::render_partial('lcs/service_rates',['status'=>$response['status'],'service'=>"",'charges'=>isset($response['charges']) ? $response['charges']:0,'msg'=>isset($response['msg']) ? $response['msg']:"" ,'order_total'=>$params['order_total']]);
                return $this->asJson(['status'=>$response['status'],'msg'=>$response['msg'],'data'=>$popup]);
            }
        }

        return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch inputs are invalid','data'=>self::render_partial(strtolower($courier->type).'/service_rates',['status'=>'failure','msg'=>'Failed to fetch inputs are invalid','courier'=>$courier])]);

    }
    /**
     * @return yii\web\Response
     * for ups
     */
    public function actionSubmitShipping()
    {
        //print_r($_POST); die();
        $courier_id = yii::$app->request->post('courier');
        $courier = Couriers::findone(['id' => $courier_id]);
        $order_items_pk = yii::$app->request->post('order_item_pk'); // primary key of order items // array or can single
       // $order_number = yii::$app->request->post('order_number');
        $order_id = yii::$app->request->post('order_id');
        $order=Orders::findone(['id'=>$order_id]);
        $items = OrderItems::find()->where(['IN','id' , $order_items_pk])->asArray()->all();
        $channel_id=yii::$app->request->post('channel_id');
        $channel=Channels::findone(['id'=>$channel_id]);

       // echo "<pre>";
       // print_r($courier); die();
        if($courier)
            $shipper_account=json_decode($courier->configuration);
        else
            return $this->asJson(['status'=>'failure','msg'=>'Failed to process ! try again']);

        $shipper = [
            'name' =>(isset($channel->name)&& $channel->name=="pedro") ? "Pedro Pakistan":yii::$app->request->post('shipper_name'), // check applied because same warehouse assign to both channel so shipper name should be by channel name // specially for spl
            'shipper_number' => isset($shipper_account->account) ? $shipper_account->account:"",
            'phone' => yii::$app->request->post('shipper_phone'),
            'address' => yii::$app->request->post('shipper_address'),
            'full_address' => yii::$app->request->post('shipper_full_address'),
            'state' => HelpUtil::getCountryStateShortCode(yii::$app->request->post('shipper_state')),
            'city' => yii::$app->request->post('shipper_city'),
            'zip' => yii::$app->request->post('shipper_zip'),
            'country' => HelpUtil::getCountryStateShortCode(yii::$app->request->post('shipper_country')),
        ];
        $customer = [
            'name' => yii::$app->request->post('cust_name'),
            'address' => yii::$app->request->post('cust_address'),
            'phone' => yii::$app->request->post('cust_phone'),
            'email' => yii::$app->request->post('cust_email'),
            'state' => HelpUtil::getCountryStateShortCode(yii::$app->request->post('cust_state')),
            'city' => yii::$app->request->post('cust_city'),
            'zip' => yii::$app->request->post('cust_zip'),
            'country' => HelpUtil::getCountryStateShortCode(yii::$app->request->post('cust_country')),
        ];
        $package = [
            'length' => yii::$app->request->post('package_length'),
            'width' => yii::$app->request->post('package_width'),
            'height' => yii::$app->request->post('package_height'),
            'weight' => yii::$app->request->post('package_weight'),
            'weight_oz' => yii::$app->request->post('package_weight_oz',0),
        ];
        $package_type = yii::$app->request->post('package_type');

        $service = [
            'code' => yii::$app->request->post('service'),
            'name' => yii::$app->request->post('service_name'),
            'amount'=> yii::$app->request->post('service_amount',NULL), // used in case of USPS
        ];

        if(isset($_POST['addon_code']) && !empty($_POST['addon_code'])) // usps
        {
            $addon_code=yii::$app->request->post('addon_code');
            $addon_amount=yii::$app->request->post('addon_amount');
            for($a=0;$a<count($addon_code);$a++){
                $addons[]=[
                    'AddOnType'=>$addon_code[$a],
                    'Amount'=>$addon_amount[$a]
                ];
            }
        }



       /* if(strtolower($courier->type)=="usps") // in case of usps service amount also needed
            $service['amount']=*/

        $params = ['shipper' => $shipper,
                   'customer' => $customer,
                   'package' => $package,
                   'package_type' => $package_type,
                   'service' => $service,
                   'order_number'=>$order->order_number,
                    'order'=>$order, // for invoice generation
                    'order_items'=>$items  //for invoice generation
                   ];
        //self::debug($params); die();
        /////////////////test
        /*$packing_slip=CourierUtil::generate_order_invoice($params);
        //$response['packing_slip']=$packing_slip; // merge packing slip in response array
        $result = ['status' => 'success', 'data' => self::render_partial($courier->type.'/success_response',['label'=>'','packing_slip'=>$packing_slip])];
        return $this->asJson($result);*/
        ///////////////////
       //die();

        if(strtolower($courier->type)=="ups") {
            $response = UpsUtil::submitShipping($courier, $params);
            $params['shipping_charges']=isset($response['amount_inc_taxes']) ? $response['amount_inc_taxes']:0;
        }
        elseif(strtolower($courier->type)=="usps"){
            $params['addons']=isset($addons) ? $addons:NULL; // addons added if
            $params['shipping_date']=yii::$app->request->post('ship_date'); // ship date
            $params['channel_id']=$channel->id; // for unique integrator generation
            $response = UspsUtil::submitShipping($courier,$params);
            $params['shipping_charges']=isset($response['amount_inc_taxes']) ? $response['amount_inc_taxes'] + $response['extra_charges']:0;
        }
        else if(strtolower($courier->type)=="internal"){
            $params['shipping_date']=yii::$app->request->post('ship_date'); // ship date
            $params['shipping_charges']=yii::$app->request->post('shipping_charges');
            $params['tracking_number']='EZ-SELF-'.time();
            $response=[
                        'status'=>'success',
                        'tracking_number'=>$params['tracking_number'],
                        'amount_inc_taxes'=>$params['shipping_charges'],
                        'amount_exc_taxes'=>$params['shipping_charges'],
                        'shipping_date'=>$params['shipping_date'],
                        'dimensions'=>$params['package'],
                        'label'=>CourierUtil::generate_order_label($params),
                        'courier_type'=>'internal',
                        'additional_info'=>['package_type'=>$params['package_type']]
                    ];
           // return $this->asJson(['status'=>'failure','msg'=>'Failed to process ss! try again']);
        }
        else if(strtolower($courier->type)=="tcs"){
            $order_total=yii::$app->request->post('order_total');
            $amount_to_collect=yii::$app->request->post('total_package_charges');
            if(in_array(strtolower($order->payment_method),['sample_gateway','hbl pay','online']) && $order->order_market_status!='pending_payment')
                $params['amount_to_collect']=0.00;
            else
                $params['amount_to_collect']=$amount_to_collect ? $amount_to_collect :$order->order_total;

            $params['order_total']=$order_total ? $order_total:$order->order_total;
            $params['grand_total']=$params['order_total']; // for invoice generation
            $params['instructions']=yii::$app->request->post('instructions');
            $params['fragile'] = yii::$app->request->post('fragile');
            $params['insurance'] = yii::$app->request->post('insurance');
            $response=TCSUtil::submitShipping($courier,$params);
            // return $this->asJson(['status'=>'failure','msg'=>'Failed to process ss! try again']);
        }
        else if(strtolower($courier->type)=="lcs"){
            $order_total=yii::$app->request->post('order_total');
            $params['shipping_charges']=yii::$app->request->post('shipping_charges'); // charges of shipping
            $amount_to_collect=yii::$app->request->post('total_package_charges'); // total cash to collect from customer
           /* if($amount_to_collect)
                $params['amount_to_collect']=$amount_to_collect;
            else
                $params['amount_to_collect']=$order_total;*/
            if(in_array(strtolower($order->payment_method),['sample_gateway','hbl pay','online']) && $order->order_market_status!='pending_payment')
                $params['amount_to_collect']=0.00;
            else
                $params['amount_to_collect']=$amount_to_collect ? $amount_to_collect :$order->order_total;

            $params['grand_total']=$params['amount_to_collect']; // for invoice generation
            $params['extra_shipping_charges']=$params['shipping_charges']; // for invoice generation to show
            $params['instructions']=yii::$app->request->post('instructions');
            $response=LCSUtil::submitShipping($courier,$params);
            // return $this->asJson(['status'=>'failure','msg'=>'Failed to process ss! try again']);
        }
        else if(strtolower($courier->type)=="blueex"){
            $order_total=yii::$app->request->post('order_total');
          //  $params['shipping_charges']=$order->order_shipping_fee ? $order->order_shipping_fee:0; // charges of shipping
          //  $params['extra_shipping_charges']=$params['shipping_charges']; // for invoice generation to show
            $params['order_total']=$order_total ? $order_total:$order->order_total;
            $params['grand_total']=$params['order_total']; // for invoice generation
            $response=BlueExUtil::submitShipping($courier,$params);
            // return $this->asJson(['status'=>'failure','msg'=>'Failed to process ss! try again']);
        }


        if ($response['status'] == 'failure') {
            return $this->asJson($response);
        } else {
            $packing_slip=CourierUtil::generate_order_invoice($params);
            $response['packing_slip']=$packing_slip; // merge packing slip in response array
            $result = ['status' => $response['status'], 'data' => self::render_partial($courier->type.'/success_response', $response)];

            // update tracking number and shipping status in local database
            OrderUtil::updateOrderTrackingAndShippingStatus($order_items_pk,$response['label'],$response['tracking_number'],$courier_id ,'shipped');
            OrderUtil::UpdateOrderStatus( $channel->id, $order->order_number); // update main order table status based on order item status

           // CourierUtil::updateMarketplaceTrackingAndShippingStatus($channel,$order,$items,$courier,$response,'Shipped'); // update tracking number and shipping status in channel

            $response['system_shipping_status']='shipped'; //
            $response['courier_shipping_status']='shipped'; //

            /// embed order item_ids into response , algo to handle fedex multi package issue for generic function
            $response_final=[];
            foreach($items as $item)
            {
                $response['order_item_id']=$item['id'];
                $response_final[]=$response;
            }
            //// add order shipment detail to order shipment table
            CourierUtil::addOrderShipmentDetail($response_final);
            return $this->asJson($result);
        }


    }

    /**
     * shipping cancelation requested
     */

    public function actionShippingCancellationRequested()
    {
        if(isset($_POST['tracking_number']) && isset($_POST['order_id_pk']))
        {
            $order=Orders::findone(['id'=>$_POST['order_id_pk']]);
            $order_items = OrderItems::find()->where(['order_id' => $_POST['order_id_pk'],'tracking_number'=>$_POST['tracking_number']])->asArray()->all();

            if($order_items)
            {
                $order_items_pk_ids=array_column($order_items,'id'); // get all pk ids at once
                $courier=Couriers::findOne(['id'=>$order_items[0]['courier_id']]);
                $order_shpiments=OrderShipment::find()->where(['IN', 'order_item_id',$order_items_pk_ids])->asArray()->all();
                $order_shpiment_pk_ids=array_column($order_shpiments,'id');
                if(strtolower($courier->type)=="usps" && $order_shpiments)
                {
                    $shipping_date=isset($order_shpiments[0]['shipping_date']) ? $order_shpiments[0]['shipping_date']:NULL;

                    if($shipping_date)
                    {
                        if($shipping_date < date('Y-m-d'))
                            return $this->asJson(['status'=>'failure','msg'=>"already shippping date passed"]);
                    }
                    /**get stamp transacton id*/
                    $stamp_trxid=isset($order_shpiments[0]['additional_info']) ? json_decode($order_shpiments[0]['additional_info']):"";
                    if($stamp_trxid)
                        $stamp_trxid=isset($stamp_trxid->StampsTxID) ? $stamp_trxid->StampsTxID:NULL;
                     $response = UspsUtil::cancelShipment($courier,$stamp_trxid);
                     if($response['status']=="success")
                     {
                         CourierUtil::make_shipping_cancellation_history($order_items,$order_shpiments); // put data in history table
                         $sql="UPDATE `order_items` SET `item_status`='pending',`courier_id`=NULL , `tracking_number`=NULL,`shipping_label`=NULL WHERE `id` IN (".implode(',',$order_items_pk_ids).")";
                         yii::$app->db->createCommand($sql)->execute();
                         yii::$app->db->createCommand()->delete('order_shipment',['in', 'id', $order_shpiment_pk_ids])->execute();
                         OrderUtil::UpdateOrderStatus( $order->channel_id, $order->order_number); // update main order table status based on order item status
                        yii::$app->session->setFlash('success','Request for Refund submitted');
                     }
                    return  $this->asJson($response);
                }
            }

        }

        return  $this->asJson(['status'=>'failure','msg'=>"Failed to update"]);
    }

    /**********************************shipment hostiry*****************************************/

    public function actionShipmentHistory()
    {
        $order_id = yii::$app->request->post('order_id'); //table pk
        $selected_type = yii::$app->request->post('selected_type'); // full order selected or single item selected
        $order_item_pk = yii::$app->request->post('order_item_pk');

        $andwhere = $selected_type == "order_item" ? ['id' => $order_item_pk] : [];
        $order = Orders::find()->where(['id' => $order_id])->one();
        $items = OrderItems::find()->where(['order_id' => $order_id])
            ->andWhere(['IS NOT', 'tracking_number', null])
            ->andwhere($andwhere)
            ->asArray()->all();
        if (empty($items))
            return $this->asJson(['status' => 'failure', 'data'=>self::render_partial('history/shipment_history',['error'=>'No shipment history available'])]);

        $customer = OrderUtil::GetCustomerDetailByPk($order_id);
        foreach($items as &$item){
            $detail=Products::findone($item['sku_id']);
            $item['image']=isset($detail->image) ?  $detail->image:"";
            $item['name']=isset($detail->name) ? $detail->name:"";
            $item['courier']= \common\models\Couriers::find()->where(['id'=>$item['courier_id']])->one();
            $item['shipping_history']= \common\models\OrderShipmentHistory::find()->where(['order_item_id'=>$item['id']])->asArray()->all();
            $item['shipping_current_status'] = \common\models\OrderShipment::find()->where(['order_item_id'=>$item['id']])->asArray()->all();
        }

        $modal_content= self::render_partial('history/shipment_history',
            [
                'order'=>$order,
                'order_items'=>$items,
                'customer'=>$customer,
                'order_item_pk'=>$order_item_pk,
                'error'=>''
            ]
        );
        return $this->asJson(['status'=>'success','address'=>$customer,'msg'=>'record found','data'=>$modal_content]);

    }

    ////change shipping status manually / specially for internal courier
    public function actionChangeCourierStatus()
    {
        $order_item_pk = yii::$app->request->post('order_item_pk');
        $status= yii::$app->request->post('courier_status');
        if($order_item_pk)
        {
            $item_order=OrderItems::find()->where(['id'=>$order_item_pk ])->one();
            $order=Orders::findOne(['id'=>$item_order->order_id]);
            $channel=Channels::findOne(['id'=>$order->channel_id]);
           $os_id= \common\models\OrderShipment::findone(['order_item_id'=>$order_item_pk ]);
           $list=[
                'os_pk_ids'=>$os_id->id,
                'order_item_ids'=>$order_item_pk,
                'system_shipping_status'=>$item_order->item_status,
                'courier_shipping_status'=>$os_id->courier_shipping_status,
                'tracking_number'=>$item_order->tracking_number,
                'shipping_label'=>$item_order->shipping_label,
                'courier_id'=>$item_order->courier_id,
           ];
          $response=CourierUtil::update_local_db_shipping_status($list,$status);
          if($response){
              self::change_courier_status_marketplace($channel,$order_item_pk,$status);
              return $this->asJson(['status'=>'success','msg'=>'Updated']);
          }

        }
            return $this->asJson(['status'=>'failure','msg'=>'Failed to update try again']);
    }

    private static function change_courier_status_marketplace($channel,$order_item_pk, $status)
    {
        //echo $status; die();
        if($channel->marketplace=="magento"){
            $items = OrderUtil::GetInternalCourierShippedItems($channel->id,$order_item_pk );
            $items=MagentoUtil::arrangeOrderForShipment($items); // arrange items order wise
            $items = MagentoUtil::append_invoice_record((object)$channel, $items);  // check how many invoices created already // before ship have t create invoice
            $response = MagentoUtil::createInvoice((object)$channel, $items);
            if($status=="completed") { // for internal courier when we shipped item we do not updated status on marketplace at that time , but after complete status we mark that as shipped
                $response = MagentoUtil::createShipment((object)$channel, $items);
            } else if($status=="canceled"){
                $response=MagentoUtil::refunOrderItem((object)$channel, $items);
            }
            MagentoUtil::updateOrderShipmentLocalDb($items,$response,false);  // local db update order_shipm
        }
        return true;

    }
   /* public function actionTest()
    {
       $channel=Channels::findone(['id'=>16]);
       $order=Orders::findOne(['id'=>869]);
       $courier=Couriers::findOne(['id'=>4]);
       $price=['total_charges'=>10,'total_charges_incl_taxes'=>10,'total_charges_excl_taxes'=>10,'weight'=>null,'tracking_number'=>'12345testbilal'];
       PrestashopUtil::updateOrderTracking($channel,$order,$courier,$price);
       // print_r($order);
    }*/
    public function actionTrack()
    {
        $courier=Couriers::findOne(['id'=>4]);
       // UpsUtil::trackShipping($courier,'1Z12345E0305271640'); // delivered  // D
      //  UpsUtil::trackShipping($courier,'1Z12345E1305277940'); // ORIGIN SCAN  // I
        //UpsUtil::trackShipping($courier,'1Z12345E6205277936'); // 2nd delivery attempt
        //UpsUtil::trackShipping($courier,'990728071'); // 2nd delivery attempt
       // UpsUtil::trackShipping($courier,'1Z648616E192760718'); // Order Process by UPS
       // UpsUtil::trackShipping($courier,'1Z12345E0305271640'); // Order Process by UPS
        UpsUtil::trackShipping($courier,'3251026119'); // Order Process by UPS
    }
    public function actionGetLazadaOrderDetail(){
        $channel = Channels::find()->where(['name'=>$_GET['channel_name']])->one();
        $response = LazadaUtil::GetOrderDetail($_GET['order_id'],$channel);
        $this->debug($response);
    }

    /**
     * update marketplace order status and tracking number
     */
    public function actionUpdateMarketplaceTrackingShipping()
    {

    }

    public function actionTestUpsTracking()
    {
        $courier = Couriers::findone(['id' =>3]);
       // $res=UpsUtil::trackShipping($courier,'1Z12345E0205271688');
        //$res=UpsUtil::trackShipping($courier,'1Z12345E6605272234');
        $res=UpsUtil::trackShipping($courier,'990728071'); // in transit
        echo "<pre>";
        print_r($res); die();
    }
    public function actionTest()
    {
       // UspsUtil::get_authenticator();
       // UspsUtil::getShippingRates();
        //$res=UspsUtil::ValidateAddress('yes','yes');
        //print_r($res);
        $courier = Couriers::findone(['id' =>4]);
       // UspsUtil::submitShipping($courier);
       // $res=UspsUtil::trackShipping($courier,'9305520111404269974233');
       // $res=UspsUtil::trackShipping($courier,'9400111969000940000011');
       // $res=UspsUtil::trackShipping($courier,'9400111899563195590223');  // live tracing number
       // $res=UspsUtil::trackShipping($courier,'9449011899562527523924');  // live tracing number
        $res=UspsUtil::trackShipping($courier,'9405511899562770052975	');  // live tracing number
       // $res=UspsUtil::cancelShipment($courier,'c605aec1-322e-48d5-bf81-b0bb820f9c22');
        echo "<pre>";
        print_r($res); die();
    }

    public function actionTest2()
    {
        $res=Product360Util::getProductDimensionsAndWeight('ADIJRW01Wooden9ft/275cm');
        print_r($res); die();
        // Your ID and token
        $authToken = 'AgAAAA**AQAAAA**aAAAAA**7KRpXg**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ANmYSnAJSFqQ+dj6x9nY+seQ**OBsGAA**AAMAAA**QaEO75J9uxEE2ydcgOo7uFKyu2HIgMuWdBF0tIyEszVIip0O7eG4pxqoj+AKy0fhfROVZBXiL0Vzarze26X53V3hIBS1bZFqX7qowwmUDK5k64BTRMqtnOm/Sk+dWxE2JdjQxnXE7wEbM/RK3819ROBz7ITE5bro+7t2t2FUg+j5X56QnZQM6kgIBdbQ7VZqTMa7w5H8Cgw/IqnAyANSFfZlb7nK8dJn1TfnuZiGROUkKrqMckYTMZoiT4foudKdDaKjq01yCt7l90zsobkqHXuyzCLvb5eiJttAOFu/5knI7lTDz7i08CZRMvXtvhMWMkBYWQtpe6xLDP2mRrxM7sLDoGOXlYECvPfdMea4GcNtU7IL4UCghU3FQDVXkU7ecB/m7IlMBqij9/KvFpCbBTd8vbWd7/8q97zuWztRraKB+w4HJiUNlmrxR/NW0rUUflhgeE8DmJheeaViVKe4UegDNzWIWKW5cJmLnI6zHsc1bKBm8/tMu/u71Z+PlYWfn/WMVeYDNQkfLlRbFEuG3I9jmjFOhtrxlcBEKBzfQ6SHW6fZTaMGq/IskQVi2BHq4qlFIpHewDMa78Cm2bOu1Ag72t3v+jgaEfOHcxyIesr3Sz8NeboOkCOPAeT4IeUaLkWjidWXDcKdmWadhDWuvLPz9L8LjiId6xiQHAz5bAItYWOUA20DuemgHxOI5RfZsVLJ5B0YSmeQcjqR7a8ZRldu9jW0aFN/8GmVqk3I0VCF41VtWZBJ/smlhvYpb8A7';

        // The data to send to the API
        $post_data = json_encode(array("legacyOrderId"=>"223594697751-2365473152012"));
        $url = 'https://api.ebay.com/post-order/v2/cancellation/check_eligibility';
        //Setup cURL
        $header = array(
            'Authorization: TOKEN '.$authToken,
            'Content-Type: application/json',
            'X-EBAY-C-MARKETPLACE-ID: EBAY_US',
            'Accept: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
       // curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            echo "ERROR:".curl_error($ch); die('error');
        }
        curl_close($ch);
        print_r($response); die();
        echo json_decode($response,true);
    }
    public function actionGetFedexPackages(){
        return $this->renderPartial('../sales/_render-partial-shipment/fedex/package_type_dropdown',['service_type'=>$_GET['service_type']]);
    }
}