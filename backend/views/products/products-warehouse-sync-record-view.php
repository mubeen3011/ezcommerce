<?php
use yii\helpers\Html;
?>
<div class="row">
    <div class="col-12">
        <!-- Tab panes -->

        <div class="tab-pane active table-responsive" id="home" role="tabpanel">
            <!-------table------->
            <table id="myTable" class="table table-bordered ">
                <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Sku</th>
                    <th>Warehouse</th>
                    <?php if(isset($_GET['product_status']) && $_GET['product_status']=='failed'):?>
                       <th>Failure Reason</th>
                    <?php endif;?>
                    <th>Status</th>

                </tr>
                </thead>
                <tbody>
                <?php
              //  echo "<pre>";
              //  print_r($products);exit;
                if (isset($products) && !empty($products)) {

                    foreach($products as $product){
                        ?>

                        <tr>

                            <td>
                            <span><?= $product['product_name']; ?></span>
                            </td>

                            <td>
                                <span><?= $product['sku']; ?></span>
                            </td>
                            <td>

                                <select data-pk-id="<?= $product['pk_id'] ?>" class="form-control form-control-sm wh_change_dd"  <?php if(isset($product['status']) && $product['status'] != "pending"){ echo "disabled";} ?>>
                                    <option value="<?= ($product['status']=="pending" )? "-1":""; ?>" selected >
                                       <?= ($product['status']=="pending" ) ? "Un Assign this product":"Assign To Warehouse";?>
                                    </option>
                                    <?php if($warehouses) {
                                        foreach ($warehouses as $warehouse) { //if(!$brand['brand']){continue;} ?>
                                            <option value="<?= $warehouse['id']?>" <?= (isset($product['warehouse']) && $product['warehouse']==$warehouse['id']) ? "selected":"";?> ><?= $warehouse['name']?></option>
                                        <?php }} ?>

                                </select>
                            </td>
                            <?php if(isset($_GET['product_status']) && $_GET['product_status']=='failed'):?>
                                <td><?= $product['api_response']; ?></td>
                            <?php endif;?>


                            <td>
                                <span ><?= $product['status']; ?></span>
                            </td>
                        </tr>

                    <?php } } else { ?>
                    <tr>
                        <td colspan="8">

                            <h4 style="text-align:center;text-shadow:1px 2px 2px black;color:#90A4AE">
                                No Record Found
                            </h4>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="9">
                        <!----pagination------>

                        <?=  Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                        <!---------->
                    </td>
                </tr>
                </tfoot>
            </table>


            <!-------table------->

            <!----------------------------------------------------->

        </div>

    </div>

</div>


<?php
$this->registerJs( <<< EOT_JS_CODE


 ///category change
    $(document).on('change','.wh_change_dd',function(){
    var new_warehouse_id=$(this).val();
    var pk_id=$(this).attr('data-pk-id');
    //var old_warehouse_selected=$(this).attr('data-warehouse');
   // alert(pk_id);
   // alert(new_warehouse_id);
    //return;
    if(confirm('Are You sure') && pk_id && new_warehouse_id)
    {
         $.ajax({
            type: "POST",
            url: '/products/update-product-sync-warehouse',
            data: {pk_id,new_warehouse_id},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				if(new_warehouse_id=='-1')
				    location.reload()
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    return;
});

EOT_JS_CODE
);