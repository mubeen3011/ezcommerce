<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/21/2018
 * Time: 3:39 PM
 */
namespace backend\controllers;
class GenericTestClassController extends GenericGridController {
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>'select tcr.ID,tcr.product_id,tcr.product_name,tcr.price,tcr.seller_name from temp_crawl_results tcr
where tcr.ID is not null',
                'GroupBy' => 'group by tcr.product_id'
            ],
            'OrderBy_Default'=>'ORDER BY tcr.ID ASC',
            'SortOrderByColumnAlias' => 'tcr',
        ];
        return $config;
    }
    public function actionAll()
    {
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/generic-test-class/generic-info',
                    'sortUrl' => '/generic-test-class/generic-info-sort',
                    'filterUrl' => '/generic-test-class/generic-info-filter',
                    'jsUrl'=>'/generic-test-class/all',
                ],
                'thead'=>
                    [
                        'ID' => [
                            'data-field' => 'ID',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ID',
                            'data-filter-type' => 'operator'
                        ],
                        'Product Id' => [
                            'data-field' => 'product_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'product_id',
                            'data-filter-type' => 'like'
                        ],
                        'Product Name' => [
                            'data-field' => 'product_name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'product_name',
                            'data-filter-type' => 'like'
                        ],
                        'Price' => [
                            'data-field' => 'price',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'price',
                            'data-filter-type' => 'operator'
                        ],
                        'Seller Name' => [
                            'data-field' => 'seller_name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'seller_name',
                            'data-filter-type' => 'like'
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
        return $this->render('index',['gridview'=>$html]);
        //echo $html;
    }
}