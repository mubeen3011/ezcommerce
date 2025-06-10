<div class="row">

    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= (!isset($_GET['product_status']) && Yii::$app->controller->action->id=='product-sync-to-warehouse') ? 'active':"";?>"  href="/products/product-sync-to-warehouse" role="tab">

                    <span><span class="badge badge-secondary"></span> All Assigned</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['product_status']) && $_GET['product_status']=='synced') ? 'active':"";?>"  href="/products/product-sync-to-warehouse?product_status=synced" role="tab">

                    <span><span class="badge badge-secondary"></span> Products synced </span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['product_status']) && $_GET['product_status']=='pending') ? 'active':"";?>"  href="/products/product-sync-to-warehouse?product_status=pending" role="tab">

                    <span><span class="badge badge-secondary"></span> Pending sync </span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['product_status']) && $_GET['product_status']=='failed') ? 'active':"";?>"  href="/products/product-sync-to-warehouse?product_status=failed" role="tab">

                    <span><span class="badge badge-danger"><?= (isset($failed_sync_count) && $failed_sync_count)? $failed_sync_count:""; ?></span> Failed sync </span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= Yii::$app->controller->action->id=='products-not-assigned-to-warehouse' ? 'active':"";?>"  href="/products/products-not-assigned-to-warehouse" role="tab">
                    <span class="badge badge-secondary"></span> Not assigned to sync
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= Yii::$app->controller->action->id=='duplicate-assigned-warehouse-products' ? 'active':"";?>"  href="/products/duplicate-assigned-warehouse-products" role="tab">
                    <span class="badge badge-secondary"></span> Duplicate assignment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= Yii::$app->controller->action->id=='csv-upload-product-warehouse-sync' ? 'active':"";?>"  href="/products/csv-upload-product-warehouse-sync" role="tab">
                    <span class="badge badge-secondary"></span> CSV Upload
                </a>
            </li>


        </ul>


    </div>

</div>