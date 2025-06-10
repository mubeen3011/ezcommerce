<?php

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Stocks', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Stocks Report';
use yii\web\View;
?>
<div class="row">

    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Stocks Report</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>
                <table id="dt-1"
                       class="display nowrap table table-hover table-striped table-bordered stocks-skus">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Cost</th>
                        <th>Total Stock Value</th>
                        <th>Total In Hand Stocks</th>
                        <th>Total Pending Orders</th>
                        <th>ISIS Stocks</th>
                        <th>Blip FBL</th>
                        <th>909 FBL</th>
                        <th>Avent FBL</th>
                        <th>Deal4U FBL</th>
                    </tr>
                    </thead>
                    <?php foreach ($stocksSkus as $value):
                        $isis = (isset($orderSkus[$value['sku']]) ? $orderSkus[$value['sku']]['isis_stocks'] : '0');
                        $fbls = (isset($orderSkus[$value['sku']]) ? $orderSkus[$value['sku']]['blip_fbl_stocks'] : '0');
                        $fbl9 = (isset($orderSkus[$value['sku']]) ? $orderSkus[$value['sku']]['909_fbl_stocks'] : '0');
                        $fbla = (isset($orderSkus[$value['sku']]) ? $orderSkus[$value['sku']]['avent_fbl_stocks'] : '0');
                        $fbld = (isset($orderSkus[$value['sku']]) ? $orderSkus[$value['sku']]['d4u_fbl_stocks'] : '0');
                        $total_inhand = ($value['isis_stocks'] - $isis ) + ($value['blip_fbl_stocks'] - $fbls ) + ($value['avent_fbl_stocks'] - $fbla ) + ($value['d4u_fbl_stocks'] - $fbld );
                        $total_pending_value  = $total_inhand <= 0 ? '0' : $total_inhand * $value['cost'];
                        $total_pending_value  = number_format($total_pending_value,2);
                        ?>
                        <tr>
                            <td><?= $value['sku'] ?></td>
                            <td><?= $value['cost'] ?></td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks Value In Hand: <?=$value['total_stocks_value'] ?><br/>Pending Orders Value: <?=$total_pending_value?>" style="text-decoration: double">
                                    <?= ($total_pending_value) ?>
                                </a>
                            </td>
                            <td><?= $value['total_stocks'] ?></td>
                            <td><?= $total_inhand ?></td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks In Hand: <?=$value['isis_stocks'] ?><br/>Pending Orders: <?=$isis?>" style="text-decoration: double">
                                    <?= ($value['isis_stocks'] - $isis ) ?>
                                </a>
                            </td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks In Hand: <?=$value['blip_fbl_stocks'] ?><br/>Pending Orders: <?=$fbls?>" style="text-decoration: double">
                                    <?= ($value['blip_fbl_stocks'] - $fbls ) ?>
                                </a>
                            </td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks In Hand: <?=$value['909_fbl_stocks'] ?><br/>Pending Orders: <?=$fbl9?>" style="text-decoration: double">
                                    <?= ($value['909_fbl_stocks'] - $fbl9 ) ?>
                                </a>
                            </td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks In Hand: <?=$value['avent_fbl_stocks'] ?><br/>Pending Orders: <?=$fbla?>" style="text-decoration: double">
                                    <?= ($value['avent_fbl_stocks'] - $fbla ) ?>
                                </a>
                            </td>
                            <td> <a href="javascript:;" data-html="true" data-toggle="tooltip" title="Stocks In Hand: <?=$value['d4u_fbl_stocks'] ?><br/>Pending Orders: <?=$fbld?>" style="text-decoration: double">
                                    <?= ($value['d4u_fbl_stocks'] - $fbld ) ?>
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    <tbody>
                    </tbody>
                </table>

            </div>

        </div>

    </div>

</div>