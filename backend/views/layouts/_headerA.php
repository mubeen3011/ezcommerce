<?php

use yii\helpers\Url;

$currentAccess = [];

?>
<div id="page-header" class="clearfix" style="height: 70px !important;">
    <a href="<?= Url::to(['/']) ?>">
        <div id="header-logo" style="background-color: #343942">
            <a href="javascript:;" class="tooltip-button" data-placement="bottom" title="" id="close-sidebar" data-original-title="Close sidebar">
                <i class="glyph-icon icon-caret-left"></i>
            </a>
            <a href="javascript:;" class="tooltip-button hidden" data-placement="bottom" title="" id="rm-close-sidebar" data-original-title="Open sidebar">
                <i class="glyph-icon icon-caret-right"></i>
            </a>
            <img src="/theme1/images/aoa_tool_logo_B.png" style="width: 100%;">
        </div>
    </a>
    <?php if (!Yii::$app->user->isGuest):

        $roleId = Yii::$app->user->identity->role_id;
        $controllerIdss = \common\models\RoleAccess::find()->select('controller_id')->where(['role_id' => $roleId])->asArray()->all();

        foreach ($controllerIdss as $v)
            $currentAccess[] = $v['controller_id'];
        ?>
        <div class="user-profile dropdown">
            <a href="javascript:;" title="" class="user-ico clearfix" data-toggle="dropdown">
                <span><?= Yii::$app->user->identity->full_name ?></span>
                <i class="glyph-icon icon-chevron-down"></i>
            </a>
            <ul class="dropdown-menu float-right">

                <li>
                    <a href="<?= Url::to(['user/update', 'id' => Yii::$app->user->id]) ?>" title="">
                        <i class="glyph-icon icon-cog mrg5R"></i>
                        Edit Profile
                    </a>
                </li>

                <li>
                    <a href="javascript:;" class="cs-logout" title="logout">
                        <i class="glyph-icon icon-signout font-size-13 mrg5R"></i>
                        <span class="font-bold">Logout</span>
                        <form id="lg" action="<?= Url::to(['/site/logout']) ?>" method="post">
                            <input id="form-token" type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                   value="<?= Yii::$app->request->csrfToken ?>"/>
                        </form>
                    </a>
                </li>
                <li class="divider"></li>


            </ul>
        </div>
        <?php if (Yii::$app->user->identity->role_id != 3): ?>
        <div class="dropdown dash-menu">
            <a href="javascript:;" data-toggle="dropdown" data-placement="left"
               class="medium btn primary-bg float-right popover-button-header hidden-mobile tooltip-button"
               title="operations menu">
                <i class="glyph-icon icon-th"></i>
            </a>
            <div class="dropdown-menu float-right">
                <div class="small-box">
                    <div class="pad10A dashboard-buttons clearfix">
                        <p class="font-gray-dark font-size-11 pad0B">App Menu</p>
                        <?php if (in_array('user', $currentAccess)): ?>
                            <a href="<?= Url::to(['user/index']) ?>"
                               class="btn vertical-button remove-border bg-blue"
                               title="Users Management">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-user opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>

                            <a href="<?= Url::to(['roles/index']) ?>"
                               class="btn vertical-button remove-border bg-blue"
                               title="Users Access Management">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-key opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('cost-price', $currentAccess)): ?>
                            <a href="<?= Url::to(['cost-price/index']) ?>"
                               class="btn vertical-button remove-border bg-purple" title="Price List">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-money opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Price List</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('channel-details', $currentAccess)): ?>
                            <a href="<?= Url::to(['channels-details/index']) ?>"
                               class="btn vertical-button remove-border bg-azure" title="Channel Details">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-shopping-cart opacity-80 font-size-20"></i>
                                    </span>
                                <!-- <span class="button-content">Channel Details</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('subsidy', $currentAccess)): ?>
                            <a href="<?= Url::to(['subsidy/skus']) ?>"
                               class="btn vertical-button remove-border bg-yellow"
                               title="SKU Subsidy/Margins">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-save opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">SKU Subsidy/Margins</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('sku-margin-settings', $currentAccess)): ?>
                            <a href="<?= Url::to(['sku-margin-settings/index']) ?>"
                               class="btn vertical-button remove-border bg-azure"
                               title="sku margin settings">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-list opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Sales Pricing Sheet</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('sellers', $currentAccess)): ?>
                            <a href="<?= Url::to(['sellers/index']) ?>"
                               class="btn vertical-button remove-border bg-azure"
                               title="Channel Sellers">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-check-sign opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Sales Pricing Sheet</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('site', $currentAccess)): ?>
                            <a href="<?= Url::to(['site/calculator']) ?>"
                               class="btn vertical-button remove-border bg-green"
                               title="SKU Price Calculator">

                                <span class="button-content">SKU Price Calculator</span>
                            </a>
                        <?php endif ?>
                        <?php if (in_array('competitive-pricing', $currentAccess)): ?>
                            <a href="<?= Url::to(['competitive-pricing/create']) ?>"
                               class="btn vertical-button remove-border bg-orange" title="Competitive Pricing">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-bar-chart opacity-80 font-size-20"></i>
                                    </span>
                                <!-- <span class="button-content">Competitive Pricing</span>-->
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('pricing', $currentAccess)): ?>
                            <a href="<?= Url::to(['pricing/index?show=all']) ?>"
                               class="btn vertical-button remove-border bg-azure"
                               title="Sales Pricing Sheet">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <i class="glyph-icon icon-bar-chart opacity-80 font-size-20"></i>
                                    </span>
                                <!--<span class="button-content">Sales Pricing Sheet</span>-->
                            </a>
                        <?php endif; ?>

                        <?php if (in_array('deal-maker', $currentAccess)): ?>
                            <a href="<?= Url::to(['/deals-maker']) ?>"
                               class="btn vertical-button remove-border bg-azure"
                               title="Deal Maker">
                                <img src="/theme1/images/icons/deal.png" style="width: 25px;">
                                <!--<span class="button-content">Sales Pricing Sheet</span>-->
                            </a>
                        <?php endif; ?>

                        <?php if (in_array('settings', $currentAccess)): ?>
                            <a href="<?= Url::to(['/settings']) ?>"
                               class="btn vertical-button remove-border bg-azure"
                               title="APP Settings">
                                <img src="/theme1/images/icons/settings.png" style="width: 25px;">
                                <!--<span class="button-content">Sales Pricing Sheet</span>-->
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php
                    $controllerIds = ['pricing', 'subsidy', 'competitive-pricing', 'cost-price'];
                    if (in_array(Yii::$app->controller->id, $controllerIds)): ?>
                        <div class="bg-gray text-transform-upr font-size-12 font-bold font-gray-dark pad10A">Screen
                            menu
                        </div>
                        <div class="pad10A  clearfix" style="overflow-y: scroll;height: 300px;">
                            <?php echo $this->render('_' . Yii::$app->controller->id . '-settings'); ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>

        </div>
        <?php if (in_array('stocks', $currentAccess)): ?>
            <div class="dropdown dash-menu" style="margin-right: 30px;">
                <a href="javascript:;" data-toggle="dropdown" data-placement="left"
                   class="medium btn success-bg float-right popover-button-header hidden-mobile tooltip-button"
                   title="Stock Management Menu">
                    <img src="/theme1/images/icons/product-stock.png" style="width: 25px;">
                </a>
                <div class="dropdown-menu float-right">
                    <div class="small-box">
                        <div class="pad10A dashboard-buttons clearfix">
                            <a href="<?= Url::to(['stocks/all?pdqs=0']) ?>"
                               class="btn vertical-button remove-border bg-white"
                               title="Stocks Sync.">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <img src="/theme1/images/icons/product-stock-isis.png" style="width: 35px;">
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>
                            <a href="<?= Url::to(['stocks/products']) ?>"
                               class="btn vertical-button remove-border bg-white"
                               title="Products Sync.">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <img src="/theme1/images/icons/products.png" style="width: 35px;">
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>
                            <a href="<?= Url::to(['stocks/manage']) ?>"
                               class="btn vertical-button remove-border bg-white"
                               title="Manage Stocks">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <img src="/theme1/images/icons/stock-manage.png" style="width: 35px;">
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>
                            <a href="<?= Url::to(['stocks/po']) ?>" class="btn vertical-button remove-border bg-white"
                               title="Purchase Orders">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <img src="/theme1/images/icons/po.png" style="width: 35px;">
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>

                        </div>
                        <?php
                        $controllerIds = ['stocks'];
                        if (in_array(Yii::$app->controller->id, $controllerIds)): ?>
                            <div class="bg-gray text-transform-upr font-size-12 font-bold font-gray-dark pad10A">Screen
                                menu
                            </div>
                            <div class="pad10A  clearfix" style="overflow-y: scroll;height: 300px;">
                                <?php echo $this->render('_' . Yii::$app->controller->id . '-settings'); ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>

        <?php if (in_array('sales', $currentAccess)): ?>
            <div class="dropdown dash-menu" style="margin-right: 30px;">
                <a href="javascript:;" data-toggle="dropdown" data-placement="left"
                   class="medium btn notice-bg float-right popover-button-header hidden-mobile tooltip-button"
                   title="Sales Report Menu">
                    <img src="/theme1/images/icons/sales.png" style="width: 25px;">
                </a>
                <div class="dropdown-menu float-right">
                    <div class="small-box">
                        <div class="pad10A dashboard-buttons clearfix">
                            <a href="<?= Url::to(['sales/reporting?view=skus']) ?>"
                               class="btn vertical-button remove-border bg-white"
                               title="Sales Reproting (SKUS)">
                                    <span class="glyph-icon icon-separator-vertical pad0A medium">
                                        <img src="/theme1/images/icons/sales.png" style="width: 35px;">
                                    </span>
                                <!--<span class="button-content">Users Management</span>-->
                            </a>


                        </div>

                    </div>
                </div>

            </div>
        <?php endif; ?>

    <?php endif; ?>
    <div class="top-icon-bar">
    <div class="dropdown">
        <?php
        $notif = \common\models\Notifications::find()->where(['to_user_id'=>Yii::$app->user->id,'is_read'=>'0'])->all();
        ?>
        <a data-toggle="dropdown" href="javascript:;" title="">
            <span class="badge badge-absolute bg-orange"><?=count($notif)?></span>
            <i class="glyph-icon icon-envelope-alt"></i>
        </a>
        <div class="dropdown-menu">

            <div class="scrollable-content medium-box scrollable-small" style="overflow: hidden;" tabindex="5000">

                <ul class="no-border messages-box">
                    <?php foreach ($notif as $n): ?>
                    <li>
                        <div class="messages-content" style="margin-left: 0px !important;">
                            <div class="messages-title">
                                <i class="glyph-icon icon-warning-sign font-red"></i>
                                <div class="messages-time">
                                    <?= date('h:i a | d M,Y') ?>
                                    <span class="glyph-icon icon-time"></span>
                                </div>
                            </div>
                            <div class="messages-text">
                                <?= $n->message ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>

            </div>
            <div id="ascrail2000" class="nicescroll-rails" style="width: 8px; z-index: 1050; cursor: default; position: absolute; top: 0px; left: 467px; height: 200px; display: none;"><div style="position: relative; top: 0px; float: right; width: 4px; height: 0px; background-color: rgb(54, 54, 54); border: 2px solid transparent; background-clip: padding-box; border-radius: 2px;"></div></div><div id="ascrail2000-hr" class="nicescroll-rails" style="height: 8px; z-index: 1050; top: 192px; left: 0px; position: absolute; cursor: default; display: none;"><div style="position: relative; top: 0px; height: 4px; width: 0px; background-color: rgb(54, 54, 54); border: 2px solid transparent; background-clip: padding-box; border-radius: 2px;"></div></div></div>
    </div>
    </div>

    <?php endif; ?>

</div><!-- #page-header -->