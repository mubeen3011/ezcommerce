<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public function __construct(array $config = [])
    {
        $this->ControlJs(\Yii::$app->controller->module->requestedRoute);
        parent::__construct($config);
    }

    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        /*'/theme1/css/minified/aui-production.min.css',
        '/theme1/css/aoa.css',
        '/theme1/themes/minified/fides/color-schemes/dark-blue.min.css',
        '/theme1/themes/minified/fides/common.min.css',
        '/theme1/themes/minified/fides/responsive.min.css',
        '/theme1/amcharts/plugins/export/export.css',
        '/theme1/css/jquery.dataTables.min.css',
        '/theme1/css/fixedHeader.dataTables.min.css',
        '/theme1/css/buttons.dataTables.min.css',
        '/theme1/css/dataTables.bootstrap4.min.css',
        '/theme1/css/colReorder.dataTables.min.css',
        '/theme1/dtp/css/bootstrap-datetimepicker.min.css',*/


        /*'/monster-admin/assets/plugins/bootstrap/css/bootstrap.min.css',
        '/monster-admin/assets/plugins/chartist-js/dist/chartist.min.css',
        '/monster-admin/assets/plugins/chartist-js/dist/chartist-init.css',
        '/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css',
        '/monster-admin/assets/plugins/css-chart/css-chart.css',
        '/monster-admin/assets/plugins/toast-master/css/jquery.toast.css',
        '/monster-admin/css/style.css',
        '/monster-admin/css/colors/blue.css',*/
        // '/monster-admin/css/colors/default-dark.css'
        //'monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.css'


    ];
    public $js;


    public function ControlJs($Route)
    {
        $this->js = [
            //'/monster-admin/assets/plugins/jquery/jquery.min.js',
            '/monster-admin/assets/plugins/bootstrap/js/popper.min.js',
            '/monster-admin/assets/plugins/bootstrap/js/bootstrap.min.js',
            '/monster-admin/js/jquery.slimscroll.js',
            '/monster-admin/js/sidebarmenu.js',
            '/monster-admin/assets/plugins/sticky-kit-master/dist/sticky-kit.min.js',
            '/monster-admin/js/custom.min.js',
            '/monster-admin/js/jasny-bootstrap.js',
            '/iziToast-master/dist/js/iziToast.min.js',
        ];
        $this->js[] = '/monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js';
        $this->js[] = '/monster-admin/js/html-table-to-csv.js';
        if ($Route == 'inventory/channels-inventory-stocks'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao-js/inventory.js';
        }
        if ($Route == 'inventory/warehouses-inventory-stocks'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao-js/inventory.js';
        }
        if ($Route == 'sales/average-sales-by-sku'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao-js/average-sales-sku.js';
        }
        if ($Route == 'inventory/channels-inventory-prices'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao-js/inventory.js';
        }
        if(in_array($Route,['courier/create','courier/update'])){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
        }
        if ($Route=='sales/chart-test'){
            $this->js[] = '/monster-admin/assets/plugins/bootstrap/js/popper.min.js';
            $this->js[] = '/monster-admin/assets/plugins/morrisjs/morris.js';
            $this->js[] = '/monster-admin/assets/plugins/raphael/raphael-min.js';
            $this->js[] = 'ao-js/chart-test.js';
        }
        if ($Route == 'inventory/stock-list'){
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao-js/inventory.js';
        }
        $this->js[] = '/monster-admin/js/aoa.js';
        if ($Route == 'pricing/sales-export') {
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
        }
        if ($Route == 'generic-test-class/all') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'stocks/po') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'stocks/all') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'crawl/create') {
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
        }

        if ($Route == 'stocks/orders') {
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/monster-admin/js/aoa.js';
            $this->js[] = '/ao-js/po.js';
        }
        if ($Route == 'stocks/po-detail') {
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/monster-admin/js/aoa.js';
            $this->js[] = '/ao-js/po.js';
        }
        if ($Route == 'site/calculator') {
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';
            $this->js[] = '/monster-admin/js/calculator.js';
        }
        if ($Route == 'stocks/manage') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';

        }
        if ($Route == 'sales/dashboard') {

            $this->js[] = '/monster-admin/amcharts/pie.js';
            $this->js[] = '/monster-admin/amcharts/plugins/export/export.min.js';
            $this->js[] = '/monster-admin/amcharts/themes/patterns.js';
            $this->js[] = '/monster-admin/js/aoa.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/js/jquery-ui.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/raphael/raphael-min.js';
            $this->js[] = '/monster-admin/assets/plugins/morrisjs/morris.js';
            $this->js[] = '/monster-admin/assets/plugins/echarts/echarts-all.js';
            $this->js[] = '/monster-admin/assets/plugins/echarts/echarts-init.js';
            $this->js[] = '/monster-admin/js/multiple-select.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/ao-js/sales-dashboard.js';
        }
        if ($Route == 'sales/report-by-marketplace') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = '/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js';
            $this->js[] = '/monster-admin/assets/plugins/echarts/echarts-all.js';
            $this->js[] = '/monster-admin/assets/plugins/css-chart/css-chart.css';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap/js/popper.min.js';
            $this->js[] = '/monster-admin/assets/plugins/morrisjs/morris.js';
            $this->js[] = '/monster-admin/assets/plugins/raphael/raphael-min.js';
            $this->js[]='https://www.amcharts.com/lib/4/core.js';
            $this->js[]='https://www.amcharts.com/lib/4/charts.js';
            $this->js[]='https://www.amcharts.com/lib/4/themes/animated.js';
            $this->js[] = '/ao-js/report-by-marketplace.js?v='.time();
        }
        if ($Route == 'sales/report-by-shop') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js';
            $this->js[] = '/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js';
            $this->js[] = '/monster-admin/assets/plugins/echarts/echarts-all.js';
            $this->js[] = '/monster-admin/assets/plugins/css-chart/css-chart.css';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap/js/popper.min.js';
            $this->js[] = '/monster-admin/assets/plugins/morrisjs/morris.js';
            $this->js[] = '/monster-admin/assets/plugins/raphael/raphael-min.js';
            $this->js[]='https://www.amcharts.com/lib/4/core.js';
            $this->js[]='https://www.amcharts.com/lib/4/charts.js';
            $this->js[]='https://www.amcharts.com/lib/4/themes/animated.js';
            $this->js[] = '/ao-js/report-by-shop.js?v='.time();
        }
        if ($Route == 'sales/reporting') {
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
        }
        if ($Route == 'deals-maker') {

            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'deals-maker/dashboard' || $Route == 'deals-maker/dashboard/' || $Route == 'deals-maker/historical-deals' || $Route == 'deals-maker/historical-deals/'
        || $Route == 'deals-maker/sku-performance') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'bundles/generic'){
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
        }
        if ($Route == 'deals-maker/request') {

            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            //$this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            //$this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            //$this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            //$this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            //$this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            //$this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            //$this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/js/bootstrap-datetimepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';
            $this->js[] = '/monster-admin/js/deals-maker-form.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            //$this->js[] = '/monster-admin/js/aoa.js';
        }
        if ($Route == 'deals-maker/detail') {

            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/js/deals-maker-form.js';
            $this->js[] = '/monster-admin/js/aoa.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'https://code.jquery.com/ui/1.11.1/jquery-ui.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
        }
        if ($Route == 'inventory/warehouse-unlisted-skus'){
            $this->js[] = 'ao-js/warehouse-unlisted-skus.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
        }
        if ($Route == 'sales/reporting') {
            /*$this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';*/
            //$this->js[] = '/monster-admin/js/aoa.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'https://code.jquery.com/ui/1.11.1/jquery-ui.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js';
            $this->js[] = '/monster-admin/assets/plugins/switchery/dist/switchery.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js';

        }
        if ($Route == 'sales/item-detail') {
            $this->js[] = '/monster-admin/js/jquery.PrintArea.js';
        }
        if ($Route == 'deals-maker/update') {
            $this->js[] = '/monster-admin/js/bootstrap-datetimepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/footable/js/footable.all.min.js';
            $this->js[] = '/monster-admin/js/deals-maker-form.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'product-360/manage') {
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/js/bootstrap-datetimepicker.js';
            $this->js[] = '/ckeditor/ckeditor.js';
            $this->js[] = '/ao-js/ckeditor/ckfinder.js';

        }
        if ($Route=='finance/audit'){
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = 'https://code.jquery.com/ui/1.11.1/jquery-ui.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[] = '/monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js';
            $this->js[] = '/ao-js/audit.js';
        }
        if ($Route == 'competitive-pricing/crawl-sku-details') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = 'https://code.jquery.com/ui/1.11.1/jquery-ui.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.js';
            $this->js[]='https://www.amcharts.com/lib/4/core.js';
            $this->js[]='https://www.amcharts.com/lib/4/charts.js';
            $this->js[]='https://www.amcharts.com/lib/4/themes/animated.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap/js/popper.min.js';
            $this->js[] = '/monster-admin/assets/plugins/morrisjs/morris.js';
            $this->js[] = '/monster-admin/assets/plugins/raphael/raphael-min.js';
            $this->js[]='ao-js/sku-crawl-charts.js';
        }
        if ($Route == 'sales/finance') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'stocks/dashboard') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
        }

        if ($Route == 'competitive-pricing/create') {
            $this->js[] = '/monster-admin/assets/plugins/moment/moment.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';

        }
        if ($Route == 'sales/finance-validation') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'pricing/index') {
            $this->js[] = 'ao-js/pricing.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
        }
        if ($Route == 'cost-price/generic') {
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/monster-admin/js/toastr.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = 'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/dropify/dist/js/dropify.min.js';
            $this->js[] = '/ao-js/cost-price.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
        }
        if ($Route == 'reports/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'claims/generic') {
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/monster-admin/js/toastr.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = 'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/dropify/dist/js/dropify.min.js';
            $this->js[] = '/ao-js/cost-price.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
        }
        if ($Route == 'deals-maker/category-mapping') {
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/ao-js/category-mapping.js';

        }
        if ($Route == 'user/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'roles/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'settings/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'sellers/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'channels-details/create') {
            $this->js[] = 'ao-js/channels-details.js';
        }
        if ($Route == 'channels-details/generic') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }
        if ($Route == 'subsidy/skus') {
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/js/aoa.js';
        }
        if ($Route == 'stocks/import-philips-stocks') {
            $this->js[] = '/monster-admin/assets/plugins/dropify/dist/js/dropify.min.js';
        }
        if ($Route == 'settings/update') {
            $this->js[] = '/monster-admin/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js';
        }
        if ($Route == 'channels/excluded-skus') {
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js';
        }
        if ($Route == 'stocks/report') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
        }
        if ($Route == 'warehouse/index') {
            $this->js[] = '/monster-admin/assets/plugins/toast-master/js/jquery.toast.js';
            $this->js[] = '/monster-admin/js/toastr.js';
            $this->js[] = 'ao-js/generic-filters.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
            $this->js[] = 'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.js';
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/dropify/dist/js/dropify.min.js';
            $this->js[] = '/ao-js/cost-price.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js';
        }
        if ($Route=='products/child-parent-mapping'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/ao-js/child-parent-mapping.js';
        }
        if ($Route=='products'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
        }
        if ($Route=='products/product-magento-attribute-lists'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = 'ao.js/product-magento-attribute-list.js';
        }
        if ($Route=='stocks/stock-not-managed-by-ezcom'){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
        }
        if ($Route == 'warehouse/create' || $Route == 'warehouse/update' ){
            $this->js[] = '/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js';
            $this->js[] = '/monster-admin/assets/plugins/multiselect/js/jquery.multi-select.js';
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = '/monster-admin/js/comboTreePlugin.js';
            $this->js[] = '/monster-admin/js/icontains.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-clockpicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = 'ao-js/distributor.js';
            $this->js[] = '/ao-js/warehouse.js';

        }
        if ( $Route=='warehouse/view' ){
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-clockpicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asColor.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/libs/jquery-asGradient.js';
            $this->js[] = '/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js';
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
        }
        if ($Route == 'reports/skus-crawl-report') {
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
        }
        if ($Route == 'reports/stock-sync-report') {
            $this->js[] = 'ao-js/reports.js';
            $this->js[] = '/monster-admin/assets/plugins/datatables/jquery.dataTables.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js';
            $this->js[] = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js';
            $this->js[] = 'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js';
            $this->js[] = 'https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js';
            $this->js[] = '/monster-admin/js/jquery.blockUI.js';
        }

        //$this->js[] = '/monster-admin/js/aoa.js';


    }

    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}