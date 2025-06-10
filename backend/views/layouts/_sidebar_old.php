<?php
$roleId = Yii::$app->user->identity->role_id;
$controllerIdss = \common\models\RoleAccess::find()->select('controller_id')->where(['role_id' => $roleId])->asArray()->all();
$user_menu=\common\models\Permissions::find()->where(['role_id'=>$roleId])->asArray()->all();
foreach ($controllerIdss as $v)
    $currentAccess[] = $v['controller_id'];
$UniqueId=Yii::$app->controller->module->requestedRoute;
/*echo '<pre>';
print_r($currentAccess);
die;*/
//die;
?>
<style>
    .user-profile .dropdown-menu{
        margin: 25px auto;
    }
    .sidebar-user-name{
        font-size: 15px;
    }
</style>
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- User profile -->
        <div class="user-profile">
            <!-- User profile image -->
            <div class="profile-img"> <img src="<?=\yii\helpers\Url::to('@web/')?>monster-admin/assets/images/users/1.png" alt="user" /> </div>
            <!-- User profile text-->
            <div class="profile-text">
                <a href="#" class="dropdown-toggle sidebar-user-name link u-dropdown" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true"><?= Yii::$app->user->identity->username ?> <span class="caret"></span></a>
                <div class="dropdown-menu animated flipInY">
                    <div class="dropdown-divider"></div> <a href="<?= \yii\helpers\Url::to(['user/update', 'id' => Yii::$app->user->id])?>" class="dropdown-item"><i class="ti-settings"></i> Edit Profile</a>
                    <div class="dropdown-divider"></div> <a href="javascript:;" class="dropdown-item cs-logout"><i class="fa fa-power-off"></i> Logout</a>
                </div>
            </div>
        </div>
        <!-- End User profile text-->
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li>
                    <a class="" href="/" aria-expanded="false">
                        <i class="mdi mdi-gauge"></i>
                        <span class="hide-menu">
                            Dashboard
                            <!--<span class="label label-rounded label-success">5
                            </span>-->
                        </span>
                    </a>
                </li>
                <li class="<?=(strpos($UniqueId, 'deals-maker') !== false || strpos($UniqueId, 'sales') !== false ||
                    strpos($UniqueId, 'pricing') !== false ) ? 'active' : '' ?>">
                    <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-cash-usd"></i><span class="hide-menu">Sales</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <?php if (in_array('sales', $currentAccess)): ?>
                            <li><a href="/sales/dashboard">Dashboard</a></li>
                        <?php endif; ?>

                        <?php if (in_array('deal-maker', $currentAccess)): ?>
                            <li class="<?=(strpos($UniqueId, 'deals-maker') !== false) ? 'active' : '' ?>">
                                <a class="<?=(strpos($UniqueId, 'deals-maker') !== false) ? 'active' : '' ?>" href="/deals-maker/dashboard">Deals Dashboard</a></li>
                        <?php endif; ?>

                        <?php if (in_array('sales', $currentAccess)): ?>
                            <li class="<?=(strpos($UniqueId, 'sales/reporting') !== false) ? 'active' : '' ?>">
                                <a class="<?=(strpos($UniqueId, 'sales/reporting') !== false) ? 'active' : '' ?>" href="/sales/reporting?view=skus&page=1">Sales Details</a></li>
                        <?php endif; ?>

                    </ul>
                </li>
                <li class="<?=(strpos($UniqueId, 'stocks') !== false ) ? 'active' : '' ?>">
                    <a class="has-arrow " href="#" aria-expanded="false"><i class="mdi mdi-tag"></i><span class="hide-menu">Inventory Management</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="/stocks/dashboard">Dashboard</a></li>
                        <li class="<?=(strpos($UniqueId, 'stocks/orders') !== false) ? 'active' : '' ?><!--" >
                            <a class="<?=(strpos($UniqueId, 'stocks/orders') !== false) ? 'active' : '' ?><!--" href="/stocks/po">Purchase Orders</a>
                        </li>
                        <li><a href="/stocks/add-remove-stocks">Add/Remove Stock</a></li>
                        <li><a href="/inventory/warehouses-inventory-stocks?page=1">Stock List</a></li>
                        <li><a href="/stocks/manage">Thresholds</a></li>
                        <li><a href="/stocks/import-office-stocks">Import Stocks/Prices</a></li>
                    </ul>
                </li>
                <!-- product 360 menu -->
                <?php  if( $roleId==1 || $roleId ==4 || $roleId ==6 || $roleId ==5 ){?>
                    <li class="<?=(strpos($UniqueId, 'product-360') !== false) ? 'active' : '' ?>">
                        <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-rotate-right"></i><span class="hide-menu">Product 360</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li class="<?=(strpos($UniqueId, 'product-360') !== false) ? 'active' : '' ?>">
                                <a href="/product-360/all?page=1" class="<?=(strpos($UniqueId, 'all') !== false) ? 'active' : '' ?>">Product List</a>
                            </li>
                            <li class="<?=(strpos($UniqueId, 'product-360') !== false) ? 'active' : '' ?>">
                                <a href="/product-360/manage" class="<?=(strpos($UniqueId, 'manage') !== false) ? 'active' : '' ?>">Product Management</a>
                            </li>


                        </ul>
                    </li>
                <?php } ?>
                <!-- end of product 360 menu -->
                <li class="<?=(strpos($UniqueId, 'user') !== false ||
                    strpos($UniqueId, 'roles') !== false ||
                    strpos($UniqueId, 'settings') !== false ||
                    strpos($UniqueId, 'cost-price') !== false ||
                    strpos($UniqueId, 'subsidy') !== false ||
                    strpos($UniqueId, 'channels-details') !== false ||
                    strpos($UniqueId, 'channels') !== false) ? 'active' : '' ?>">
                    <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-message-settings-variant"></i><span class="hide-menu">Administrator</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <?php
                        if( in_array('cost-price',$currentAccess) || Yii::$app->user->identity->getId()==27 ){
                            ?>
                            <li class="<?=(strpos($UniqueId, 'cost-price') !== false) ? 'active' : '' ?>">
                                <a href="/cost-price/generic" class="class="<?=(strpos($UniqueId, 'cost-price') !== false) ? 'active' : '' ?>"">Product List</a>
                            </li>
                            <?php
                        }
                        ?>
                        <?php
                        if(  ($roleId==1 || $roleId==6 || $roleId == 4) ){
                            ?>
                            <li class="<?=(strpos($UniqueId, 'channels-details') !== false || strpos($UniqueId, 'channels') !== false) ? 'active' : '' ?>">
                                <a href="/channels-details/generic" class="<?=(strpos($UniqueId, 'channels-details') !== false || strpos($UniqueId, 'channels') !== false) ? 'active' : '' ?>">Shop Settings</a>
                            </li>

                            <li class="<?=(strpos($UniqueId, 'warehouse') !== false || strpos($UniqueId, 'channels') !== false) ? 'active' : '' ?>">
                                <a href="/warehouse/index" class="<?=(strpos($UniqueId, 'warehouse') !== false || strpos($UniqueId, 'warehouse') !== false) ? 'active' : '' ?>">Warehouse Settings</a>
                            </li>
                            <?php
                        }
                        ?>
                        <?php
                        if( $roleId==1 ){
                            ?>
                            <li class="<?=(strpos($UniqueId, 'settings') !== false) ? 'active' : '' ?>">
                                <a href="/settings/generic" class="<?=(strpos($UniqueId, 'settings') !== false) ? 'active' : '' ?>">App Settings</a>
                            </li>
                            <?php
                        }
                        ?>

                        <?php
                        if( $roleId==1 ){
                            ?>
                            <li class="<?=(strpos($UniqueId, 'user') !== false) ? 'active' : '' ?>">
                                <a href="/user/generic" class="<?=(strpos($UniqueId, 'user') !== false) ? 'active' : '' ?>">Users</a>
                            </li>
                            <?php
                        }
                        ?>
                        <?php
                        if( $roleId==1 ){
                            ?>
                            <li class="<?=(strpos($UniqueId, 'roles') !== false) ? 'active' : '' ?>">
                                <a href="/roles/generic" class="<?=(strpos($UniqueId, 'roles') !== false) ? 'active' : '' ?>">User Roles</a>
                            </li>
                            <li class="<?=(strpos($UniqueId, 'roles') !== false) ? 'active' : '' ?>">
                                <a href="/permissions" class="<?=(strpos($UniqueId, 'roles') !== false) ? 'active' : '' ?>">Permissions</a>
                            </li>
                            <?php
                        }
                        ?>


                    </ul>
                </li>
                <li class="nav-devider"></li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
    <!-- Bottom points-->
    <div class="sidebar-footer">
        <!-- item-->
        <!--<a href="" class="link" data-toggle="tooltip" title="Settings"><i class="ti-settings"></i></a>-->
        <!-- item-->
        <!--<a href="" class="link" data-toggle="tooltip" title="Email"><i class="mdi mdi-gmail"></i></a>-->
        <!-- item-->
        <!--<a href="javascript:;" class="link cs-logout" data-toggle="tooltip" title="Logout"><i class="mdi mdi-power"></i></a>-->
    </div>
    <!-- End Bottom points-->
</aside>

<!-- #page-sidebar -->