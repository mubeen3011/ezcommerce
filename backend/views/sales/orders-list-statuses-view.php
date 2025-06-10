<div class="row">

    <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= (!isset($_GET['order_status'])) ? 'active':"";?>"  href="/sales/reporting" role="tab">

                    <span><span class="badge badge-secondary"><?= isset($order_counts) ? array_sum($order_counts):"X"; ?></span> All </span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='pending') ? 'active':"";?>"  href="/sales/reporting?order_status=pending" role="tab">
                    <span class="badge badge-secondary"><?= isset($order_counts['pending']) ? $order_counts['pending']:"X";?></span> Pending
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='shipped') ? 'active':"";?>"  href="/sales/reporting?order_status=shipped" role="tab">
                    <span class="badge badge-secondary"><?= isset($order_counts['shipped']) ? $order_counts['shipped']:"X";?></span> Shipped
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='canceled') ? 'active':"";?>"  href="/sales/reporting?order_status=canceled" role="tab">
                    <span class="badge badge-secondary"><?= isset($order_counts['canceled']) ? $order_counts['canceled']:"X";?></span> Canceled
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='completed') ? 'active':"";?>"  href="/sales/reporting?order_status=completed" role="tab">
                     <span class="badge badge-secondary"><?= isset($order_counts['completed']) ? $order_counts['completed']:"X";?></span> Completed
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='other') ? 'active':"";?>"  href="/sales/reporting?order_status=other" role="tab">
                   <span class="badge badge-secondary"> <?= isset($order_counts['other']) ? $order_counts['other']:"X";?></span> Other
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link "  href="/order-shipment/load-sheet" role="tab">
                    <span class="badge badge-secondary"></span> Load Sheet
                </a>
            </li>
        </ul>


    </div>
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <a  title="Clear Filter" class="btn btn-sm btn-secondary pull-right" href="/sales/reporting" >
            <i class="fa fa-filter "></i>
        </a>&nbsp;
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/sales/export-csv?<?=http_build_query($_GET)?>">
            <i class="fa fa-download"> </i>
        </a> &nbsp;
        <a  class="btn btn-sm btn-secondary pull-right mr-2" >Records
            <i class="fa fa-notes"> : <?= isset($total_records) ? $total_records:"x";?></i>
        </a>

    </div>
</div>