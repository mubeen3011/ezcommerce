<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/21/2018
 * Time: 11:20 AM
 */
/*echo '<pre>';
print_r($crawled_skus);
die;*/
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Reports', 'url' => ['/reports/skus-crawl-report']];
$this->params['breadcrumbs'][] = 'Crawler Report';
?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Crawl Report</h3>
                </div>
            </div>
        </div>
        <!--Negative margin skus-->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">Skus Successful Crawled <i style="font-size: 20px;color: green;" class="fa fa-check-circle-o"></i></h4>
                    <div class="table-responsive ">
                            <table class="table dataTable skus-crawled">
                                <thead>
                                <tr>
                                    <th>Sku</th>
                                    <th>Channel</th>
                                    <th>Product Ids</th>
                                    <th>Least Rate Product Id</th>
                                    <th>Price</th>
                                    <th>Seller name</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ( $crawled_skus as $key=>$value ){
                                    if ( !isset($value['Crawl_Detail']['product_id']) )
                                        continue;
                                    ?>
                                    <tr>
                                        <td><?=$value['sku']?></td>
                                        <td><?=$value['name']?></td>
                                        <td>
                                            <?php
                                            $Product_Ids=explode('?',$value['product_ids']);
                                            foreach ($Product_Ids as $product_valz){
                                                echo $product_valz.'<br />';
                                            }
                                            ?>
                                        </td>
                                        <td><?=isset($value['Crawl_Detail']['product_id']) ? $value['Crawl_Detail']['product_id'] : '' ?></td>
                                        <td><?=isset($value['Crawl_Detail']['price']) ? $value['Crawl_Detail']['price'] : '' ?></td>
                                        <td><?=isset($value['Crawl_Detail']['seller_name']) ? $value['Crawl_Detail']['seller_name'] : '' ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>

                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">Skus Failed To Crawl <i style="font-size: 20px;color: red;" class="fa fa-times-circle"></i></h4>
                    <p>Reason: Maybe because of product not found.</p>
                    <div class="table-responsive ">
                        <table class="table dataTable skus-crawled">
                            <thead>
                            <tr>
                                <th>Sku</th>
                                <th>Channel</th>
                                <th>Product Ids</th>
                                <th>Least Rate Product Id</th>
                                <th>Price</th>
                                <th>Seller name</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ( $crawled_skus as $key=>$value ){
                                if ( isset($value['Crawl_Detail']['product_id']) )
                                    continue;
                                ?>
                                <tr>
                                    <td><?=$value['sku']?></td>
                                    <td><?=$value['name']?></td>
                                    <td>
                                        <?php
                                        $Product_Ids=explode('?',$value['product_ids']);
                                        foreach ($Product_Ids as $product_valz){
                                            echo $product_valz.'<br />';
                                        }
                                        ?>
                                    </td>
                                    <td><?=isset($value['Crawl_Detail']['product_id']) ? $value['Crawl_Detail']['product_id'] : '' ?></td>
                                    <td><?=isset($value['Crawl_Detail']['price']) ? $value['Crawl_Detail']['price'] : '' ?></td>
                                    <td><?=isset($value['Crawl_Detail']['seller_name']) ? $value['Crawl_Detail']['seller_name'] : '' ?></td>
                                </tr>
                                <?php
                            }
                            ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$this->registerJs('$(\'.skus-crawled\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', \yii\web\View::POS_END);