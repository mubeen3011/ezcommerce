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
$this->params['breadcrumbs'][] = ['label' => 'Menu Management', 'url' => ['/menu/index']];
$this->params['breadcrumbs'][] = 'Menu';
?>
<style>

   .menu_div   li{
       cursor:pointer;
        padding: 4px;
       border: 2px solid #f4f4f4;
       font-size:medium;
       /*box-shadow: 0px 0px 3px #888888;*/
       border-left:2px solid   #00B2D5;
       /*background: #00aa88;
       background: #00B0D4;
       color:white*/

    }

</style>

<div class="card">
    <div class="card-body">
        <?= \yii\widgets\Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <h3>Menu</h3> <br /><br /><br />
        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-10  menu_div">
            <ul id="unique-ul">
            <?= (isset($data) && !empty($data)) ? $data : ""?>
            </ul>
            </div>
        </div>
        <br/>


    </div>
</div>


<?php
$this->registerJsFile(
    'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);

$this->registerJs( <<< EOT_JS_CODE
$('document').ready(function() {
			/////////for drag drop///////
		/*$(".reorder-gall").sortable({		
				update: function( event, ui ) {
					updateOrder();
				}
			});
			
			$(".reorder-gall1").sortable({		
				update: function( event, ui ) {
					updateOrder();
				}
			});*/
	$(function(){
        $('#unique-ul').sortable({items:'li',update:function(event,ui){
                 updateOrder();
        }});
    });		
});
			
function updateOrder() {	
	var items = new Array();
	$('#unique-ul li').each(function() {
        if($(this).attr("id"))
        {
            var parent=$(this).parents('li:first').attr("id");
            var id=$(this).attr("id");
            items.push({id:id,parent:parent ? parent:null});
        }
	});
	
	if(items)
	{
	
	    $.ajax({
            type: "POST",
            url: '/menu/update-and-sort',
            data: {data:items},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','processing');					
								},
			 success: function(msg){
			     var message=msg.msg;
					if(msg.done) {
					    message = 'Processed: ' + msg.done + ' , Non processed: ' + msg.error;  
					} 
					display_notice(msg.status,message);
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					
					display_notice('failure',errorThrown);
			} 		
		   });
	
	} else {
	    display_notice('failure','Failed to update');
	}
	
	
}



EOT_JS_CODE
);

