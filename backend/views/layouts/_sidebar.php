<?php
$UniqueId=Yii::$app->controller->module->requestedRoute;

//$user_menu=\backend\util\HelpUtil::getSidebarMenu();
$user_menu=Yii::$app->permissionCheck->getSidebarMenu();
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
       <?php if($user_menu):
         foreach ($user_menu as $main_menu):
             if(!$main_menu['view'])  // if view permission not allowed
                 continue;
             ?>
             <li>
                 <a class="<?= isset($main_menu['children']) ? 'has-arrow':'';?>" href="<?= $main_menu['controller_id'] ? $main_menu['controller_id'].$main_menu['view_id'].$main_menu['params']:'#';?>" aria-expanded="false">
                        <i class="<?= $main_menu['icon'];?>"></i>
                        <span class="hide-menu"><?= $main_menu['name'];?> </span>
                 </a>
                     <!---- if have child as well>--->
                 <?php
                 if(isset($main_menu['children']) && is_array($main_menu['children'])) : ?>
                     <ul aria-expanded="false" class="collapse">
                   <?php  foreach ($main_menu['children'] as $menu) {
                       $realTimeData = '';
                       if ( $menu['controller_id']=='/inventory' && $menu['view_id']=='/warehouses-inventory-stocks' )
                           $realTimeData .= '&time='.time();
                       else
                           $realTimeData .= '';
                       if($menu['view']) { ?>
                            <li>
                                <a class="<?= isset($menu['children']) ? 'has-arrow':'';?>" href="<?= $menu['controller_id'] ? $menu['controller_id'].$menu['view_id'].$menu['params'].$realTimeData:'#';?>" aria-expanded="false">
                                    <i class="<?= $menu['icon'];?>"></i>
                                    <span class="hide-menu"><?= $menu['name'];?> </span>
                                </a>

                                <!---------if 3rd level child---------->
                                <?php
                                if(isset($menu['children']) && is_array($menu['children'])) : ?>
                                    <ul aria-expanded="false" class="collapse">
                                        <?php  foreach ($menu['children'] as $cmenu) {
                                            if($cmenu['view']) { ?>
                                                <li>
                                                    <a  href="<?=$cmenu['controller_id'].$cmenu['view_id'].$cmenu['params'];?>" >
                                                        <?= $cmenu['name'];?>
                                                    </a>
                                                </li>
                                            <?php  }} ?>
                                    </ul>
                                <?php endif;  ?>
                                <!---------if 3rd level child---------->
                            </li>
                     <?php  }} ?>
                     </ul>
                 <?php endif;  ?>
             </li>
        <?php endforeach;
              endif;?>
    </ul>
</nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
    <!-- Bottom points-->
    <div class="sidebar-footer">

    </div>
    <!-- End Bottom points-->
</aside>

<!-- #page-sidebar -->