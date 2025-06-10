<?php

namespace backend\controllers;

use app\models\PoImportTemp;
use backend\models\PasswordResetRequestForm;
use backend\models\ResetPasswordForm;
use backend\util\DealsUtil;
//use common\models\search\UserSearch;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use backend\util\GraphsUtil;
use backend\util\HelpUtil;
use backend\util\RecordUtil;
use common\models\CalculatorForm;
use common\models\Category;
use common\models\CategoryCopy;
use common\models\Channels;
use common\models\ChannelsDetails;
use common\models\CompetitivePricing;
use common\models\CostPrice;
use common\models\CustomersInfo;
use common\models\DealMaker;
use common\models\DealsMaker;
use common\models\DealsMakerSkus;
use common\models\OrderItems;
use common\models\PoDetails;
use common\models\ProductDetails;
use common\models\Products;
use common\models\ProductStocks;
use common\models\Subsidy;
use common\models\SubsidyLog;
use common\models\User;
use Yii;
use yii\base\ErrorException;
use yii\db\Query;
use yii\helpers\Json;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'request-password-reset', 'reset-password','new-dashboard'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index',
                            'category-import',
                            'subsidy-import',
                            'new-subsidy-import',
                            'single-subsidy-import',
                            'main-category-import',
                            'commission-import',
                            'fbl-import',
                            'seller-list',
                            'status-import',
                            'assign-import',
                            'cp-import',
                            // channels live data test methods
                            'estreet-data',
                            'product-import',
                            'import-customers',
                            'calculator',
                            'merge-isis-sku',
                            'threshold-import',
                            'new-sku-import',
                            'import-sales-csv',
                            'import-sales-target-csv',
                            'import-category-sku',
                            'test-curl',
                            'fix-order-items',
                            'change-gst',
                            'fetch-all-pending-orders',
                            'po-archive-import',
                            'product',
                            'fbl-io-import',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }


    public function beforeAction($action)
    {
        if ($action->id == 'calculator') {
            $this->enableCsrfValidation = false;
        }


        return parent::beforeAction($action);
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex_old_deprecated()
    {
      //  echo  Yii::$app->controller->id; die();
        $warehouses_Stocks = HelpUtil::WarehousesStocks();
        $stocksTrans = HelpUtil::getStockInTrans();
        $totalRevenue = HelpUtil::getTotalRevenueForMainDashboard();
        $distributorsale=HelpUtil::getDistributorSale();



        if(Yii::$app->permissionCheck->check_logged_in_user_authorization('site')) {
            return $this->render('index',
                [
                    'warehouses_Stocks' => $warehouses_Stocks,
                    'stocksTrans' => $stocksTrans,
                    'totalRevenue' => $totalRevenue,
                    'distributorsale' => $distributorsale
                    ]);
        } else {
            return $this->render('error',
                [
                    'name' => 'Forbidden (#403)',
                    'message' => 'YOU ARE NOT ALLOWED TO PERFORM ACTION AND VIEW THIS PAGE',

                ]);
        }

    }

    /**
     * new dashboard
     */
    public function actionIndex()
    {
        $warehouses_Stocks = HelpUtil::WarehousesStocks();
        $stocksTrans = HelpUtil::getStockInTrans();
        $categories=Category::find()->where(['parent_id'=>NULL])->orWhere(['parent_id'=>0])->asArray()->all();
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
        $product_styles=Products::find()->select('style')->distinct()->asArray()->all();
        $distributorsale=HelpUtil::distributor_sale();
        $marketplace_sales_cont=GraphsUtil::sales_contributions('shop');
        $top_performers=GraphsUtil::top_performers();
        $first_order=GraphsUtil::first_order_date();
        $parent_and_variations=\backend\util\ProductsUtil::parents_and_variation_counts(); //count parent and variation
        $salesForcastData = GraphsUtil::getSalesForcast();
        $forcast = HelpUtil::getMonthSales();
        $sales_by_shop_per_month = GraphsUtil::SalesGraphByShop(); // sales graph data by shop/marketplace every month
        $brands_sales=GraphsUtil::brands_sales();

        // echo "<pre>";
       // print_r($marketplace_sales_cont);
      //  die();
        if(Yii::$app->permissionCheck->check_logged_in_user_authorization('site')) {
            return $this->render('index',
                [
                    'warehouses_Stocks' => $warehouses_Stocks,
                    'stocksTrans' => $stocksTrans,
                    'categories'=>$categories,
                    'brands'=>$brands,
                    'product_styles'=>$product_styles,
                    'distributorsale' => $distributorsale,
                    'marketplace_sales_cont' => $marketplace_sales_cont,
                   'top_performers'=>$top_performers,
                    'first_order'=>$first_order, // when first order placed // days, date ,weeks , months
                   'parent_and_variations'=>$parent_and_variations,
                   'salesForcastData'=>$salesForcastData,
                   'forcast'=>$forcast,
                   'sales_by_shop_per_month'=>$sales_by_shop_per_month,
                    'brands_sales'=>$brands_sales
                ]);
                } else {
                return $this->render('error',
                    [
                    'name' => 'Forbidden (#403)',
                    'message' => 'YOU ARE NOT ALLOWED TO PERFORM ACTION AND VIEW THIS PAGE',

                    ]);
            }

    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if($model->passwordExpired) {
                $redirecturl = '/user/update?id=' . $model->userId .'&password_expired=true';
                $this->redirect(array($redirecturl));
            }
            else {
                $this->goBack();
            }
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {

        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionCategoryImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $cp = Products::find()->where(['sku' => $getData[0]])->one();
                    if ($cp) {
                        $sub = Category::find()->where(['name' => $getData[1]])->andWhere(['not', ['main_category_id' => null]])->one();
                        if ($sub) {
                            $cp->sub_category = $sub->id;
                            $cp->update(false);
                        }
                    }

                }
            }
        }


        return $this->render('_ci');

    }

    public function actionFblIoImport()
    {
        if (isset($_POST['_csrf-backend'])) {


            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    $sku = trim($getData[7]);
                    $qty = $getData[14];
                    $ioNumber = $getData[0];
                    $poDetails = PoDetails::find()->joinWith('po')->where(['product_stocks_po.er_no'=>$ioNumber])->andWhere(['sku'=>$sku])->one();
                    if($poDetails)
                    {
                        $poDetails->er_qty = $qty;
                        $poDetails->update();
                    }

                }
            }
        }


        return $this->render('_ci');

    }

    public function actionSubsidyImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    $skuId = (isset($skuList[$getData[0]])) ? $skuList[$getData[0]] : '';
                    if ($skuId) {

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 1]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '1';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '1';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 2]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '2';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[3]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $$sub->subsidy = str_replace('%', '', $getData[3]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '2';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 3]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '3';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[2]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[2]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '3';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 5]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '5';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[4]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[4]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '5';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 6]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '6';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);


                            $sub->subsidy = str_replace('%', '', $getData[5]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[5]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '6';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 11]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '11';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);


                            $sub->subsidy = str_replace('%', '', $getData[8]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[8]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '11';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 9]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '9';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[7]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[7]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '9';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 10]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '10';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[6]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[6]);
                            $sub->ao_margins = str_replace('%', '', $getData[9]);
                            $sub->margins = str_replace('%', '', $getData[9]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '10';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                    }

                }
            }
        }


        return $this->render('_subsidy_import');

    }

    public function actionStatusImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $cp = Products::findOne(['sku' => trim($getData[0])]);
                    $status = strtolower($getData[1]);
                    $status = ucwords($status);
                    if ($cp) {
                        $cp->stock_status = $status;
                        //$cp->stock_status = str_replace('%', '', $getData[1]);
                        $cp->update(false);
                    }
                }
            }
        }


        return $this->render('_ci');

    }

    public function actionMainCategoryImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {

                    $grp = CategoryCopy::find()->where(['name' => $getData[1]])->andWhere(['is_main' => '1'])->one();
                    $sub = CategoryCopy::find()->where(['name' => $getData[0]])->andWhere(['not', ['main_category_id' => null]])->one();
                    if ($sub && $grp) {
                        $sub->group_category_id = $grp->id;
                        $sub->update(false);
                    }

                }
            }
        }
        return $this->render('_ci');

    }

    public function actionFblImport()
    {
        $users = User::find()->where(['role_id' => 2])->all();
        $ulist = $err = [];
        foreach ($users as $u)
            $ulist[$u->full_name] = $u->id;

        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;
                $skuUsers = [];
                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }

                    $sku = trim($getData[0]);
                    $rccp = $getData[5];
                    $cost = $getData[3];


                    $cp = Products::findOne(['sku' => $sku]);
                    /*$cat = Category::findOne(['name' => $category]);

                    if (!$cat)
                        echo $category;*/

                    if ($cp) {
                        $cp->without_gst_cost = $getData['4'];;
                        $cp->rccp_cost = trim($rccp);
                        $cp->without_gst_rccp_cost = trim($getData['6']);
                        $cp->nc12 = $getData['1'];
                        $cp->barcode = $getData['2'];
                        $cp->cost = trim($getData['3']);
                        $cp->save(false);
                    } else {
                        echo $sku . "<br/>";
                    }


                }

                /*foreach ($skuUsers as $k => $sk) {
                    $m = User::findOne(['id' => $k]);
                    $skus = ($m->skus != '') ? $m->skus . ',' : $m->skus  ;
                    $newSkus = implode(',', $sk);
                    $m->skus = $skus.$newSkus;
                    $m->update(false);
                }*/
            }
        }
        return $this->render('_ci');
    }

    public function actionAssignImport()
    {
        $users = User::find()->where(['role_id' => 2])->all();
        $ulist = $err = [];
        foreach ($users as $u)
            $ulist[$u->full_name] = $u->id;

        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;
                $skuUsers = [];
                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $skuId = (isset($skuList[$getData[0]])) ? $skuList[$getData[0]] : '';
                    $assignee = $getData[1];
                    if ($skuId)
                        $skuUsers[$ulist[ucwords($assignee)]][] = $skuId;

                }
                foreach ($skuUsers as $k => $sk) {
                    $m = User::findOne(['id' => $k]);
                    /* $skus = ($m->skus != '') ? $m->skus . ',' : $m->skus  ;
                     $newSkus = implode(',', $sk);
                     */
                    $m->skus = implode(',', $skuUsers[$k]);
                    $m->update(false);
                }
            }
        }
        return $this->render('_ci');

    }

    public function actionCommissionImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {

                    $cat = $getData[0];
                    $lazada = $getData[1];
                    $shopee = $getData[2];
                    $street = $getData[3];
                    $lelong = $getData[4];
                    $blip = $getData[5];
                    $shoppu = $getData[6];

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 1;
                    $ch->commission = str_replace('%', '', $lazada);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 2;
                    $ch->commission = str_replace('%', '', $shopee);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 3;
                    $ch->commission = str_replace('%', '', $street);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 5;
                    $ch->commission = str_replace('%', '', $lelong);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 6;
                    $ch->commission = str_replace('%', '', $blip);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);

                    $ch = new ChannelsDetails();
                    $ch->channel_id = 7;
                    $ch->commission = str_replace('%', '', $shoppu);
                    $ch->category_id = $cat;
                    $ch->updated_by = 1;
                    $ch->save(false);
                }
            }
        }
        return $this->render('_ci');

    }

    public function actionSellerList($q = null)
    {
        $query = new Query;

        $query->select('seller_name')
            ->from('channel_sellers')
            ->where('seller_name LIKE "%' . $q . '%"')
            ->orderBy('seller_name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        $out = [];
        foreach ($data as $d) {
            $out[] = ['value' => $d['seller_name']];
        }
        echo Json::encode($out);
    }

    public function actionCpImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $skuId = (isset($skuList[$getData[0]])) ? $skuList[$getData[0]] : '';
                    if ($skuId) {
                        $cp = new CompetitivePricing();
                        $cp->sku_id = $skuId;
                        $cp->channel_id = '1';
                        $cp->seller_name = $getData[4];
                        $cp->low_price = $getData[3];
                        $cp->created_at = '2017-11-17';
                        $cp->keywords = '';
                        $cp->created_by = Yii::$app->user->id;
                        $cp->price_change = '0';
                        $cp->save(false);

                        $cp = new CompetitivePricing();
                        $cp->sku_id = $skuId;
                        $cp->channel_id = '2';
                        $cp->seller_name = $getData[6];
                        $cp->low_price = $getData[5];
                        $cp->created_at = '2017-11-17';
                        $cp->keywords = '';
                        $cp->created_by = Yii::$app->user->id;
                        $cp->price_change = '0';
                        $cp->save(false);

                        $cp = new CompetitivePricing();
                        $cp->sku_id = $skuId;
                        $cp->channel_id = '3';
                        $cp->seller_name = $getData[2];
                        $cp->low_price = $getData[1];
                        $cp->created_at = '2017-11-17';
                        $cp->keywords = '';
                        $cp->created_by = Yii::$app->user->id;
                        $cp->price_change = '0';
                        $cp->save(false);
                    }

                }
            }
        }
        return $this->render('_ci');

    }


    public function actionNewSubsidyImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    $skuId = (isset($skuList[$getData[0]])) ? $skuList[$getData[0]] : '';
                    if ($skuId) {

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 1]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '1';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '1';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 2]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '2';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '2';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 3]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '3';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '3';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 5]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '5';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '5';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 6]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '6';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);


                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);

                            $sub->updated_by = '1';
                            $sub->channel_id = '6';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 11]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '11';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);


                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '11';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 9]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '9';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '9';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 10]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '10';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '10';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }
                    }

                }
            }
        }


        return $this->render('_subsidy_import');

    }

    public function actionSingleSubsidyImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    $skuId = (isset($skuList[$getData[0]])) ? $skuList[$getData[0]] : '';
                    if ($skuId) {

                        $sub = Subsidy::findOne(['sku_id' => $skuId, 'channel_id' => 3]);
                        if ($sub) {
                            //log file
                            $lsub = new SubsidyLog();
                            $lsub->subsidy = $sub->subsidy;
                            $lsub->ao_margins = $sub->ao_margins;
                            $lsub->margins = $sub->margins;
                            $lsub->channel_id = '3';
                            $lsub->sku_id = $skuId;
                            $lsub->save(false);

                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->update(false);
                        } /*else {
                            $sub = new Subsidy();
                            $sub->subsidy = str_replace('%', '', $getData[1]);
                            $sub->ao_margins = str_replace('%', '', $getData[2]);
                            $sub->margins = str_replace('%', '', $getData[1]);
                            $sub->updated_by = '1';
                            $sub->channel_id = '1';
                            $sub->sku_id = $skuId;
                            $sub->save(false);
                        }*/

                    }

                }
            }
        }


        return $this->render('_subsidy_import');

    }

    public function actionProductImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $skuId = (isset($skuList[$getData[1]])) ? $skuList[$getData[1]] : '';
                    if ($skuId) {
                        $pd = new ProductDetails();
                        $pd->sku_id = $skuId;
                        $pd->channel_id = '3';
                        $pd->sku_code = $getData[0];
                        $pd->stocks = $getData[2];
                        $pd->sale_price = str_replace('RM ', '', $getData[3]);
                        $pd->shipping_price = str_replace(['RM ', '~'], '', $getData[4]);
                        $pd->last_update = date('Y-m-d H:i:s');
                        $pd->save(false);

                    }

                }
            }
        }
        return $this->render('_ci');
    }

    public function actionCalculator()
    {
        $model = new CalculatorForm();
        $margins=[];
        $Channel=[];
        if ($model->load(Yii::$app->request->post())) {

            if ($model->validate()) {


                //die;
                $margins = HelpUtil::ApplyMarketplaceCharges($model->channel,$model->sku,$model->price_sell,$model->subsidy,$model->fbl,$model->cost);
                //echo '<pre>';print_r($model->qty);die;
                //echo $model->q
                //echo gettype($margins['margin_amount']);die;
                $margins['margins_with_quantity'] = (double)$margins['margin_amount'] * $model->qty;

                $Channel = Channels::find()->where(['id'=>$model->channel])->asArray()->one();

                /*$params['sku_id'] = $model->sku;
                $params['channel_id'] = $model->channel;
                $params['price_sell'] = $model->price_sell;
                $params['fbl'] = $model->fbl;
                $params['subsidy'] = $model->subsidy;
                $params['qty'] = $model->qty;
                $params['cost'] = $model->cost;
                $is_fbl = 0;

                $stock = HelpUtil::getFblStock($params['sku_id'], $params['channel_id']);
                if ($stock > 0 && $stock >= $params['qty']) {
                    $is_fbl = 1;
                }
                $params['fbl'] = $is_fbl;
                $values = HelpUtil::getSkuInfo($params, true, true);*/

            }
        }
        return $this->render('calculator', [
            'model' => $model,
            'margins' => $margins,
            'channelDetail'=>$Channel
        ]);
    }

    public function actionMergeIsisSku()
    {

        $connection = Yii::$app->getDb();
        $sql = "SELECT 
                  isis_sku,sale_price,selling_status,SUBSTRING(isis_sku, 1, 9) AS refine_sku
                FROM
                  `product_details` pd 
                WHERE isis_sku IS NOT NULL AND sale_price IS NOT NULL AND sku_id IS NULL ";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        foreach ($result as $v) {
            $model = Products::findOne(['sku' => $v['refine_sku']]);

            if (!$model) {
                $model = new Products();
                $model->sku = $v['isis_sku'];
                $model->name = $v['isis_sku'];
                $model->cost = $v['sale_price'];
                $model->rccp_cost = $v['sale_price'];
                $model->sub_category = 1;
                $model->selling_status = $v['selling_status'];
                $model->stock_status = $v['selling_status'];
                $model->is_active = 1;
                $model->save(false);
            }
        }


    }

    public function actionMigrateDealMaker()
    {
        // old deal maker obj
        $oldDealMakerObj = DealMaker::find()->all();
        foreach ($oldDealMakerObj as $k => $v) {
            $v->name = str_replace('/', '_', $v->name);
            $v->name = str_replace('.', '', $v->name);
            $dealsMakerObj = new DealsMaker();
            $dealsMakerObj->requester_id = $v->requester_id;
            $dealsMakerObj->name = $v->name;
            $dealsMakerObj->channel_id = $v->channel_id;
            $dealsMakerObj->start_date = $v->start_date;
            $dealsMakerObj->end_date = $v->end_date;
            $dealsMakerObj->created_at = $v->created_at;
            $dealsMakerObj->updated_at = $v->updated_at;
            $dealsMakerObj->motivation = $v->motivation;
            if ($dealsMakerObj->save()) {

                // adding SKUs
                $dealsMakerSkusObj = new DealsMakerSkus();
                $dealsMakerSkusObj->approval_id = $v->requester_id;
                $dealsMakerSkusObj->sku_id = $v->sku_id;
                $dealsMakerSkusObj->status = $v->status;
                $dealsMakerSkusObj->approval_comments = $v->comments;
                $dealsMakerSkusObj->requestor_reason = $v->reasons;
                $dealsMakerSkusObj->deal_margin = $v->deal_margin;
                $dealsMakerSkusObj->deal_target = $v->deal_qty;
                $dealsMakerSkusObj->deal_subsidy = $v->deal_subsidy;
                $dealsMakerSkusObj->deal_subsidy = $v->deal_subsidy;
                $dealsMakerSkusObj->deal_price = $v->deal_price;
                $dealsMakerSkusObj->deals_maker_id = $dealsMakerObj->id;
                $dealsMakerSkusObj->deals_maker_id = $dealsMakerObj->id;
                $dealsMakerSkusObj->save(false);
            } else {
                print_r($dealsMakerObj->getErrors());
            }
        }
    }

    public function actionThresholdImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $skuList = HelpUtil::getStkSkuList('sku');
            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;
                $noSkus = [];
                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    $sku = str_replace('_', '/', $getData[0]);
                    $pd = ProductDetails::find()->where(['isis_sku' => trim($sku)])->one();
                    if ($pd) {
                        $sub = ProductStocks::findOne(['stock_id' => $pd->id]);
                        if ($sub) {
                            $sub->is_active = '1';
                            $sub->stock_status = $getData[7];
                            $sub->isis_threshold = (int)$getData[5];
                            $sub->isis_threshold_critical = (int)$getData[6];
                            $sub->datetime_updated = date('Y-m-d H:i:s');
                            $sub->update(false);
                        } else {
                            $sub = new ProductStocks();
                            $sub->stock_id = $pd->id;
                            $sub->is_active = '1';
                            $sub->stock_status = $getData[7];
                            $sub->datetime_updated = date('Y-m-d H:i:s');
                            $sub->isis_threshold = (int)$getData[5];
                            $sub->isis_threshold_critical = (int)$getData[6];
                            if (!$sub->save(false)) {
                                print_r($sub->getErrors());
                            }
                        }
                    } else {
                        $noSkus[] = $getData[0];
                    }


                }
                echo "<pre>";
                print_r($noSkus);
                die();
            }
        }
        return $this->render('_ci');
    }

    public function actionNewSkuImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $csvFile = $_FILES['csv'];
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


                        //assign foc to main asku
                        $ps = Products::find()->where(['sku' => trim($getData[0])])->one();
                        if (!$ps) {
                            $ps = new Products();
                            $ps->sku = $getData[0];
                            $ps->cost = $getData[5];
                            $ps->without_gst_cost = $getData[5];
                            $ps->name = $getData[2];
                            $ps->promo_price = $getData[4];
                            $ps->nc12 = $getData[1];
                            $ps->rccp_cost = $getData[3];
                            $ps->without_gst_rccp_cost = $getData[3];
                            $ps->sub_category = $getData[6];
                            $ps->save(false);
                        }

                        // add subsidy and margins
                        $cp = Products::find()->where(['sku' => $getData[0]])->one();
                        if ($cp) {
                            $ch = Channels::find()->where(['is_active' => '1'])->all();
                            foreach ($ch as $c) {
                                $sub = subsidy::find()->where(['sku_id' => $cp->id])->andWhere(['channel_id' => $c->id])->one();
                                if (!$sub)
                                    $sub = new subsidy();
                                $sub->sku_id = $cp->id;
                                $sub->subsidy = $getData[8];
                                $sub->margins = $getData[7];
                                $sub->ao_margins = $getData[7];
                                $sub->start_date = date('Y-m-d h:i:s');
                                $sub->end_date = date('Y-m-d h:i:s', strtotime('+ 1 month'));
                                $sub->channel_id = $c->id;
                                $sub->updated_by = '1';
                                $sub->save(false);

                            }

                        } else {
                            echo $getData[0] . "<br/>";
                        }

                        // assign sku to user
                        /*$user = User::find()->where(['full_name'=>$getData[9]])->one();
                        if($user)
                        {
                            $user->skus = $user->skus .",".$lastId;
                            $user->update();
                        }*/


                    }

                }
            }
        }
        return $this->render('_ci');
    }

    public function actionTestx()
    {
        //GraphsUtil::getAverageDaySales();
        // echo "<pre>";
        print_r(HelpUtil::getSkuActualStockNumber());

    }

    public function actionTestCurl()
    {
        /* header('Access-Control-Allow-Origin: https://shopee.com.my');

         $curl = curl_init();

         curl_setopt_array($curl, array(
             CURLOPT_URL => "https://shopee.com.my/api/v1/item_detail/?item_id=124063020&shop_id=12413844",
             CURLOPT_ENCODING => "",
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_TIMEOUT => 30,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => "GET",
         ));

         $response = curl_exec($curl);
         $err = curl_error($curl);

         curl_close($curl);

         if ($err) {
             echo "cURL Error #:" . $err;
         } else {

            //header('Content-Type: application/json');
             echo $response;
         }*/
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        //      'Content-Length: ' . strlen($data))
        );
        curl_setopt($curl, CURLOPT_URL, 'https://shopee.com.my/api/v1/item_detail/?item_id=124063020&shop_id=12413844');
        $result = curl_exec($curl);
        $json = json_decode($result, true);
        curl_close($curl);
        print_r($result);
        echo "\n";

    }

    public function actionFixOrderItems($mp)
    {
        ini_set('memory_limit', '-1');
        $connection = Yii::$app->db;
        $sql = "SELECT oi.* FROM order_items oi 
                INNER JOIN orders o ON o.id = oi.`order_id`
                INNER JOIN channels c ON c.id = o.`channel_id`
                WHERE c.`marketplace`= '$mp'";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $v) {
            $fr = json_decode($v['full_response'], true);
            if ($mp == 'lazada') {
                if (!isset($fr['item_price'])) {
                    $price = $fr['ItemPrice'];
                    $pprice = $fr['PaidPrice'];
                } else {
                    $price = $fr['item_price'];
                    $pprice = $fr['paid_price'];
                }
            }
            if ($mp == 'street') {
                $price = $fr['ordAmt'];
                $pprice = $fr['ordPayAmt'];
            }
            if ($mp == 'shopee') {
                $price = $fr['variation_discounted_price'];
                $pprice = $fr['variation_discounted_price'];
            }
            if ($mp == 'shop') {
                $price = $fr['price'];
                $pprice = $fr['paid_price'];
            }

            $price = str_replace(',', '', $price);
            $pprice = str_replace(',', '', $pprice);

            $oi = OrderItems::find()->where(['id' => $v['id']])->one();
            $oi->price = number_format($price, 2, '.', '');
            $oi->paid_price = number_format($pprice, 2, '.', '');
            $oi->save(false);
        }

        echo $mp . " Udpated!!!";
    }

    public function actionFetchAllPendingOrders()
    {
        $sql = "SELECT o.order_id,order_created_at,o.order_status,c.`prefix`,oi.`item_sku`,oi.`item_status`,o.`full_response`,c.marketplace FROM orders o
                INNER JOIN channels c ON c.id = o.`channel_id`
                INNER JOIN order_items oi ON oi.`order_id` = o.id
                WHERE o.order_status = 'pending'";

        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $re) {
            $jsAr = json_decode($re['full_response'], true);
            if ($re['marketplace'] == 'lazada') {
                $status = isset($jsAr['Statuses']) ? $jsAr['Statuses'] : $jsAr['statuses'];
                $re['order_status'] = implode(',', $status);

            }
            if ($re['marketplace'] == 'shopee') {
                $re['order_status'] = $jsAr['order_status'];
            }

            $refine[] = $re;
        }

        $html = "order_id,order_created,order_status,prefix,item_sku,item_status\n";
        foreach ($refine as $re) {
            $html .= $re['order_id'] . ",";
            $html .= $re['order_created_at'] . ",";
            $html .= $re['order_status'] . ",";
            $html .= $re['prefix'] . ",";
            $html .= $re['item_sku'] . ",";
            $html .= $re['item_status'];
            $html .= "\n";
        }
        echo $html;
    }

    public function _ApiCall($apiUrl, $authorizeHead = [], $method = 'GET', $curl_port = "", $post_fields = "")
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
            CURLOPT_HTTPHEADER => $authorizeHead
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

    public function _refineResponse($response)
    {
        $response = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '', $response);
        $searchBadTags = ['ns2:'];
        $response = str_replace($searchBadTags, '', $response);
        $r = simplexml_load_string($response);
        $json = json_encode($r);
        $refine_data = json_decode($json, true);

        return $refine_data;
    }

    public function actionErcTest()
    {

        // login ISIS API
        $poDoc = "A&O-DAP145IS";
        $remarks = "";
        $poDate = date("Y-m-d h:i:s");

        // login ISIS API
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        $authSession = $refine['returnObject']['BondSession'];

        // add ER
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/addErForStorageClientWebApi";
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $postFields = [
            'erDate' => date("d/m/Y h:i:s", strtotime($poDate)),
            'erType' => strtoupper("Purchase Order"),
            'docNo' => $poDoc,
            'storageClientNo' => "SSL0333",
            'remark' => $remarks,
            'supplierNo' => "",
        ];
        $postFields = json_encode($postFields);

        $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);

        $refine = json_decode($response, true);

        if (isset($refine['returnObject'])) {
            echo "ERC:" . $refine['returnObject'];
            $sql = "select * from tmp_erc_items_6122018";
            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();

            // add ER details
            foreach ($result as $ps) {
                $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ErDetail/addErDetailForStorageClientWebApi";
                $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
                $port = "5191";
                $postFields = [
                    'dataVersion' => 1,
                    'erNo' => $refine['returnObject'],
                    'storageClientNo' => "SSL0333",
                    'skuDesc' => $ps['sku'],
                    'storageClientSkuNo' => $ps['sku'],
                    'erQty' => $ps['qty'],
                    'recvPrintLabel' => true,
                    'scanSerialNo' => false,
                ];
                $postFields = json_encode($postFields);
                $responsex = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
                $refinex[$ps['sku']] = json_decode($responsex, true);
            }
        }

        echo "<pre>";
        print_r($refinex);
    }

    public function actionTest()
    {
        // login ISIS API
        /* $poDoc = "A&O-DAP150IS";
         $remarks = "";
         $poDate = date("Y-m-d h:i:s");

         // login ISIS API
         $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
         $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
         $port = "5191";
         $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
         $refine = self::_refineResponse($response);
         $authSession = $refine['returnObject']['BondSession'];
         // add ER
         $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/addErForStorageClientWebApi";
         $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
         $postFields = [
             'erDate' => date("d/m/Y h:i:s", strtotime($poDate)),
             'erType' => strtoupper("Purchase Order"),
             'docNo' => $poDoc,
             'storageClientNo' => "SSL0333",
             'remark' => $remarks,
             'supplierNo' => "",
         ];
         $postFields = json_encode($postFields);

         $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);

         $refine = json_decode($response, true);

         if (isset($refine['returnObject'])) {
             echo "ERC:".$refine['returnObject'];
             $sql = "select * from tmp_erc_items_6122018";
             $connection = Yii::$app->db;
             $command = $connection->createCommand($sql);
             $result = $command->queryAll();

             // add ER details
             foreach ($result as $ps) {
                 $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ErDetail/addErDetailForStorageClientWebApi";
                 $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
                 $port = "5191";
                 $postFields = [
                     'dataVersion' => 1,
                     'erNo' => $refine['returnObject'],
                     'storageClientNo' => "SSL0333",
                     'skuDesc' => $ps['sku'],
                     'storageClientSkuNo' => $ps['sku'],
                     'erQty' => $ps['qty'],
                     'recvPrintLabel' => true,
                     'scanSerialNo' => false,
                 ];
                 $postFields = json_encode($postFields);
                 $responsex = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
                 $refinex[$ps['sku']] = json_decode($responsex, true);
             }
         }

         echo "<pre>";
         print_r($refinex);*/
        echo "<pre>";
        print_r(ApiController::createER('273'));
        die();

    }

    public function actionPoArchiveImport()
    {
        if (isset($_POST['_csrf-backend'])) {

            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            /*if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    try{
                        $sku = str_replace('�', '', rtrim($getData[1]));
                        $sku = trim($sku);
                        $isku = (rtrim($getData[2]) != '') ? rtrim($getData[2]) : $sku;
                        $iqty = (int)$getData[3];
                        $Poscode = trim($getData[0]);
                        $erc = $this->getErc($Poscode);
                        $po = new PoImportTemp();
                        $po->sku = $sku;
                        $po->in_bound_sku = $isku;
                        $po->po_number = $Poscode;
                        $po->quantity = $iqty;
                        $po->er_no_1 = $erc;
                        $po->save();
                    } catch(yii\db\Exception $ex)
                    {
                        print_r($getData);
                        $sku = str_replace('�', '', rtrim($getData[3]));
                        echo $sku = rtrim($getData[3]);
                        echo $sku = "<-->".$sku;
                        die();
                    }


                }
            }*/

            /// generating ER QTY
            $po = PoImportTemp::find()->where(['not',['in_bound_sku'=>null]])->andWhere(['is','er_qty',null])->groupBy(["er_no_1"])->asArray()->all();
            foreach($po as $p)
            {
                $erNo = $p['er_no_1'];
                if ($erNo && $erNo != 'not found') {
                    $erDetails = ApiController::fetchER($erNo);

                    if ($erDetails['success'] == '1') {
                        $details = $erDetails['returnObject']['erDetailViewList'];

                        foreach ($details as $d) {
                            $sku = str_replace('�', '', rtrim($d['storageClientSkuNo']));
                            // matching SKU
                            //$erSKU = substr($d['storageClientSkuNo'],0,4);
                            //$sysSKU = substr($p['sku'],0,5);
                            $pods = PoImportTemp::find()->where(['er_no_1'=>$erNo])
                                ->andWhere(['in_bound_sku'=>trim($sku)])
                               // ->andWhere(['is','er_qty',null])
                                ->asArray()->all();
                            foreach($pods as $po)
                            {
                                $obj = PoImportTemp::find()->where(['id'=>$po['id']])->one();
                                $obj->er_qty = $d['recvQty'];
                                $obj->update();

                            }

                        }

                    }
                }
            }
        }



        return $this->render('_ci');

    }

    public function getErc($doc)
    {
        $sql = "SELECT * FROM `temp_po_erc` WHERE document_no LIKE '%$doc%'";

        $connection = Yii::$app->db;

        $command = $connection->createCommand($sql);
        $result = $command->queryOne();


        return $result['erc'];
    }

    public function actionProduct()
    {
        if (isset($_POST['_csrf-backend'])) {

            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;
                $errors = [];

                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    try {
                        $sku = trim($getData[1]);
                        $name = trim($getData[2]);
                        $cost = (double)str_replace(',','',$getData[3]);
                        $rccp = (double)str_replace(',','',$getData[4]);
                        $cost = ($cost == "") ? "0.00" : $cost;
                        $rccp = ($rccp == "") ? "0.00" : $rccp;
                        $catid = (int)$getData[5];
                        $created = ($getData[6] == '\N') ? time() : $getData[6];
                        $fbl = trim($getData[7]);
                        $updated = ($getData[8] == '\N') ? time() : $getData[8];
                        $selling = trim($getData[9]);
                        $stocks = trim($getData[10]);
                        $active = (string)$getData[11];
                        $barcode = ($getData[12] == '\N') ? '0' : (string)$getData[12];
                        $nc = ($getData[13] == '\N') ? '0' : (string)$getData[13];
                        $by = 1;

                        $p = new Products();
                        $p->id = (int)$getData[0];
                        $p->sku = $sku;
                        $p->name = $name;
                        $p->cost = $cost;
                        $p->rccp_cost = $rccp;
                        $p->sub_category = $catid;
                        $p->is_fbl = $fbl;
                        $p->selling_status = $selling;
                        $p->stock_status = $stocks;
                        $p->barcode = $barcode;
                        $p->tnc = $nc;
                        $p->is_active = $active;
                        $p->created_at = $created;
                        $p->updated_at = $updated;
                        $p->created_by = $by;
                        $p->is_orderable = ($cost != "0.00") ? '1' : '0';
                        $p->is_foc = ($catid == '167') ? '1' : '0';
                        $p->refer_id = (int)$getData[0];
                        if(!$p->save())
                        {
                            $errors[] = $p->getErrors();

                        }


                    } catch (yii\db\Exception $ex) {
                        var_dump($getData);
                    }


                }
                echo "<pre>";
                print_r($errors);
            }
        }
        return $this->render('_ci');
    }
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                //return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $comparePassword = User::ValidateLastFourPasswordsAndCompareNewPassword($model->_user->id,$model->password);
            if(!$comparePassword){
                Yii::$app->session->setFlash('success', "<h4 style='color: red;'>You cannot use your last 4 passwords.</h4>");
                return $this->render('resetPassword', ['model' => $model]);
            }
            if($model->resetPassword())
            Yii::$app->session->setFlash('success', 'New password saved.');
            return $this->goHome();
        }

        return $this->render('resetPassword', ['model' => $model]);
    }

}
