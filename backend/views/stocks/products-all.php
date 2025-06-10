<?php

use common\models\Settings;

$this->title = 'Channels Products Stocks';
$this->params['breadcrumbs'][] = $this->title;
$settings = Settings::find()->where(['name' => 'last_products_api_update'])->one();
?>
    <style type="text/css">
        a.sort {
            color: #0b93d5;
            text-decoration: underline;
        }

        input.filter {
            text-align: center;
            font-size: 12px !important;
            font-weight: normal !important;
            color: #007fff;

        }
    </style>
    <span style="border-bottom: 1px solid #0a0a0a;">
    Last Synchronize at: <span style="color: #9e0505;font-weight: bold"><?= $settings->value; ?></span>
</span><br>
    <div class="pricing-index stk-tbl" style="margin-top: 10px;">
        <table class="table table-striped table-bordered nowrap">
            <thead>
            <tr>
                <th class="tg-kr94"> <a class="sort" data-field="channel_sku" data-sort="desc" href="javascript:;">SKU</a></th>
                <th class="tg-kr94"><a class="sort" data-field="price" data-sort="desc" href="javascript:;">Price</a></th>
                <th class="tg-kr94"><a class="sort" data-field="blip_lazada_qty" data-sort="desc" href="javascript:;">Lazada Blip</a></th>
                <th class="tg-kr94"><a class="sort" data-field="909_lazada_qty" data-sort="desc" href="javascript:;">Lazada 909</a></th>
                <th class="tg-kr94"><a class="sort" data-field="deal4u_lazada_qty" data-sort="desc" href="javascript:;">Lazada Deal4U</a></th>
                <th class="tg-kr94"><a class="sort" data-field="blip_11Street_qty" data-sort="desc" href="javascript:;">11 Street Blip</a></th>
                <th class="tg-kr94"><a class="sort" data-field="909_11Street_qty" data-sort="desc" href="javascript:;">11 Street 909</a></th>
                <th class="tg-kr94"><a class="sort" data-field="blip_qty" data-sort="desc" href="javascript:;">Blip Shop</a></th>
                <th class="tg-kr94"><a class="sort" data-field="philips_qty" data-sort="desc" href="javascript:;">Philips Shop</a></th>
            </tr>
            <!-- filters -->
            <tr>
                <th class="tg-kr94"> <input type="text" data-filter-field="channel_sku" data-filter-type="like" class="filter form-control "></th>
                <th class="tg-kr94"></th>
                <th class="tg-kr94"><input type="text" data-filter-field="blip_lazada_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="909_lazada_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="deal4u_lazada_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="blip_11Street_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="909_11Street_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="blip_qty" data-filter-type="operator" class="filter form-control "></th>
                <th class="tg-kr94"><input type="text" data-filter-field="philips_qty" data-filter-type="operator" class="filter form-control "></th>
            </tr>

            </thead>

            <tbody class="gridData">

            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        var defaultUrl = '/stocks/products-info';
        var sortUrl = '/stocks/products-info-sort';
        var filterUrl = '/stocks/products-info-filter';
        var pdqs = '0';
    </script>
<?php

$this->registerJsFile(
    '@web/ao-js/table-filters.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]
);
?>