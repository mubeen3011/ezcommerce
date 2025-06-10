<?php
use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

AppAsset::register($this);
$divClass = "";
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title>ezcommerce | <?= Html::encode($this->title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <!-----------localization------->
    <script src="https://global.localizecdn.com/localize.js"></script>
    <script>!function(a){if(!a.Localize){a.Localize={};for(var e=["translate","untranslate","phrase","initialize","translatePage","setLanguage","getLanguage","getSourceLanguage","detectLanguage","getAvailableLanguages","untranslatePage","bootstrap","prefetch","on","off","hideWidget","showWidget"],t=0;t<e.length;t++)a.Localize[e[t]]=function(){}}}(window);</script>

    <script>
        Localize.initialize({
            key: 'cU0l7Dph58WcX',
            rememberLanguage: true
        });
    </script>

    <!-----------localization------->
    <!-- Favicons -->

    <link rel="apple-touch-icon" sizes="57x57" href="/monster-admin/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/monster-admin/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/monster-admin/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/monster-admin/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/monster-admin/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/monster-admin/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/monster-admin/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/monster-admin/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/monster-admin/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/monster-admin/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/monster-admin/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/monster-admin/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/monster-admin/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/monster-admin/images/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/monster-admin/images/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="/../monster-admin/assets/images/favicon-ecommerce.png">
    <title>Monster Admin Template - The Most Complete & Trusted Bootstrap 4 Admin Template</title>
    <!-- Bootstrap Core CSS -->

    <!--<link href="/../monster-admin/assets/plugins/morrisjs/morris.css" rel="stylesheet">-->
    <link href="/../monster-admin/assets/plugins/jquery-asColorPicker-master/css/asColorPicker.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/morrisjs/morris.css" rel="stylesheet">


    <link href="/../monster-admin/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="/../monster-admin/css/multiple-select.css" rel="stylesheet">
    <link href="/theme1/dtp/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <!-- chartist CSS -->
    <link href="/../monster-admin/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/switchery/dist/switchery.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/chartist-js/dist/chartist.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/chartist-js/dist/chartist-init.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/css-chart/css-chart.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/jsgrid/jsgrid.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/jsgrid/jsgrid-theme.min.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/html5-editor/bootstrap-wysihtml5.css" rel="stylesheet">

    <link href="/../css/aoa.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/dropzone-master/dist/dropzone.css" rel="stylesheet">
    <!-- toast CSS -->
    <link href="../monster-admin/assets/plugins/dropify/dist/css/dropify.min.css">
    <link href="/../monster-admin/assets/plugins/toast-master/css/jquery.toast.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/../monster-admin/css/style.css" rel="stylesheet">
    <!-- You can change the theme colors from here -->
    <link href="/../monster-admin/css/colors/blue.css" id="theme" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/footable/css/footable.core.css" id="theme" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css" rel="stylesheet"/>
    <link href="/../monster-admin/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/multiselect/css/multi-select.css" rel="stylesheet">
    <link href="/../monster-admin/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css" rel="stylesheet">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">


    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <link href="/../monster-admin/css/bootstrap-datetimepicker.min.css" rel="stylesheet">

    <![endif]-->
    <style>
        table.dataTable thead .sorting:after {
            margin-left: 0px !important;
        }
        .thead-border{
            border: 1px solid #dee2e6 !important;
        }
        .side-border{
            border: 1px solid #dee2e6 !important;
            border-top: 0px !important;

        }
    </style>
    <?php
    if ($_SERVER['HTTP_HOST']=='philips.ezcommerce.io'){
        ?>
        <script type="text/javascript" src="//laz-g-cdn.alicdn.com/sj/securesdk/0.0.3/securesdk_lzd_v1.js" id="J_secure_sdk_v2" data-appkey="102471"></script>
        <?php
    }
    ?>
</head>
<body class="fix-header fix-sidebar card-no-border">
<?php $this->beginBody() ?>
<div class="preloader">
    <svg class="circular" viewBox="25 25 50 50">
        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" /> </svg>
</div>
<!--<div id="loading" class="ui-front loader ui-widget-overlay bg-white opacity-100">
    <img src="/monster-admin/images/loader-dark.gif" alt="" />
</div>-->
<?php
if( !Yii::$app->user->isGuest ){
?>
    <div id="main-wrapper" class="demo-example">

        <?= $this->render('_header'); ?>
        <?php if (!Yii::$app->user->isGuest):
            $divClass = "page-content-wrapper";
            ?>
            <?= $this->render('_sidebar'); ?>
        <?php endif; ?>
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row page-titles">
                    <div class="col-md-12 col-8 align-self-center">
                        <h3 class="text-themecolor m-b-0 m-t-0"><?= $this->title ?></h3>


                    </div>


                </div>
                <?= $content ?>
            </div>
        </div>
    </div><!-- #page-wrapper -->

    <?php
}else{
    ?>
    <?=$content?>
<?php
}
?>

<?php $this->endBody() ?>
<?php
if( !Yii::$app->user->isGuest ){
    ?>
    <footer class="footer">
        © <?=date('Y');?> Ecommerce Admin by Axle & Olio
    </footer>
<?php
}
?>

<!-- ============================================================== -->
<!-- End footer -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Page wrapper  -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Wrapper -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- All Jquery -->
<!-- ============================================================== -->

<!-- slimscrollbar scrollbar JavaScript -->
<!--Wave Effects -->

<!--Menu sidebar -->
<!--stickey kit -->

<!--Custom JavaScript -->

<!-- ============================================================== -->
<!-- This page plugins -->
<!-- ============================================================== -->
<!-- chartist chart -->

<!-- Chart JS -->

<!-- ============================================================== -->
<!-- Style switcher -->
<!-- ============================================================== -->
<script>
    $('.cs-logout').on('click', function () {
        $("#lg").submit();
    });

    <?php if(Yii::$app->session->hasFlash('success')) { ?>
    var msg='<?php echo Yii::$app->session->getFlash('success'); ?>';
    var type='success';
    display_notice(type, msg);
    <?php } ?>
    <?php if(Yii::$app->session->hasFlash('failure')) { ?>
    var msg='<?php echo Yii::$app->session->getFlash('failure'); ?>';
    var type='failure';
    display_notice(type, msg);
    <?php } ?>


    function display_notice(type,msg,keep=false)
    {
        iziToast.destroy();

        iziToast.show({
            message: msg,
            messageColor:'white',
            color: type=="success" ?  '#2A8947' :type=="info" ? '#3C8DBC':'#d73925',
            icon:type=="success" ? 'fa fa-check':type=="info" ? 'fa fa-spinner fa-spin':'fa fa-warning',
            position: 'bottomRight',
            iconColor: 'white',
            timeout:keep ? false:4000,
            transitionIn:'bounceInUp'
        });
    }
</script>
<form id="lg" action="/site/logout" method="post">
    <input id="form-token" type="hidden" name="_csrf-backend" value="<?=Yii::$app->request->csrfToken?>"/>
</form>

</body>
</html>
<link href="/../iziToast-master/dist/css/iziToast.min.css" rel="stylesheet">
<?php $this->endPage() ?>

