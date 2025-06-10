<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 4/23/2018
 * Time: 11:57 AM
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/*$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => 'Stock List', 'url' => ['stocks/all?pdqs=0']];
$this->params['breadcrumbs'][] = $this->title;*/
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = $title;
?>


    <div class="card">
        <div class="card-body">
            <?= \yii\widgets\Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <h3><?= $title ?></h3> <br /><br /><br />
            <div class="row">
                <div class="col-md-3">

                </div>
                <div class="col-md-4">

                    <?php //ActiveForm::begin(['action' =>'/stocks/submit-test' ,'options' => [ 'class' => 'stock-submit-form']]); ?>
                    <form action="/stocks/add-remove-stocks" class="stock-submit-form" method="post">

                        <div class="form-group">
                            <select class="form-control" name="warehouse_id" id="stock_warehouse_id">
                                <option value="0">SELECT WAREHOUSE</option>
                                <?php foreach($warehouses as $warehouse): ?>
                                    <option value="<?= $warehouse['id'];?>"><?= $warehouse['name'];?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text"  name="sku" id="stock_sku_input" required class="form-control" placeholder="SKU">
                        </div>

                        <div class="form-group">
                            <input type="number" min="1" name="qty" required class="form-control" placeholder="QTY">
                        </div>


                    <div class="form-group">
                        <select class="form-control" name="action">
                                <option value="add">Add</option>
                                <option value="remove">Remove</option>
                         </select>
                    </div>



                    <div class="form-group">
                        <textarea class="form-control" name="reason" placeholder="Reason"></textarea>
                    </div>
                    <div class="form-group">
                        <?= Html::submitButton('Confirm', ['class' => 'btn btn-success btn_submit']) ?>
                    </div>
                    </form>
                    <?php //ActiveForm::end(); ?>
                </div>
                <div class="col-md-5 stock_display_span">

                </div>
            </div>
            <br/>


        </div>
    </div>


<?php
$this->registerJs( <<< EOT_JS_CODE

  $('.stock-submit-form').submit(function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    var submit_btn=".btn_submit";
    $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
			beforeSend: function(){
									$('.stock_display_span').html("<span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);					
								},
			 success: function(msg){
				if(msg.status=="success")
				{
					location.reload();
				}
				else
				{
					display_notice('failure',msg.msg);
				}
					$(submit_btn).html("Confirm");
                    $(submit_btn).removeAttr("disabled");
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					$(submit_btn).html("Confirm");
                    $(submit_btn).removeAttr("disabled");
					display_notice('failure',errorThrown);
			} 		
		   });
       
    });
    ////////////////////////get sku information//////////////////
    $('#stock_sku_input').focusout(function(){
     
     get_sku_stock();
    });
    $('#stock_warehouse_id').change(function(){
        get_sku_stock();
    });
    
    function get_sku_stock()
    {
         let sku=$('#stock_sku_input').val();
         let warehouse_id=$('select[id=stock_warehouse_id] option').filter(':selected').val();
         if(sku && warehouse_id > 0)
         {
             $.ajax({
            type: "POST",
            url: '/stocks/get-sku-stock',
            data: {sku,warehouse_id},
            dataType: 'json',
			beforeSend: function(){
									$('.stock_display_span').html("<span class='fa fa-spinner fa-spin'></span>");					
								},
			 success: function(msg){
				populate_sku_stock(msg);
				
			 }		
		   });
         }
    }
    
    function populate_sku_stock(data)
    {
        let table=`<table class="table table-bordered ">
                            <thead>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Stock Pending</th>
                            </thead>
                            <tbody>
                            <tr>
                                <td>\${data.sku}</td>
                                <td>\${data.stock}</td>
                                <td>\${data.pending_stock}</td>
                            </tr>
                            </tbody>
                        </table>`;
        $('.stock_display_span').html(table);
    }

EOT_JS_CODE
);

