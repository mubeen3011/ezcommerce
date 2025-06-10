<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/1/2018
 * Time: 3:30 PM
 */

$this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['/user/generic']];
$this->params['breadcrumbs'][] = 'Product List';
?>
<style>
    .select2-container{
        width: 100% !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <div id="displayBox" style="display: none;">
            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <!--------------------------------->
            <?php
            $session = Yii::$app->session;
            if($session->hasFlash('error')): ?>
                <hr>
                <div class="alert alert-danger pull-left">
                    <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong><?= $session->getFlash('error');?></strong>
                </div>


            <?php endif; ?>
            <?php if($session->hasFlash('success')): ?>
                <hr>
                <div class="alert alert-success pull-left">
                    <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong><?= $session->getFlash('success');?></strong>
                </div>
            <?php endif; ?>
            <!--------------------------------->
            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='Product List'?></h3>
                        <button type="button" id="btnManageSkus" class="btn btn-info" data-toggle="modal" data-target="#newskuModal" data-whatever="@fat">Manage Sku's</button>
                        <?php if (Yii::$app->session->hasFlash('success')): ?>
                            <div class="alert alert-success alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                <h4><i class="icon fa fa-check"></i>Saved!</h4>
                                <?= Yii::$app->session->getFlash('success') ?>
                            </div>
                        <?php endif; ?>
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

                <?php echo  $gridview; ?>
            </div>
        </div>
    </div>

</div>
<!-- Add new skus modal -->
<!-- Modal -->
<?=$this->render('popup/add-new-sku', ['Categories'=>$Categories]);?>
<?php
$this->registerJs( <<< EOT_JS_CODE
$(document).on('change','.cp-cat-dd',function(){
    var selected_cat=$(this).val();
    var selected_sku=$(this).attr('data-sku');
    if(confirm('Are You sure'))
    {
         $.ajax({
            type: "POST",
            url: '/cost-price/update-product-category',
            data: {sku:selected_sku,cat:selected_cat},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				
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