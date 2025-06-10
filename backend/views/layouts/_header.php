<?php


use yii\helpers\Url;
$currentAccess = [];

?>
<style>
    .help-block{
        color: red;
    }
    html body .m-t-40 {
        margin-top: 0px;
    }
    .page-titles .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        content: "\e649";
        font-family: themify;
        color: #a6b7bf;
        font-size: 10px;
    }
</style>
<header class="topbar">
    <nav class="navbar top-navbar navbar-expand-md navbar-light">
        <!-- ============================================================== -->
        <!-- Logo -->
        <!-- ============================================================== -->
        <div class="navbar-header">
            <a class="navbar-brand" href="/">
                <!-- Logo icon -->
                <b>
                    <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                    <!-- Dark Logo icon -->
                    <img src="<?=Url::to('@web/')?>monster-admin/assets/images/ecommerce-logo-icon.png" alt="homepage" class="dark-logo" />
                    <!-- Light Logo icon -->
                    <img src="<?=Url::to('@web/')?>monster-admin/assets/images/logo-light-icon.png" alt="homepage" class="light-logo" />
                </b>
                <!--End Logo icon -->
                <!-- Logo text -->
                <span>
                         <!-- dark Logo text -->
                         <img src="<?=Url::to('@web/')?>monster-admin/assets/images/logo-ecommerce-text.png" alt="homepage" class="dark-logo" />
                    <!-- Light Logo text -->
                         <img src="<?=Url::to('@web/')?>monster-admin/assets/images/logo-ecommercrce-light-text.png" class="light-logo" alt="homepage" /></span> </a>
        </div>
        <!-- ============================================================== -->
        <!-- End Logo -->
        <!-- ============================================================== -->
        <div class="navbar-collapse">
            <!-- ============================================================== -->
            <!-- toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav mr-auto mt-md-0 ">
                <!-- This is  -->
                <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted waves-effect waves-dark" href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                <li class="nav-item"> <a class="nav-link sidebartoggler hidden-sm-down text-muted waves-effect waves-dark" href="javascript:void(0)"><i class="icon-arrow-left-circle"></i></a> </li>
                <!-- ============================================================== -->
                <!-- Comment -->
                <!-- ============================================================== -->

                <!-- ============================================================== -->
                <!-- End Comment -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Messages -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- End Messages -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Messages -->
                <!-- ============================================================== -->
                <?php
                $notif = \common\models\Notifications::find()->where(['to_user_id' => Yii::$app->user->id, 'is_read' => '0'])->all();
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" id="2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-email"></i>

                        <?php
                        if( !empty($notif) ){
                            ?>
                            <div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
                        <?php
                        }
                        ?>

                    </a>
                    <div class="dropdown-menu mailbox animated bounceInDown" aria-labelledby="2">
                        <ul>
                            <?php
                            $notif = \common\models\Notifications::find()->where(['to_user_id' => Yii::$app->user->id, 'is_read' => '0'])->all();
                            ?>
                            <li>
                                <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 250px;">
                                    <div class="message-center" style="overflow: hidden; width: auto; height: 250px;">
                                        <!-- Message -->
                                        <?php foreach ($notif as $n): ?>

                                                <div class="mail-contnet">
                                                    <span class="mail-desc">
                                                        <?= $n->message ?>
                                                        <p><?= date('h:i a | d M') ?></p>
                                                    </span>

                                                </div>

                            <?php endforeach; ?>
                                    </div>

                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- ============================================================== -->
                <!-- End Messages -->
                <!-- ============================================================== -->
            </ul>
            <!-- ============================================================== -->
            <!-- User profile and search -->
            <!-- ============================================================== -->
            <ul class="navbar-nav my-lg-0">
                <!--<li class="nav-item hidden-sm-down">
                    <form class="app-search">
                        <input type="text" class="form-control" placeholder="Search for..."> <a class="srh-btn"><i class="ti-search"></i></a> </form>
                </li>-->
                <?php
                if( isset(Yii::$app->user->identity->full_name) ) {
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href=""
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Create"> <i
                                    class="fa fa-plus"></i>
                        </a>
                        <div class="dropdown-menu  dropdown-menu-right animated bounceInDown">
                            <?php
                            $userid=Yii::$app->user->identity;
                            ?>
                            <?php
                            if($userid['role_id'] == 8) {
                                ?>
                                <a class="dropdown-item" href="/stocks/create-po"><i class="glyph-icon icon-plus"></i>
                                    Create PO</a>
                                <?php
                            }
                            ?>
                            <?php
                            if($userid['role_id'] != 8) {
                                ?>
                                <?php
                                $userRole = \backend\util\HelpUtil::GetRole();
                                ?>
                                <?php
                                if ($userRole != 'super') {
                                    ?>
                                    <a class="dropdown-item" href="/deals-maker/request"><i
                                                class="glyph-icon icon-plus"></i>Create Deal</a>
                                    <?php
                                }
                                ?>
                                <?php
                            }
                            ?>

                            <!--<a class="dropdown-item" href="/crawl/create"><i class="glyph-icon icon-plus"></i> Add Sku
                                For Crawl</a>-->
                            <?php
                            if($userid['role_id'] != 8) {
                                ?>
                                <a class="dropdown-item" href="/stocks/create-po"><i class="glyph-icon icon-plus"></i>
                                    Create PO</a>
                                <a class="dropdown-item" href="/user/create"><i class="glyph-icon icon-user"></i> Create
                                    User</a>
                                <a class="dropdown-item" href="/channels/create"><i class="glyph-icon icon-plus"></i>
                                    Creat shop</a>
                                <?php
                            }
                            ?>
                            <!--<a class="dropdown-item" href="/roles/create"><i class="glyph-icon icon-user"></i> Create User Access Role</a>-->
                            <!--<a class="dropdown-item" href="/channels-details/create"><i class="glyph-icon icon-plus"></i> Create Shop Details</a>-->
                        </div>
                    </li>
                    <?php
                }
                ?>
                <?php
                if( isset(Yii::$app->user->identity->full_name) ){
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="<?=Url::to('@web/')?>monster-admin/assets/images/users/1.png" alt="user" class="profile-pic" /></a>
                        <div class="dropdown-menu dropdown-menu-right animated flipInY">
                            <ul class="dropdown-user">
                                <li>
                                    <div class="dw-user-box">
                                        <div class="u-img"><img src="<?=Url::to('@web/')?>monster-admin/assets/images/users/1.png" alt="user"></div>
                                        <div class="u-text">
                                            <h4><?= isset(Yii::$app->user->identity->full_name) ? Yii::$app->user->identity->full_name : ''  ?></h4>
                                            <p class="text-muted"><?= isset(Yii::$app->user->identity->username) ? Yii::$app->user->identity->username : '' ?></p><a href="<?= Url::to(['user/view', 'id' => Yii::$app->user->id])?>" class="btn btn-rounded btn-danger btn-sm">View Profile</a></div>
                                    </div>
                                </li>
                                <li role="separator" class="divider"></li>
                                <li><a href="<?= Url::to(['user/update', 'id' => Yii::$app->user->id])?>"><i class="ti-pencil"></i> Edit Profile</a></li>
                                <!--<li><a href="#"><i class="ti-wallet"></i> My Balance</a></li>
                                <li><a href="#"><i class="ti-email"></i> Inbox</a></li>
                                <li role="separator" class="divider"></li>
                                <li><a href="#"><i class="ti-settings"></i> Account Setting</a></li>-->
                                <li role="separator" class="divider"></li>
                                <li class="cs-logout"><a href="javascript:;"><i class="fa fa-power-off "></i> Logout</a></li>

                            </ul>
                        </div>
                    </li>
                <?php
                }
                ?>


            </ul>
        </div>
    </nav>
</header>