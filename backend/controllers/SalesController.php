<?php

namespace backend\controllers;

use backend\util\CourierUtil;
use backend\util\EbayUtil;
use backend\util\FedExUtil;
use backend\util\GraphsUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\OrderUtil;
use backend\util\PrestashopUtil;
use backend\util\ProductsUtil;
use backend\util\RecordUtil;
use backend\util\SalesUtil;
use backend\util\ShopeeUtil;
use backend\util\WarehouseUtil;
use common\models\Category;
use common\models\Channels;
use common\models\Couriers;
use common\models\CustomersAddress;
use common\models\DealsMaker;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrdersCustomersAddress;
use common\models\PoDetails;
use common\models\Products;
use common\models\ProductStocks;
use common\models\SalesTargets;
use common\models\search\OrderItemsSearch;
use common\models\search\OrdersPaidStatusSearch;
use common\models\Settings;
use common\models\StocksPo;
use common\models\Warehouses;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use  yii\data\Pagination;

class SalesController extends GenericGridController
{
    /*public function behaviors()
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
    }*/


    public function actionIndex()
    {
        return $this->render('index');
    }

    /*
     *  all order listings
     *
     */

    public function actionReporting()
    {
        $_oiObj = new OrderItemsSearch();
        $userid=Yii::$app->user->identity;
        $encryptionKey = Settings::GetDataEncryptionKey();
        $id=$userid['id'];
        $data = $_oiObj->GetOiRecords();
        $stats_count=$_oiObj->getOrderCountStats();
        $channels=Channels::find()->where(['is_active'=>1])->asArray()->all();
        if($userid['role_id'] == 8){
            $warehouses = Warehouses::find()->where(['is_active' => 1,'user_id' => $id])->asArray()->all();
        }else {
            $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        }
        $shippingLabel = HelpUtil::GetShippingLabelsSalesScreen($data['orders']);
        $order_cities=OrderUtil::getUniqueOrderCities();  // unique orders cities
        $coupon_codes=Orders::find()->select('coupon_code')->distinct()->asArray()->all();
        //permissions for shipment and warehouse assignment
        $currentModule = Yii::$app->controller->id;
        $ArrangeShipPermission=\backend\util\HelpUtil::AccessAllowed($currentModule,'arrange-shipment');
        $AssignWarehouse = \backend\util\HelpUtil::AccessAllowed($currentModule,'assign-warehouse');
        return $this->render('orders-list', [
            'orders' => $data['orders'],
            'shippingLabel'=>$shippingLabel,
            'total_records' => $data['total_records'],
            'channels'=>$channels,
            'warehouses'=>$warehouses,
            'order_counts'=>$stats_count,
            'order_cities'=>$order_cities,
            'coupon_codes'=>$coupon_codes,
            'permissions'=>['shipment'=>$ArrangeShipPermission,'warehouse_assignment'=>$AssignWarehouse],
        ]);
        /*ini_set('memory_limit', '-1');
        $_oiObj = new OrderItemsSearch();
        $data = $_oiObj->GetOiRecords();
        $warehouses = $this->getList('id','warehouses','',' WHERE is_active = 1');
        $courier_services = Couriers::find()->asArray()->all();
        //$this->debug($data['Orders']);
        return $this->render('reporting-skus', [
            'data' => $data['Orders'],
            'total_records' => $data['TotalRecords'],
            'warehouses' => $warehouses,
            'courier_services' => $courier_services
        ]);*/
    }

    /***
     * @return \yii\web\Response
     * get packing invoice alip generated after courier shiping
     */
    public function actionGetPackingSlip()
    {
        //return "<a href=''>";
        if(isset($_GET['order_item_id']))
        {
            $slip=\common\models\OrderShipment::findOne(['order_item_id'=>$_GET['order_item_id']]);
            if(isset($slip->packing_slip) && $slip->packing_slip)
                return  $this->redirect('/shipping-labels/'.$slip->packing_slip); // direct to folder and pdf file
             else
                 die('not found');
        }else
            die('not found');


    }
    /**
     *  oreder listing export
     */
    public function actionExportCsv()
    {

        $_GET['record_per_page']=100000; // to fetch all
        $_oiObj = new OrderItemsSearch();
        $data = $_oiObj->GetOiRecords('report');
        return  $_oiObj->export_csv($data );
    }

    public function actionTargets()
    {
        $records="SELECT st.* ,group_concat(std.markup) as markups,group_concat(std.type_value) as applied_to
                    FROM
                      `sales_targets` st 
                    INNER JOIN
                      `sales_target_detail` std
                      ON `st`.`id`=`std`.`target_id`
                    GROUP BY `st`.`id`
                    ORDER BY `st`.`id` DESC";
        //$records=SalesTargets::findBySql($records)->asArray()->all();
        //print_r($records); die();
        $data=[
                'records'=> SalesTargets::findBySql($records)->asArray()->all(),
                'channels'=>Channels::find()->select(['id','name','marketplace'])->where(['is_active'=>1])->asArray()->all(),
                'categories'=>Category::find()->select(['id','name'])->where(['is_active'=>1,'parent_id'=>NULL])->asArray()->all(),
              // 'already_approved_exists'=>SalesTargets::findone(['status'=>'approved','year'=>date('Y')]),
        ];
     //  print_r($data['records']); die();
        return $this->render('targets/sales-targets',$data);
    }

    public function actionTargetDetail()
    {
        if(!isset($_GET['id']))
          return   $this->redirect(['targets']);

        $res=SalesUtil::getSalesAndTarget($_GET['id']);
        //$res=SalesUtil::OverallTotal($_GET['id']);
       // print_r($res); die();
        $data=[
            'total_records'=>$res['total_records'],
            'records'=>$res['records'],
            'channels'=>Channels::find()->where(['is_active'=>'1'])->all(),
            'target'=>SalesTargets::findone(['id'=>$_GET['id']]),
            'already_approved_exists'=>SalesTargets::findone(['status'=>'approved','year'=>date('Y')]),
            'overall_total'=>SalesUtil::OverallTotal($_GET['id'],'yearly'),
            ];
       // echo "<pre>";
       // print_r($data['records']); die();
        return $this->render('targets/sales-target-detail',$data);
    }

    /**
     * create sale target
     */
    public function actionCreateSaleTarget()
    {
       //print_r($_POST);die();
            //////////first check if sales exists for proir year selected
             $sales_exist=Yii::$app->db->createCommand("SELECT `id`  FROM `sku_sales_archive` WHERE `year`='".$_POST['prior_year']."'")->query()->count();
             if($sales_exist <= 0)
                 return $this->asJson(['status'=>'failure','msg'=>'Prior year sales not exists']);

             //insert sales target // sales_targets table
              $target=SalesUtil::insertSalesTarget();

             // insert sales target detail  // sales_target_detail table
              if(isset($target['error']))
                  return $this->asJson($target);
              else
                $target_detail=SalesUtil::insertSalesTargetDetail($target);

            //create and insert target for individual skus
            if(!isset($target_detail['error']))
            {
                $targ=['id'=>$target->id,'type'=>$target->type,'cal_type'=>$target->calculation_type,'cal_subtype'=>$target->calculation_subtype,'prior_year'=>$target->year_compared,/*'markup'=>$target_detail->markup*/];
                $target = (object) $targ;
                /// arrange markup
                if($target->type=="marketplace") // if marketplace wise targets set
                {
                    $marketplaces=[
                        'markup_values'=>$_POST['markup'], // markup values in array
                        'marketplace_names'=>$_POST['markup_for_name'] // all marketplaces name in array
                    ];
                    $markups=SalesUtil::MarketplaceChannelsAndMarkups($marketplaces); // get channels of market places with markups ,
                }
                elseif($target->type=="channel")
                {
                    $markups=array_combine($_POST['markup_for_id'],$_POST['markup']); // index channel_id, value=>markup value
                }
                elseif($target->type=="category")
                {
                    $markups=array_combine($_POST['markup_for_id'],$_POST['markup']); // index cat_id, value=>markup value
                    $all_cats=Category::find()->select(['id','name','parent_id'])->asArray()->all();
                    $cat=SalesUtil::markup_category_tree($markups,$all_cats); // add  markup to all cats and its child subcat
                    $markups=SalesUtil::get_id_markup_cat($cat); // make array ['cat_id','markup']
                }
                elseif($target->type=="overall")
                {
                    $markups=$_POST['markup']; // single direct value of markup for all skus
                }

                $sales=SalesUtil::fetchSalesArchiveAccordingToTarget($target); // fetch sales of prior year according to target previous year
                $target_sku_count=SalesUtil::SaveSalesTargetSkuBased($target,$sales,$markups);  // sku individual sku based target

                yii::$app->session->setFlash('success','Target made for ' . $target_sku_count. 'skus');
                return $this->asJson(['status'=>'success','msg'=>'Created','target_id'=>$target->id]);
            }
            else
            {
                $errors = $target->getFirstErrors();
                return $this->asJson(['status'=>'failure','msg'=>reset($errors)]);
            }



    }

    /***
     * fill sales target detail table
     * accodring to target markup set map sku sales with new target and save as target
     */

    /*private function process_sales_target_detail($target)
    {

       // print_r($response); die();
        return $response;
    }*/

    /*
     * approve sales target
     */

    public function actionTargetApproval()
    {
        if(Yii::$app->user->id!=1) // only by super admin
            return $this->asJson(['status'=>'failure','msg'=>'Not Allowed']);

            if(isset($_POST['target_id']) && !empty($_POST['target_id']))
            {
                $model=SalesTargets::findone($_POST['target_id']);
                $model->status='approved';
                if($model->update())
                    return $this->asJson(['status'=>'success','msg'=>'approved']);

            }
         return $this->asJson(['status'=>'failure','msg'=>'Action Failed']);
    }

    /**
     * @return string
     * Delete target
     */

    public function actionDeleteTarget()
    {
        if(isset($_GET['id']) && !empty($_GET['id']))
        {
            $delete=SalesTargets::deleteAll(['AND',
                                ['NOT',['status'=>'approved']],  
                                ['id'=> $_GET['id'] ]
                            ]);
            if($delete)
                yii::$app->session->setFlash('success','Deleted');
            else
                yii::$app->session->setFlash('failure','Failed to perform action');
        }
         return   $this->redirect(['targets']);

    }
    /****
     * top contributors products in sales
     */
    public function actionTopPerformers()
    {
        $top_performers=GraphsUtil::top_performers(null,'yes');
        return $this->render('top-performers',['top_performers'=>$top_performers]);
    }
    public function actionAssignWarehouse(){

        $response = ['updated'=>0];
        // update order item warehouse id
        $Item = OrderItems::findOne($_POST['itemId']);
        $Item->fulfilled_by_warehouse = $_POST['warehouseId'];
        $Item->update();
        if ( $Item->errors ){
            $response['updated'] = 0;
            return json_encode($response);
        }
        else{
            $response['updated'] = 1;
            return json_encode($response);
        }
    }

    public function actionItemDetail($id)
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $order = Orders::find()->where(['id' => $id])
            ->addSelect(['id','order_number','channel_id','AES_DECRYPT(customer_fname, "'.$encryptionKey.'") as customer_fname',
                'customer_lname','order_total',
                "(CONVERT_TZ(order_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) as order_created_at",
                'order_count','order_status','order_shipping_fee',
                'order_discount','payment_method','coupon_code',
                'created_at','updated_at','created_by','updated_by','is_update','AES_DECRYPT(full_response, "'.$encryptionKey.'") as full_response'])
            ->one();
        //echo "<pre>";
        //print_r($order->customersAddresses); die();
        $Courier = $this->getList('id','couriers','','');
        $searchModel = new OrderItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('item-detail', ['order' => $order, 'dp' => $dataProvider,'couriers'=>$Courier]);
    }

    public function actionUploadBatch()
    {
        ini_set('memory_limit', '-1');
        $csvList = [];
        if (isset($_POST['_csrf-backend'])) {
            $csvFile = $_FILES['csv'];
            $actualName = $csvFile['name'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {

                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    if ($getData[0] != '') {
                        $csvList[$getData[13]][$getData[6]] = [
                            'tdate' => date('Y-m-d', strtotime($getData[0])),
                            'order_id' => $getData[13],
                            'item' => $getData[6],
                            'status' => ($getData[12] == 'Not paid') ? '0' : '1',
                            'file' => str_replace('.csv', '', $actualName)
                        ];
                    }
                }
            }
            if ($csvList) {
                RecordUtil::updateSalesStatus($csvList);
            }
        }
        $this->redirect('/sales/finance-validation?type=fv');
    }

    public function actionFinanceValidation()
    {
        $DealsMakerChannels = "SELECT c.id as `key`,c.name as `value` FROM deals_maker dm inner join channels c on c.id = dm.channel_id
        where dm.channel_id IN (1,10) and c.is_active = 1 group by c.id";
        $ChannelList = DealsMaker::findBySql($DealsMakerChannels)->asArray()->all();
        $item_statuses = "SELECT oi.`item_status` as `key`, oi.`item_status` as `value` FROM order_items oi group by oi.`item_status`";
        $item_statuses_list = OrderItems::findBySql($item_statuses)->asArray()->all();
        $config =
            ['UrlSetting' =>
                [
                    'defualtUrl' => '/sales/generic-info',
                    'sortUrl' => '/sales/generic-info-sort',
                    'filterUrl' => '/sales/generic-info-filter',
                    'jsUrl' => '/sales/finance-validation',
                ],
                'thead' =>
                    [
                        'SKU' => [
                            'data-field' => 'item_sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'item_sku',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => '',

                        ],
                        'Order Date' => [
                            'data-field' => 'io.item_created_at',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'io.item_created_at',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'hidden',
                            'input-type-class' => '',
                        ],

                        'Shop Name' => [
                            'data-field' => 'c.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.id',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'select',
                            'options' => $ChannelList,
                            'input-type-class' => ''
                        ],
                        'Quantity' => [
                            'data-field' => 'io.quantity',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'io.quantity',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Price (RM)' => [
                            'data-field' => 'price',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'price',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Order ID' => [
                            'data-field' => 'o.order_number',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'o.order_number',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Shop Sku' => [
                            'data-field' => 'io.shop_sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'io.shop_sku',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Item Status' => [
                            'data-field' => 'io.item_status',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'io.item_status',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'select',
                            'options' => $item_statuses_list,
                            'input-type-class' => ''
                        ],
                        'Shipping Type' => [
                            'data-field' => 'shipping_type',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'shipping_type',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Days #' => [
                            'data-field' => 'io.item_created_at',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'io.item_created_at',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                    ]
            ];
        $session = \Yii::$app->session;
        $officeSku = [];
        if ($session->has('sku-imported')) {
            $officeSku = $session->get('sku-imported');
        }

        $session->remove('sku-imported');
        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku, 'config' => $config, 'Modal' => 1]);
        $roleId = Yii::$app->user->identity->role_id;
        return $this->render('index-grid-view', ['gridview' => $html, 'roleId' => $roleId]);
    }
    public function actionFinance()
    {
    }
    public function actionConfigParams()
    {
        $data = \Yii::$app->request->post();
        if (isset($data['filters'][8]) && ($data['filters'][8]['val']) != '') {
            $having_clause = ' having DATEDIFF(NOW(), io.item_created_at) ' . $data['filters'][8]['val'] . ' ';
        } else {
            $having_clause = ' having DATEDIFF(NOW(), io.item_created_at) >=0 ';
        }
        if (isset($data['filters'][0]) && ($data['filters'][0]) != '') {
        }
        if (isset($data['filters'][9]) && ($data['filters'][9]) != '') {
            $having_clause .= ' AND  io.full_response like \'%' . $data['filters'][9]['val'] . '%\'';
        }
        $config = [
            'query' => [
                'FirstQuery' => 'SELECT
 item_sku,
 io.item_created_at as created_at_finance_validation,
 c.name,
 io.quantity, CAST(io.price AS SIGNED) AS price,
 o.order_number,
 io.shop_sku,
 io.item_status,
 io.item_sku,
 null as shipping_type,
 DATEDIFF(NOW(), io.item_created_at) AS days,
 io.full_response
FROM
 order_items io
INNER JOIN orders o ON o.id = io.`order_id`
INNER JOIN channels c ON c.id = o.`channel_id`
where io.is_paid = 0  and o.channel_id IN (1,10)  
',
                'LastQuery' => $having_clause,
                'GroupBy' => ''
            ],
            'OrderBy_Default' => 'ORDER BY created_at_finance_validation DESC',
            'SortOrderByColumnAlias' => 'io',
        ];
        return $config;
    }

    public function actionUpdateQuarterData()
    {
        $quarterSales = GraphsUtil::getQuarterSales();
        return json_encode($quarterSales);
    }

    public function actionGetWeeks()
    {
        return GraphsUtil::getWeeks();
    }

    public function actionDashboard()
    {
        /**
         * Filters for the morris bar chart
         * */
       $quarterSales = GraphsUtil::getQuarterSales();
        $morris_bar_categories = GraphsUtil::getMainCategories();
        $morris_bar_marketplaces = GraphsUtil::getMarketPlaces();
        $morris_bar_shops = GraphsUtil::getShops();
        $postDate = isset($_POST['filter']['date']) ? $_POST['filter']['date'] : "";

        //  $st = GraphsUtil::getSalesTarget('year'); // pcp contains main_category, i didn't changed it.
        //  $cs = GraphsUtil::getSalesTarget('category'); // pcp contains main_category, i didn't changed it.
        //  $revenueList = GraphsUtil::getSalesRevenue($st['target']);
        $categories=Category::find()->where(['parent_id'=>NULL])->orWhere(['parent_id'=>0])->asArray()->all();
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
      //  $this->debug($brands);
        $sales_by_shop_per_month =GraphsUtil::SalesGraphByShop(); // sales graph data by shop/marketplace every month/quarter of all years
       // self::debug($sales_by_shop_per_month);
        $monthlySales = GraphsUtil::getMonthSales($postDate);
        $mpSales = GraphsUtil::getMarketplaceSales(null, $postDate);
        $yearSales = GraphsUtil::getYearlySales();
        //self::debug($yearSales);
        $avgDaySales = GraphsUtil::getAverageDaySales();
        $avgSixMonthDaySales = GraphsUtil::getAverageDaySales(6);
        // for target
        $filters = SalesUtil::refine_sales_dashboard_filter(isset($_POST['filter']) ? $_POST['filter'] : NULL); // post variables will be renamed and sent to target function
        $targets = SalesUtil::getSalesTarget($filters);
        $product_styles=Products::find()->select('style')->distinct()->asArray()->all();
       // print_r($targets); die();
       //self::debug($sales_by_shop_per_month);

        if (isset($targets['error']))
            $targets=NULL;
        else
             $targets = SalesUtil::map_sales_and_targets($filters['type'], $targets, json_decode($yearSales['json']));

        // echo json_encode($targets); die();
        return $this->render('dashboard', [
            'monthlySales' => $monthlySales,
            'categories'=>$categories,
            'brands'=>$brands,
            'mpSales' => $mpSales,
            'morris_bar_categories' => $morris_bar_categories,
            'morris_bar_marketplaces' => $morris_bar_marketplaces,
            'morris_bar_shops' => $morris_bar_shops,
            'sales_by_shop_per_month' => $sales_by_shop_per_month,
            'quarter_sales' => $quarterSales['bars_data'],
            'yKeys' => $quarterSales['yKeys'],
            'avgDaySales' => $avgDaySales,
            'yearSales' => $yearSales,
            'avgSixMonthDaySales' => $avgSixMonthDaySales,
            'targets' => $targets,
            'product_styles' => $product_styles
        ]);
    }
    // sales report by marketplace
    public function actionReportByMarketplace()
    {

        $mp = $_GET['mp'];

        $date = isset($_GET['date']) ? $_GET['date'] : '';
        $date = (isset($date) && $date != "") ? base64_decode($date) : "";
        $cat_data_range=date('Y-01-01') ." to ". date('Y-m-d'); // will get category sale data
        //$totalRevenue = HelpUtil::getTotalRevenueForDashboard([$_GET['mp']],[],$date); // blue boxes data, like total sale, total customers.
      //  $mpSales = GraphsUtil::getMarketplaceSales($mp, $date);
        $channel_ids=HelpUtil::get_marketplace_channel_ids($mp); // get channel ids of all channels under specific marketplace
     //   $skuSales = GraphsUtil::getSkuSales($mp, $date);
     //   $starget = GraphsUtil::getSalesTargetReveiw($mp, $date);
        $categories=Category::find()->where(['parent_id'=>NULL])->orWhere(['parent_id'=>0])->asArray()->all();
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
        $sales_contribution=GraphsUtil::sales_contributions('shop'); // will group by shop/channels in marketplace
        $salesForcastData = GraphsUtil::getSalesForcast([$_GET['mp']]);
        $top_performers=GraphsUtil::top_performers('shop'); // will group by shop/channels in marketplace
        $distributorsale=HelpUtil::distributor_sale($channel_ids);
        $first_order=GraphsUtil::first_order_date(null,$mp);
        $parent_and_variations=\backend\util\ProductsUtil::parents_and_variation_counts_marketplace($mp); //count parent and variation
        $sales_by_shop_per_month = GraphsUtil::SalesGraphByShop(); // sales graph data by shop/marketplace every month
       // echo "<pre>";
     //  print_r($sales_by_shop_per_month); die();
        return $this->render('report-by-mp', [
            'mp' => ucwords($mp),
            'categories'=>$categories,
            'brands'=>$brands,
            'sales_contribution' => $sales_contribution,
            'salesForcastData' => $salesForcastData,
            'top_performers'=>$top_performers,
            'first_order'=>$first_order, // when first order placed // days, date ,weeks , months
            'parent_and_variations'=>$parent_and_variations,
            'sales_by_shop_per_month'=>$sales_by_shop_per_month,
            'distributorsale'=>$distributorsale,
            //'mpSales' => $mpSales,
           // 'skuSales' => $skuSales,
          //  'starget' => $starget,
            'date' => $date,
        ]);
    }

    public function actionSalesByCategory()
    {

        if(isset($_POST['filter_date']))
            $filter_date=$_POST['filter_date'];
        else
            $filter_date=date('Y-01-01') ." to ". date('Y-m-d');  // this year sales

        if(isset($_POST['mp']))
            $data=$this->GetSalesByCategory([$_POST['mp']],[],$filter_date);
        if(isset($_POST['shop']))
            $data=$this->GetSalesByCategory([],[$_POST['shop']],$filter_date);

        return $this->asJson($data);
    }

    public function actionReportByShop($shop)
    {

        $date = isset($_GET['date']) ? $_GET['date'] : '';
        $date = (isset($date) && $date != "") ? base64_decode($date) : "";
        //print_r($date); die();
        $cat_data_range=date('Y-01-01') ." to ". date('Y-m-d'); // will get category sale data
       // $totalRevenue = HelpUtil::getTotalRevenueForDashboard([],[$_GET['shop']], $date);
        $channel_id=Channels::find()->select('id')->where(['name'=>$_GET['shop']])->scalar();  //
        $categories=Category::find()->where(['parent_id'=>NULL])->orWhere(['parent_id'=>0])->asArray()->all();
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
        $product_styles=Products::find()->select('style')->distinct()->asArray()->all();
        $sales_contribution=GraphsUtil::sales_contributions('shop'); // will group by shop/channels in marketplace
        $mpSales = GraphsUtil::getShopSales($_GET['shop'],$date);
        $distributorsale=HelpUtil::distributor_sale($channel_id);
       // $skuSales = GraphsUtil::getSkuSales($_GET['shop'], $date,'shop');
       // $starget = GraphsUtil::getSalesTargetReveiw($_GET['shop'], $date,'shop');
        $salesForcastData = GraphsUtil::getSalesForcast([],[$_GET['shop']]);
        $sales_by_shop_per_month = GraphsUtil::SalesGraphByShop(); // sales graph data by shop/marketplace every month
        $first_order=GraphsUtil::first_order_date(null,null,$_GET['shop']);
        $parent_and_variations=\backend\util\ProductsUtil::parents_and_variation_counts_marketplace(null,$_GET['shop']); //count parent and variation
        $top_performers=GraphsUtil::top_performers('shop'); // will group by shop/channels in marketplace
        // print_r($sales_by_shop_per_month);exit;
        // echo "<pre>";
       //print_r($mpSales); die();
      //  self::debug($mpSales);
        return $this->render('report-by-shop', [
            'sp' => ucwords($_GET['shop']),
            'categories'=>$categories,
            'brands'=>$brands,
            'product_styles'=>$product_styles,
            'sales_contribution' => $sales_contribution,
            'first_order' =>$first_order,
            'salesForcastData' => $salesForcastData,
            'parent_and_variations'=>$parent_and_variations,
            'top_performers'=>$top_performers,
            'distributorsale'=>$distributorsale,
            'mpSales' => $mpSales,
            'sales_by_shop_per_month' => $sales_by_shop_per_month,
           // 'skuSales' => $skuSales,
          //  'starget' => $starget,
            'date' => $date,
        ]);
    }

    public function actionAverageSalesBySku(){
        //$this->debug($_GET);
        $mp = [];
        $shop=[];
        if ( isset($_GET['mp']) ){
            $mp = [$_GET['mp']];
        }
        if ( isset($_GET['shop']) ){
            $shop=[$_GET['shop']];
        }
        $avgSkuSales = GraphsUtil::getAvgMonthlySkus($mp,$shop);
        $Products = ProductsUtil::GetChildProductsSkus();
        return $this->render('average-sku-sales', ['avgSkuSales'=>$avgSkuSales['records'],'total_records'=>$avgSkuSales['total_records'],'products'=>$Products]);
    }
    public function actionDownloadAverageSalesBySku(){
        //$this->debug($_GET);
        $mp = [];
        $shop=[];
        if ( isset($_GET['mp']) ){
            $mp = [$_GET['mp']];
        }
        if ( isset($_GET['shop']) ){
            $shop=[$_GET['shop']];
        }
        $_GET['record_per_page'] = 15000;
        $avgSkuSales = GraphsUtil::getAvgMonthlySkus($mp,$shop);

        if (!file_exists('average-sku-sales-report')) {
            mkdir('average-sku-sales-report', 0777, true);
        }

        $fileName = "avg-sku-sale-report.csv";
        $file = fopen('average-sku-sales-report/'.$fileName, 'w');

        $head=[];
        foreach ($avgSkuSales['records'][0] as $headName=>$val){
            $head[] = str_replace('_',' ',ucwords($headName));
        }

        $content=[];
        foreach ($avgSkuSales['records'] as $key=>$dataDetail){
            $contentDetail=[];

            foreach ( $dataDetail as $value ){
                $contentDetail[] = $value;
            }
            $content[]=$contentDetail;
        }
        fputcsv($file, $head);

        $data = $content;

        foreach ($data as $row)
        {
            fputcsv($file, $row);
        }
        $this->redirect('/average-sku-sales-report/'.$fileName);
        fclose($file);
    }
    public function GetSalesByCategory($mp=[],$shops=[],$date){
        // get parent categories
        $parent_categories = Category::findBySql("SELECT * FROM category c WHERE c.parent_id IS NULL")->asArray()->all();
        $sales=[];
        foreach ( $parent_categories as $parent_category_detail ){
            $get_all_child_categories = $this->GetAllChildCategories($parent_category_detail['id']);
            $get_all_child_categories[]=$parent_category_detail['id'];
            $sales[$parent_category_detail['name']] = (SalesUtil::GetCategorySales($mp,$get_all_child_categories,$shops,$date));
        }
        $final_data=[];
        $mapping=[];
        $colors=[];
        $i=1;
        foreach ( $sales as $key=>$value ){
            $mapping['c'.$i]=$key;
            $i++;
        }
        foreach ( $mapping as $cat_short=>$category_name ){
            $colors[$cat_short] = '#'.HelpUtil::get_random_color();
        }

        foreach ( $sales[$mapping['c1']] as $key=>$value ){

            $detail = [];
            $detail['period'] = $value['period'];
            for( $z=1; $z<=count($mapping); $z++ ){
                $detail['c'.$z]=$sales[$mapping['c'.$z]][$key]['sale'];
            }
            $final_data[] = $detail;
        }

        $data=[];
        $data['dataset']=($final_data);
        $data['categories']=($mapping);
        $data['colors']=($colors);

        return $data;
    }
    //sales report by month/quarterly
    public function actionReportBy()
    {
        $mpSales = GraphsUtil::getMonthlySales();
        $avgSkuSales = GraphsUtil::getAvgMonthlySkusByType();
        $skuSales = GraphsUtil::getSkuSalesByType();
        $type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
      //  print_r($mpSales); die();
        return $this->render('report-by', [
            'mpSales' => $mpSales,
            'avgSkuSales' => $avgSkuSales,
            'skuSales' => $skuSales,
            'type'=>$type,
        ]);
    }



    public function actionOfflineSalesImport(){
        // 0 => some type of order id
        // 1 => Order Date
        // 2 => Customer name
        // 3 => SKU
        // 4 => Main sku
        // 5 => Product Name
        // 6 => Quantity
        // 7 => Unit Price
        // 8 => Shipping
        // 9 => Amount
        // 10 => GST
        // 11 => Amount Incl. GST
        // 12 => Invoice total
        $row = 1;
        $import_info=[
            'orders'=>0,
            'order_items'=>0
        ];
        $formatted_data=[];
        if (($handle = fopen($_FILES['offline_sales']['tmp_name'], "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if(!array_filter($data))
                    continue;
                if ( (isset($data[0]) && $data[0]!='') && !isset($formatted_data[$data[0]]) ){
                    $Order_Total = 0;
                    $ord_id=$data[0];
                    // set the order parameters
                    $formatted_data[$data[0]]['order'][0]=$data[0];
                    $formatted_data[$data[0]]['order'][1]=$data[1];
                    $formatted_data[$data[0]]['order'][2]=$data[2];

                    // set the order item
                    $formatted_data[$ord_id]['order_item'][0][0]=$data[0];
                    $formatted_data[$ord_id]['order_item'][0][1]=$data[1];
                    $formatted_data[$ord_id]['order_item'][0][2]=$data[2];
                    $formatted_data[$ord_id]['order_item'][0][3]=$data[3];
                    $formatted_data[$ord_id]['order_item'][0][4]=$data[4];
                    $formatted_data[$ord_id]['order_item'][0][5]=$data[5];
                    $formatted_data[$ord_id]['order_item'][0][6]=$data[6];
                    $Order_Total += $data[5] * $data[6];
                }else{
                    $Order_Total += $data[5] * $data[6];
                    $ord_id=$data[0];
                    $formatted_data[$ord_id]['order_item'][]=$data;
                }
                $formatted_data[$ord_id]['order_total']=$Order_Total;
            }
            fclose($handle);
        }
        unset($formatted_data['Invoice']);
        unset($formatted_data['invoice']);
        //$this->debug($formatted_data);
        foreach ( $formatted_data as $key=>$value ){

            $MakeOrderId= $value['order'][0].'-'.$value['order'][1];
            $MakeOrderId = strtr($MakeOrderId, array('/' => '-', '&' => '-'));
            $CheckOrderAlreadyExist=Orders::find()->where(['order_id'=>$MakeOrderId])->asArray()->all();
            if (empty($CheckOrderAlreadyExist)){
                // check customer already exist
                $check_customer_already_exist = CustomersAddress::find()->where(['shipping_fname'=>$value['order'][2]])->asArray()->all();
                if ( !empty($check_customer_already_exist) ){
                    $id=$check_customer_already_exist[0]['id'];
                }

                if (empty($check_customer_already_exist)){
                    $create_customer = new CustomersAddress();
                    $create_customer->shipping_fname=$value['order'][2];
                    $create_customer->shipping_number='Offline sale';
                    $create_customer->save();
                    echo '445';
                    $this->db_debug($create_customer->errors);
                    $id=$create_customer->getPrimaryKey();
                }
                $order_created_at=date('Y-m-d', strtotime($value['order'][1]));
                $order_item_created_at=strtotime($value['order'][1]);

                $create_order=new Orders();
                $create_order->order_id=$MakeOrderId;
                $create_order->order_number=$MakeOrderId;
                $create_order->channel_id=$_POST['channel_id'];
                $create_order->customer_fname=$value['order'][2];
                $create_order->order_total=str_replace(',','',number_format( (int) str_replace(',','',$value['order_total']),2  )) ;
                $create_order->order_created_at=date('Y-m-d', strtotime($value['order'][1]));
                $create_order->order_count=count($value['order_item']);
                $create_order->order_status='shipped';
                $create_order->created_at=(int) strtotime($value['order'][1]);
                $create_order->updated_at=(int) strtotime($value['order'][1]);
                $create_order->is_update=1;
                $create_order->save();
                echo '472';
                $this->db_debug($create_order->errors);
                //$import_info
                if (empty($create_order->errors))
                    $import_info['orders']+=1;
                $order_id=$create_order->getPrimaryKey();
                //echo $order_id;die;
                // set the order id and customer id in the bridge table
                $insert_ord_cus= new OrdersCustomersAddress();
                $insert_ord_cus->orders_id=$order_id;
                $insert_ord_cus->customer_address_id=$id;
                $insert_ord_cus->save();
                echo '484';
                $this->db_debug($insert_ord_cus->errors);

                $a=1;
                foreach ( $value['order_item'] as $key=>$value ){
                    $create_order_items=new OrderItems();
                    $create_order_items->order_id=$order_id;
                    $get_sku_id = Products::find()->where(['sku'=>$value[3],'is_active'=>1])->asArray()->all();
                    if (empty($get_sku_id))
                        $sku_id='';
                    else
                        $sku_id=$get_sku_id[0]['id'];
                    $create_order_items->sku_id=$sku_id;
                    $create_order_items->order_item_id=$MakeOrderId.'-item-'.$a;
                    $create_order_items->price=$value[6];
                    $create_order_items->paid_price=$value[5]*$value[6];
                    $create_order_items->item_status='Shipped';
                    $create_order_items->item_created_at=$order_created_at;
                    $create_order_items->item_updated_at=$order_created_at;
                    $create_order_items->quantity=$value[5];
                    $create_order_items->item_sku=$value[3];
                    $create_order_items->created_at=(int) $order_item_created_at;
                    $create_order_items->updated_at=(int) $order_item_created_at;
                    $create_order_items->is_paid=1;
                    $create_order_items->save();
                    /*
                     * condition will run for lazada outright to put the actual no of units recieve in the PO
                     * */
                    if (isset($_POST['po_number']) && $_POST['po_number']!=''){
                        $getPoId = StocksPo::find()->where(['er_no'=>$_POST['po_number']])->asArray()->all();
                        if (!empty($getPoId))
                        {
                            $updatePoItem = PoDetails::findOne(['po_id'=>$getPoId[0]['id'],'sku'=>$value[3]]);
                            $updatePoItem->er_qty=$value[5];
                            $updatePoItem->update();
                        }
                    }
                    echo '509';
                    //$this->db_debug($create_order_items->errors);
                    //$import_info['orders']+=1;
                    if (empty($create_order_items->errors)){
                        $import_info['order_items']+=1;
                    }
                    $a++;
                }
            }

        }
        // if lazadad outright then mark the PO as shipped
        if (isset($_POST['po_number']) && $_POST['po_number']!=''){
            $updatePO = StocksPo::findOne(['er_no'=>$_POST['po_number']]);
            $updatePO->po_status = 'Shipped';
            $updatePO->update();
        }
        //$import_info
        Yii::$app->session->setFlash('import_info', $import_info['orders'].' Orders & '.$import_info['order_items'].' Order Items has successfully added.');
        $this->redirect('/sales/reporting?view=skus&page=1');

    }
    private function db_debug($data){
        if (!empty($data))
        {
            echo '<pre>';
            print_r($data);
            die;
        }
    }
    public function actionArrangeShipment(){
        $order_id = $_GET['order_number'];
        /**
         * Get the channel id by order id
         * */
        //$channel_id = $this->exchange_values('order_number','channel_id',$order_id,'orders');
        //$channel_id=13;
        //$Marketplace = Channels::find()->where(['id'=>$channel_id,'is_active'=>'1'])->asArray()->all();
        /**
         * Get the order details
         * */

        if ( $_GET['marketplace'] == 'shopee' ){

        }
        else if ( $_GET['marketplace'] == 'lazada' )
        {
            $OrderItems = LazadaUtil::GetOrderItems($_GET['channel_id'],$order_id);
            $OrderDetails = LazadaUtil::GetOrder($_GET['channel_id'],$order_id);
            $modal_header = $this->renderPartial('_render-partial-shipment/lazada/header',['order_number'=>$order_id]);
            $modal_content = $this->renderPartial('_render-partial-shipment/lazada/content',['OrderItems'=>$OrderItems,'OrderDetails'=>$OrderDetails
            ,'order_number'=>$order_id,'ShippingProviders'=>LazadaUtil::GetShipmentProviders($_GET['channel_id']),'channel_id'=>$_GET['channel_id']]);
            return json_encode(['header'=>$modal_header,'content'=>$modal_content]);
        }
        else if ($_GET['courier_type'] == 'fedex') {

        }
    }
    public function actionShopeeOrderFulfilmentGetStates(){
        $channel = Channels::find()->where(['id'=>$_GET['channel_id']])->one();
        $order = Orders::find()->where(['id'=>$_GET['order_id']])->one();
        $response = ShopeeUtil::GetBranch($channel,$order->order_number);
        $response = json_decode($response,1);
        $states = [];
        foreach ($response['branch'] as $value){
            $states[$value['state']] = $value['state'];
        }
        //$this->debug($states);
        $modal_content = $this->renderPartial('_render-partial-shipment/shopee/states',['states'=>$states]);
        return json_encode(['status'=>'success','content'=>$modal_content]);

    }
    public function actionShopeeOrderFulfilmentGetBranches(){
        $channel = Channels::find()->where(['id'=>$_GET['channel_id']])->one();
        $order = Orders::find()->where(['id'=>$_GET['order_id']])->one();
        $response = ShopeeUtil::GetBranch($channel,$order->order_number);
        $response = json_decode($response,1);
        $branches = [];
        foreach ($response['branch'] as $value){
            if ($_GET['state']==$value['state']){
                $branches[$value['branch_id']] = $value['address'];
            }
        }
        $modal_content = $this->renderPartial('_render-partial-shipment/shopee/branches',['branches'=>$branches]);
        return json_encode(['status'=>'success','content'=>$modal_content]);

    }
    public function actionShopeeOrderFullfillmentGetAddress(){
        $channel = Channels::find()->where(['id'=>$_GET['channel_id']])->one();
        $order = Orders::find()->where(['id'=>$_GET['order_id']])->one();
        $response = ShopeeUtil::GetAddress($channel);
        $Order_Details = json_decode(ShopeeUtil::OrderDetail($channel,[$order->order_number]));

        $modal_header = $this->renderPartial('/sales/_render-partial-shipment/shopee/header',['order_number'=>$order->order_number]);
        $modal_content = $this->renderPartial('/sales/_render-partial-shipment/shopee/content',['address'=>json_decode($response),'order_number'=>$order->id,
            'order_details'=>$Order_Details->orders[0]]);
        return json_encode(['status'=>'success','header'=>$modal_header,'data'=>$modal_content]);
    }
    public function actionGetShippingRatesFedEx(){
        //$this->debug($_GET);
        $ItemDetail = OrderUtil::GetOrderItemDetail($_GET['orderItemIds']);
        $CustomerInfo = OrderUtil::GetCustomerDetail($_GET['orderId']);
        $CustomerInfo[0]['customer_address'] = $_GET['address'];
        $CustomerInfo[0]['customer_state'] = $_GET['state'];
        $CustomerInfo[0]['customer_city'] = $_GET['city'];
        $CustomerInfo[0]['customer_postcode'] = $_GET['zipcode'];
        $CustomerInfo[0]['customer_country'] = $_GET['country'];

        $WarehouseInfo = WarehouseUtil::GetWarehouseRecipentInfo($ItemDetail[0]['fulfilled_by_warehouse']);
        if (isset($_GET['serviceTypeOption']))
            $one_rate=$_GET['serviceTypeOption'];
        else
            $one_rate='';
        $_GET['package_weight'] = FedExUtil::GetPackageWeight($_GET['package_weight_lbs'],$_GET['package_weight_once']);
        $ShippingRatesFedex = FedExUtil::RateRequest($_GET['courierId'],$CustomerInfo, $WarehouseInfo, $ItemDetail,$_GET['package_weight'],$one_rate,$_GET['serviceType'],$_GET['packageOption'],$_GET['ship_date']);
        //self::debug($ShippingRatesFedex);
        if ($ShippingRatesFedex->HighestSeverity=='ERROR' && isset($ShippingRatesFedex->Notifications->Code) && $ShippingRatesFedex->Notifications->Code=='1000'){
            $Failed = $this->renderPartial('../sales/_render-partial-shipment/fedex/failed',[
                'error_response'=>$ShippingRatesFedex->Notifications->Message
            ]);
            return json_encode( [ 'status'=>'failed','content' => $Failed ] );
        }elseif ($ShippingRatesFedex->HighestSeverity=='ERROR'){
            if (gettype($ShippingRatesFedex->Notifications)=='array'){
                $messages=[];
                foreach ( $ShippingRatesFedex->Notifications as $ErrorDetail ){
                    $messages[] = $ErrorDetail->Message;
                }
                $Warning = $this->renderPartial('../sales/_render-partial-shipment/fedex/warning',[
                    'error_response'=>$messages
                ]);
            }else{
                $Warning = $this->renderPartial('../sales/_render-partial-shipment/fedex/warning',[
                    'error_response'=>[$ShippingRatesFedex->Notifications->Message]
                ]);
            }

            return json_encode( [ 'status'=>'warning','content' => $Warning ] );
        }
        //echo json_encode($ShippingRatesFedex);die;
        $html = Yii::$app->controller->renderPartial('_render-partial-shipment/fedex/ShippingRates',['FedExShippingRates'=>$ShippingRatesFedex,
            'OrderItemIds'=>$_GET['orderItemIds'],'CourierId'=>$_GET['courierId']]);
        return json_encode([
            'status' =>'success',
            'content' => $html
        ]);
    }
    public function actionShipNowFedex(){
        $WarehouseInfo = WarehouseUtil::GetWarehouseRecipentInfo($_POST['warehouseId']);
        $Items = OrderUtil::GetOrderItemDetail($_POST['orderItemIds']);
        $CustomerInfo = OrderUtil::GetCustomerDetail($_POST['orderId']);
        $CustomerInfo[0]['customer_address'] = $_POST['address'];
        $CustomerInfo[0]['customer_state'] = $_POST['state'];
        $CustomerInfo[0]['customer_city'] = $_POST['city'];
        $CustomerInfo[0]['customer_postcode'] = $_POST['zipcode'];
        $CustomerInfo[0]['customer_country'] = $_POST['country'];
        $Channel = Channels::find()->where(['id'=>$_POST['channelId']])->one();

        if (isset($_POST['serviceTypeOption']))
            $serviceTypeOption=$_POST['serviceTypeOption'];
        else
            $serviceTypeOption='';
        $_POST['package_weight'] = FedExUtil::GetPackageWeight($_POST['package_weight_lbs'],$_POST['package_weight_once']);
        $CreateShipment = FedExUtil::createOpenShipment($CustomerInfo,$WarehouseInfo,$Items,$_POST['courierId'],$_POST['package_weight'],$_POST['service_type'],$_POST['package_option'],$serviceTypeOption,$_POST['ship_date']);

        $response = [];

        if ( gettype($CreateShipment)=='string' && $CreateShipment=='Fault' ){
            $response['status'] = 'Failed';
            $Error_Message = 'There is some Fault coming when processing the request.';
            $response['message'] = $Error_Message;
        }
        else if (gettype($CreateShipment)=='object' && $CreateShipment->HighestSeverity == 'SUCCESS' || $CreateShipment->HighestSeverity=='WARNING' ){
            // Updating order items, courier id , shipping labels, tracking number
            OrderUtil::UpdateOrderItemShippingLabels($CreateShipment->CompletedShipmentDetail->CompletedPackageDetails,$Items,$_POST['courierId'],$_POST['package_weight']);
            OrderUtil::UpdateOrderStatus($_POST['channelId'],$_POST['orderId']);    // Update main Order status

            $packages = $_POST['package_weight'];
            $CreateShipment = CourierUtil::unsetFedexLabels($packages, $CreateShipment);

            $orderShipmentDetail = [];
            foreach ( $Items as $key=>$ItemDetail ){
                $orderShipmentDetail[$key]['order_item_id']=$ItemDetail['id'];
                $orderShipmentDetail[$key]['amount_inc_taxes'] = (isset($CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount)) ? $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount : '';
                $orderShipmentDetail[$key]['amount_exc_taxes'] = (isset($CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalBaseCharge->Amount)) ? $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalBaseCharge->Amount : '';
                $orderShipmentDetail[$key]['extra_charges'] = (isset($CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalSurcharges->Amount)) ? $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalSurcharges->Amount : '';
                $orderShipmentDetail[$key]['courier_shipping_status'] = 'shipped';
                $orderShipmentDetail[$key]['system_shipping_status'] = 'shipped';
                $orderShipmentDetail[$key]['shipping_date'] = $_POST['ship_date'];
                $orderShipmentDetail[$key]['estimated_delivery_date'] = (isset($CreateShipment->CompletedShipmentDetail->OperationalDetail->DeliveryDate)) ? $CreateShipment->CompletedShipmentDetail->OperationalDetail->DeliveryDate : '';
                $orderShipmentDetail[$key]['dimensions'] = ['height'=>8,'width'=>8,'length'=>8,'weight'=>array_sum($_POST['package_weight'])];
                $orderShipmentDetail[$key]['full_response'] = $CreateShipment;
            }
            CourierUtil::addOrderShipmentDetail($orderShipmentDetail);

            $response['status'] = 'Success';
            $response['TrackingNumber'] = $CreateShipment->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
        }
        else{

            $response['status'] = 'Failed';
            $Error_Message = '';
            if ( isset($CreateShipment->Notifications) && gettype($CreateShipment->Notifications) == 'array' ){
                foreach ( $CreateShipment->Notifications as $val ){
                    $Error_Message .= '<br />'.$val->Message.' <br />';
                }
            }else{
                if (isset($CreateShipment->Notifications->Message)){
                    $Error_Message = $CreateShipment->Notifications->Message;
                }else{
                    $Error_Message = 'There is some Fault coming when process the request.';
                }
            }
            $response['message'] = $Error_Message;
        }
        $html = [];
        $html['status'] = $response['status'];
        //$this->debug($CustomerInfo);
        if ( $response['status']=='Success' ){
            $shipper = [
                'name' =>$WarehouseInfo[0]['display_name'],
                'shipper_number' => '-',
                'phone' => $WarehouseInfo[0]['phone'],
                'address' => $WarehouseInfo[0]['address'],
                'full_address' => $WarehouseInfo[0]['full_address'],
                'state' => HelpUtil::getCountryStateShortCode($WarehouseInfo[0]['state']),
                'city' => $WarehouseInfo[0]['city'],
                'zip' => $WarehouseInfo[0]['zipcode'],
                'country' => HelpUtil::getCountryStateShortCode($WarehouseInfo[0]['country']),
            ];
            $customer = [
                'name' => $CustomerInfo[0]['customer_fname'].' '.$CustomerInfo[0]['customer_lname'],
                'address' => $CustomerInfo[0]['customer_address'],
                'phone' => $CustomerInfo[0]['customer_number'],
                'email' => $CustomerInfo[0]['shipping_email'],
                'state' => HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_state']),
                'city' => $CustomerInfo[0]['customer_city'],
                'zip' => $CustomerInfo[0]['customer_postcode'],
                'country' => HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_country']),
            ];
            $package = [
                'length' => 8,
                'width' => 8,
                'height' => 8,
                'weight' => array_sum($_POST['package_weight']),
                'weight_oz' => array_sum($_POST['package_weight_once']),
            ];
            $package_type = yii::$app->request->post('package_option');
            $shipping_inc_tax_amount = (isset($CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount)) ? $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount : NULL;
            $service = [
                'code' => yii::$app->request->post('service_type'),
                'name' => yii::$app->request->post('service_type'),
                'amount'=> $shipping_inc_tax_amount,
            ];
            $order=Orders::findone(['order_number'=>$_POST['orderId']]);
            $items = OrderItems::find()->where(['IN','id' , explode(',',$_POST['orderItemIds'])])->asArray()->all();

            $params = ['shipper' => $shipper,
                'customer' => $customer,
                'package' => $package,
                'package_type' => $package_type,
                'service' => $service,
                'order_number'=>$_POST['orderId'],
                'order'=>$order, // for invoice generation
                'order_items'=>$items  //for invoice generation
            ];
            $packing_slip=CourierUtil::generate_order_invoice($params);
            OrderUtil::UpdatePackingSlip($_POST['orderItemIds'],$packing_slip);
            $CourierName = HelpUtil::exchange_values('id','name',$_POST['courierId'],'couriers');

            $html['response'] = $this->renderPartial('_render-partial-shipment/fedex/SuccessfulShippingRequest',['response'=>$response,
                'tracking_number'=>$response['TrackingNumber'],'amount_inc_taxes'=>$shipping_inc_tax_amount,'currency_code'=>'USD',
                'label'=>$CourierName.'-Item-'.explode(',', $_POST['orderItemIds'])[0].'.pdf','packing_slip'=>$packing_slip]);
        }else{
            $html['response'] = $this->renderPartial('_render-partial-shipment/fedex/SuccessfulShippingRequest',['response'=>$response]);
        }


        return json_encode($html);
    }

    public function actionGetPickUpDates(){

        $channel_id = $this->exchange_values('id','channel_id',$_GET['order_number'],'orders');
        $order = Orders::find()->where(['id'=>$_GET['order_number']])->one();
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        $response = json_decode(ShopeeUtil::GetTimsSlot($channel,$order->order_number,(int)$_GET['address_id']));
        $pickup_time = $this->renderPartial('_render-partial-shipment/shopee/pickup-time',['dates'=>($response)]);
        return json_encode(['pickupTime'=>$pickup_time,'msg'=>isset($response->error) ? $response->msg : 0 ]);

    }
    public function actionGetLogistics(){
        //echo '<h1>Philips Official</h1>';
        /*$channel = Channels::find()->where(['id'=>23])->one();
        $response = json_decode(ShopeeUtil::GetParameterForInit($channel,'2001314231UF3X'));*/
        $channel = Channels::find()->where(['id'=>22])->one();
        $response = json_decode(ShopeeUtil::GetParameterForInit($channel,'200111D2PA24RR'));
        $this->debug($response);
        $pickup_time = $this->renderPartial('_render-partial-shipment/shopee/pickup-time',['dates'=>($response)]);
        return json_encode(['pickupTime'=>$pickup_time,'msg'=>isset($response->error) ? $response->msg : 0 ]);
    }
    public function actionShopeeInit(){

        $channel_id = $this->exchange_values('id','channel_id',$_GET['order_number'],'orders');
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        $order = Orders::find()->where(['id'=>$_GET['order_number']])->one();


        if ( $_GET['type'] == 'pickup' )
        {
            $Detail = [
                'pickup' =>
                    [
                        'address_id' => (int) $_GET['address_id'],
                        'pickup_time_id' => $_GET['pickup_time']
                    ]
            ];
        }
        elseif( $_GET['type']=='dropoff' )
        {
            $Detail = [
                'dropoff' => []
            ];
            ( isset($_GET['branch_id']) ) ? $Detail['dropoff']['branch_id'] = (int) $_GET['branch_id'] : '';
        }

        $response = ShopeeUtil::ShopeeInit($channel,$order->order_number,$Detail);
        $resp_decode = json_decode($response);

        if ( isset($resp_decode->error) ){
            $Failed = $this->renderPartial('_render-partial-shipment/shopee/failed',['error_response'=>$resp_decode->msg]);
            return json_encode( [ 'content' => $Failed ] );
        }

        if ( isset($resp_decode->result->errors) && $resp_decode->result->errors ){
            $Failed = $this->renderPartial('_render-partial-shipment/shopee/failed',['error_response'=>$resp_decode->result->errors[0]->error_description]);
            return json_encode( [ 'content' => $Failed ] );
        }

        /*
         * There are two methods to ship item , dropoff and pickup
         * Dropoff means our customer support representative will go to the nearest branch of poslaju to submit the item to deliver it to customer
         * Pickup means poslaju rider will come and pickup the item from seller
         *
         * So in dropoff we don't get the tracking number on the spot. But we get it when we deliver it to poslaju branch
         *
         * */

        if ( array_key_exists('tracking_number' , $resp_decode) ){
            /**
             * Get Air way bill
             * Shopee API will return the bill link and we will be able to print it
             * */
            sleep(6);
            $Airway_Bill = ShopeeUtil::GetAirwayBill($channel,$order->order_number);
            $Airway_Bill_decode = json_decode($Airway_Bill);

            if(isset($Airway_Bill_decode->error)){
                $Failed = $this->renderPartial('_render-partial-shipment/shopee/failed',['error_response'=>$Airway_Bill_decode->msg]);
                return json_encode( [ 'content' => $Failed ] );
            }

            if ( isset($Airway_Bill_decode->result->errors) && $Airway_Bill_decode->result->errors ){
                $Failed = $this->renderPartial('_render-partial-shipment/shopee/failed',['error_response'=>$Airway_Bill_decode->result->errors[0]->error_description]);
                return json_encode( [ 'content' => $Failed ] );
            }

            // Update label and tracking number to order items.
            OrderUtil::UpdateShippingDetail($order->id,$Airway_Bill_decode->result->airway_bills[0]->airway_bill,$resp_decode->tracking_number,'shopee-fbs');
            $Success = $this->renderPartial('_render-partial-shipment/shopee/success',
                [
                    'airway_bill_link'=>$Airway_Bill_decode->result->airway_bills[0]->airway_bill,
                    'tracking_number' => $resp_decode->tracking_number
                ]);
            return json_encode( [ 'content' => $Success ] );
        }else{
            $Failed = $this->renderPartial('_render-partial-shipment/shopee/failed',['error_response'=>$resp_decode->msg]);
            return json_encode( [ 'content' => $Failed ] );
        }
    }
    public function actionLazadaLabel(){
        $a = $_POST['label'];
        $label = base64_decode($a);
        echo $this->renderPartial('_render-partial-shipment/lazada/label',['label'=>$label]);
        die;
    }
    public function actionSaveInvoiceLazada(){

        $OrderItems = HelpUtil::GetOrderItems($_GET['order_id']);
        $infoNote='';
        $label='';
        $Invoicelabel = '';

        $Items=[];
        foreach ( $OrderItems as $Val ) {
            $Items[] = $Val['order_item_id'];
        }

        $PackOrder = LazadaUtil::SetStatusToPackedByMarketplace($_GET['channel_id'],$_GET['ShippingProvider'],'dropship','['.implode(',',$Items).']');
        if ($PackOrder->code!="0" && $PackOrder->code!="120" /*Because 120 is seller cant change shipment provider. No body is changing*/){
            $msg="Error while updating order status to 'PackedByMarketPlace' <br />";
            $msg.=$PackOrder->message;
            $Failed = $this->renderPartial('_render-partial-shipment/lazada/failed',['error_response'=>$msg]);
            return json_encode( [ 'content' => $Failed ] );

        }else{
            if (isset($PackOrder->data->order_items[0]->shipment_provider)){
                $shipperName=$PackOrder->data->order_items[0]->shipment_provider;
            }else{
                $OrderDetail = LazadaUtil::GetOrderItems($_GET['order_id'],$_GET['channel_id']);
                $shipper = $OrderDetail->data[0]->shipment_provider;
                $shipper = explode(',',$shipper);
                $shipperName = str_replace('Pickup: ','',$shipper[0]);
            }

            if($shipperName!=$_GET['ShippingProvider']){
                $infoNote = 'You selected "'.$_GET['ShippingProvider'].'" but Lazada automatically assigned "'.$shipperName.'" to ship your order.';

            }
        }

        $GetShippingLabel = LazadaUtil::GetDocument($_GET['channel_id'],'shippingLabel','['.implode(',',$Items).']');

        if ( $GetShippingLabel['code']=='0' ){
            if (isset($GetShippingLabel['data']['document']['file']))
                $label=$GetShippingLabel['data']['document']['file'];

            // Save invoice number
            $resp = LazadaUtil::SetInvoiceNumber($_GET['channel_id'],$Items,$_GET['invoice_number']);
            $final_resp = [];
            foreach ( $resp as $value ){
                $response = json_decode($value,1);
                if ( isset($response['code']) && $response['code']=='InvalidParameter' ){
                    $final_resp[] = 0;
                }else{
                    $final_resp[] = 1;
                }
            }
            if ( in_array(0,$final_resp) ){
                $msg="Something went wrong when updating Invoice number.";
                $Failed = $this->renderPartial('_render-partial-shipment/lazada/failed',['error_response'=>$msg]);
                return json_encode( [ 'content' => $Failed ] );

            }
            $GetInvoiceLabel = LazadaUtil::GetDocument($_GET['channel_id'],'invoice','['.implode(',',$Items).']');
            if ( $GetInvoiceLabel['code']=='0' && isset($GetInvoiceLabel['data']['document']['file']) ) {
                $Invoicelabel = $GetInvoiceLabel['data']['document']['file'];
            }

            if (isset($PackOrder->data->order_items[0]->tracking_number)){
                $TrackingNumber = $PackOrder->data->order_items[0]->tracking_number;
            }else{
                $msg="Tracking Number was not issued by marketplace";
                $Failed = $this->renderPartial('_render-partial-shipment/lazada/failed',['error_response'=>$msg]);
                return json_encode( [ 'content' => $Failed ] );
            }

            // now finally mark the order as ready to ship
            $ReadyToShip = LazadaUtil::SetStatusToReadyToShip($_GET['channel_id'],'['.implode(',',$Items).']','dropship',$TrackingNumber,$shipperName);
            if ( $ReadyToShip->code != '0' ){
                $msg="Error when updating order status to Read_To_Ship";
                $Failed = $this->renderPartial('_render-partial-shipment/lazada/failed',['error_response'=>$msg]);
                return json_encode( [ 'content' => $Failed ] );
            }else{
                $orderidPk = $this->exchange_values('order_id','id',$_GET['order_id'],'orders');
                $r = OrderUtil::UpdateShippingDetail($orderidPk,$label,$TrackingNumber,'lazada-fbl');
                $Success = $this->renderPartial('_render-partial-shipment/lazada/success',
                    [
                        'shipping_label'=>$label,
                        'tracking_number' => $TrackingNumber,
                        'invoiceLabel'=>$Invoicelabel,
                        'infoNote'=>$infoNote,
                        'shipperName'=>$shipperName
                    ]);
                return json_encode( [ 'content' => $Success ] );
            }
        }
        else{
            $msg="Error when calling GetDocument <br />";
            $msg.=$GetShippingLabel['message'];
            $Failed = $this->renderPartial('_render-partial-shipment/lazada/failed',['error_response'=>$msg]);
            return json_encode( [ 'content' => $Failed ] );
        }

    }
    public function actionGetOrderDocument(){
        $channel = Channels::find()->where(['id'=>25])->one();
        $orderId = '242990456354888';
        $detail = LazadaUtil::GetDocument($channel->id,'shippingLabel','[242990456554888]');
        $this->debug($detail);
    }
    public function actionMarketplaceShops(){
        if ($_GET['marketplace']=='all'){
            $shops = Channels::find()->where(['is_active'=>1])->asArray()->all();
        }else{
            $shops = Channels::find()->where(['is_active'=>1,'marketplace'=>$_GET['marketplace']])->asArray()->all();
        }
        $shop_options='';
        foreach ( $shops as $key=>$value ){
            $shop_options .= '<option class="shop-options" value="'.$value['id'].'">'.$value['name'].'</option>';
        }
        echo ($shop_options);
    }
}