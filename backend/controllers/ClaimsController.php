<?php

namespace backend\controllers;

use common\models\Category;
use common\models\Channels;
use common\models\ProductDetails;
use common\models\Products;
use common\models\Subsidy;
use Yii;
use common\models\CostPrice;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * CostPriceController implements the CRUD actions for CostPrice model.
 */
class ClaimsController extends GenericGridController
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

    /**
     * Lists all CostPrice models.
     * @return mixed
     */
    public function actionGeneric(){
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/claims/generic-info',
                    'sortUrl' => '/claims/generic-info-sort',
                    'filterUrl' => '/claims/generic-info-filter',
                    'jsUrl'=>'/claims/generic',
                ],
                'thead'=>
                    [
                        'Order Id' => [
                            'data-field' => 'ci.order_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.order_id',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        /*
                         * below commented code is so important, if any one wants to open the bundle parent and child, just remove the comments and
                         * also remove the # sign from the configparam query.
                         * */

                        /*'Bundle Name' => [
                            'data-field' => 'bundle_name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'bundle_name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Bundle Child' => [
                            'data-field' => 'bundle_name_child',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'bundle_name_child',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                        ,*/
                        'Sku' => [
                            'data-field' => 'ci.sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.sku',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Order Status' => [
                            'data-field' => 'ci.order_status',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.order_status',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Reason' => [
                            'data-field' => 'ci.reason',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.reason',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Shop' => [
                            'data-field' => 'c.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Order Last Updated' => [
                            'data-field' => 'ci.order_item_updated_at',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.order_item_updated_at',
                            'label' => 'show',
                            'data-filter-type' => 'text',
                            'input-type' => 'hidden',
                            'input-type-class' => '',
                        ],
                        'Claim Category' => [
                            'data-field' => 'ci.claim_category',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ci.claim_category',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => '',
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
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku,'config'=>$config]);

        return $this->render('generic-view',['gridview'=>$html]);
    }
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT ci.order_id,ci.sku,ci.order_status,ci.reason,c.name,ci.order_item_updated_at,ci.claim_category FROM claim_items ci 
INNER JOIN channels c ON 
c.id = ci.channel_id
where 1=1
",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY ci.order_item_updated_at DESC',
            'SortOrderByColumnAlias' => 'ci',
        ];
        return $config;
    }




}
