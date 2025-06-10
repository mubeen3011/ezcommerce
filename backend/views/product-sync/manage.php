<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product 360', 'url' => ['manage']];
$this->params['breadcrumbs'][] = 'Manage';
if (isset($fields['uqid']))
    $uniqId = $fields['uqid'];
 else
    $uniqId = uniqid();

 $this->params['uniqId']=$uniqId; // to accessible in all render partials

if (isset($_GET['shop']))
    $shopid = \backend\util\HelpUtil::exchange_values('id','shop_id',$_GET['shop'],'product_360_status');
else
    $shopid = '';

$admin_approval=isset($fields['p360']['admin_status']) ? $fields['p360']['admin_status']:"pending";
$current_status=isset($fields['p360']['current_status']) ? $fields['p360']['current_status']:"pending";
?>
<!---css file----->
<link href="/../css/sales_v1.css" rel="stylesheet">

<div class="row">
    <div class="col-lg-12">
        <div class="card" >
            <div class="card-body" >
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h5>Create / Update  Product</h5>
                </div>
            </div>
        </div>
    </div>
</div>
<!--------------------------->
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add attribute</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label> Name</label>
                            <select class="form-control form-control-sm amazon-att-selected">
                                <option value="BulletPoint" data-placeholder="description list points">BulletPoint</option>
                                <option value="LaunchDate" data-placeholder="2019-05-29T00:00:01">LaunchDate</option>
                                <option value="SearchTerms" data-placeholder="rope,entertainment">SearchTerms</option>
                                <option value="TargetAudience" data-placeholder="adults,female,male,unisex">TargetAudience</option>
                                <option value="MaxOrderQuantity" data-placeholder="25">MaxOrderQuantity</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Value</label>
                            <input class="form-control form-control-sm amazon-att-selected-value" >
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <a  class="btn btn-success btn-sm amazon-att-add-btn">ADD</a>
                </div>
            </div>
        </div>
    </div>
<!---------------------------->
<div class="row">

    <div class="col-lg-10 col-md-10 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active"  data-toggle="tab" href="#general-tab" role="tab">
                <span> General</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"  data-toggle="tab"  href="#images-tab" role="tab">
                    <span> Images</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"  data-toggle="tab" href="#variations-tab" role="tab">
                    <span> Variations</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-toggle="tab"  href="#properties-tab" role="tab">
                    <span> Properties / Meta data</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab"  href="#other-tab" role="tab">
                    <span> Other</span>
                </a>
            </li>
        </ul>


    </div>
    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 pull-right">
        <?php if(in_array($admin_approval,['draft','pending','reject']))
                  echo "Approval: <i class='fa fa-times-circle'  style='color:red'></i>";
                elseif(in_array($admin_approval,['approved']))
                    echo "Approval: <i class='fa fa-check-circle text-green' style='color:green'></i>";

        ?>
    </div>

   <!-- <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 ">
        <?php /*if ($isUpdate): */?>
            <input type="submit" name="save" class="btn btn-success btn-sm sbmit_btn_form" value="Update">
        <?php /*else: */?>
            <input type="submit" name="save" class="btn btn-success btn-sm sbmit_btn_form" value="Publish">
            <input type="submit" name="save" class="btn btn-warning btn-sm  sbmit_btn_form" value="Draft">
        <?php /*endif; */?>
    </div>-->

</div>
<!---------------------------->
<div class="card">
    <div class="card-body">
<!--<div class="row">
    <div class="col-12">-->
        <!-- Tab panes -->
        <form id="pinfos" class="" enctype="multipart/form-data" method="post">
            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 pull-right">

                <!-----in case of admin--------->
                <?php $roleId = Yii::$app->user->identity->role_id;
                 if($roleId==1) { ?>
                 <?php if ($isUpdate) {  ?>
                     <?php if(strtolower($current_status)=="draft") { ?>
                             <input type="submit" name="save" class="btn btn-warning btn-sm"  value="Update Draft">
                                 <?php if($admin_approval=="approved") { ?>
                             <input type="submit" name="save" class="btn btn-success btn-sm"   value="Publish">
                                     <?php } else { ?>

                                 <?php if($admin_approval!="reject") { ?>
                                     <a class="btn btn-danger btn-sm text-white btn-reject"  data-field-id="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>" >Reject</a>
                                 <?php } ?>
                                 <a class="btn btn-success btn-sm text-white btn-approval" data-field-id="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>" >Approve</a>
                                    <?php }?>
                     <?php } else { ?>
                         <?php if($admin_approval=="approved") { ?>
                         <input type="submit" name="save" class="btn btn-success btn-sm" style="width:100%" value="Update">
                             <?php } else { ?>

                             <?php if($admin_approval!="reject") { ?>
                                 <a class="btn btn-danger btn-sm text-white btn-reject"  data-field-id="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>" >Reject</a>
                             <?php } ?>
                            <a class="btn btn-success btn-sm text-white btn-approval"  data-field-id="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>" >Approve</a>
                            <?php } ?>
                     <?php } ?>
                 <?php }else{ ?>
                     <!--<input type="submit" name="save" class="btn btn-success btn-sm"  value="Publish">-->
                     <input type="submit" name="save" class="btn btn-warning btn-sm" style="width:100%"  value="Draft">
                 <?php } ?>

                <?php } else { ?>
                <!-----in case of non admin--------->
                <?php if ($isUpdate) {  ?>
                      <?php if(strtolower($current_status)=="draft") { ?>
                     <input type="submit" name="save" class="btn btn-warning btn-sm" style="width:100%"  value="Update Draft">
                    <?php } else { ?>
                        <input type="submit" name="save" class="btn btn-success btn-sm" style="width:100%" value="Update">
                    <?php } ?>
                <?php }else{ ?>
                    <!--<input type="submit" name="save" class="btn btn-success btn-sm"  value="Publish">-->
                    <input type="submit" name="save" class="btn btn-warning btn-sm" style="width:100%"  value="Draft">
                <?php } } ?>
            </div>
        <div class="tab-content">

            <div class="tab-pane active" id="general-tab" role="tabpanel">
                <?= Yii::$app->controller->renderPartial('portions/general',$_params_); ?>
                <!--<input type="hidden" name="save" value="Publish">-->
            </div>

            <!---- variations  tab---->
            <div class="tab-pane" id="variations-tab" role="tabpanel">
                <?= Yii::$app->controller->renderPartial('portions/variations',$_params_); ?>
            </div>

            <!---- properties  tab---->
            <div class="tab-pane" id="properties-tab" role="tabpanel">
                <?= Yii::$app->controller->renderPartial('portions/properties',$_params_); ?>

            </div>
            </form>
            <!---- images tab---->
            <div class="tab-pane" id="images-tab" role="tabpanel">
                <?= Yii::$app->controller->renderPartial('portions/images',$_params_); ?>
            </div>
        <!---- othertab---->
        <div class="tab-pane" id="other-tab" role="tabpanel">
            <?= Yii::$app->controller->renderPartial('portions/other',$_params_); ?>
        </div>
        </div>
   <!-- </div>
</div>-->
        <!---this is just template to add during new variation add---->

    </div>
</div>
    <script>
        var isUpdate = '<?= $isUpdate ?>';
        var status = '<?= $status ?>';
        var shop_id = '<?=$shopid?>';
        var variations_count = '<?= isset($fields['p360']['variations']) ? (count($fields['p360']['variations']) - 1):0; ?>';
        var amazon_att_count=<?= $this->params['$att_count']?>;
    </script>
<?php
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/assets/plugins/tinymce/tinymce.min.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '//cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/assets/plugins/dropzone-master/dist/dropzone.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/js/product-360.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);
/*$this->registerJsFile(
    '/ao-js/product-variations.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);*/
$this->registerJsFile(
    '/ao-js/variations.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);

$this->registerJs(
        <<<EOD
 //alert(amazon_att_count);
$('.amazon-att-add-btn').click(function () {  
    let att_name=$('.amazon-att-selected').val();
    let att_value=$('.amazon-att-selected-value').val();
    
    if(att_value)
    {
        
        let template='<tr>';
             template +='  <td><span class="fa fa-trash remove-amazon-att" style="color: gray;"></td>';
              template +='          <td> ' +  att_name + '</td>';
              template +='          <td> <input class="form-control form-control-sm" type="text"  name="p360[amazon-attributes]['+ amazon_att_count + ']['+ att_name +']" value="' + att_value + '">  </td>';
              template +='          <td>Attribute</td>';
             template +='       </tr>';
        $('#append-amazon-att-span').append(template);
        $('.amazon-att-selected-value').val('');
        display_notice('success','attribute added');
        amazon_att_count=(amazon_att_count+1);
    }
    else
    {
    display_notice('failure','input value');
    }
});

$('.amazon-att-selected').on('change',function(){
let att_name_placeholder=$('option:selected', this).attr('data-placeholder');
    $('.amazon-att-selected-value').attr("placeholder", att_name_placeholder ? att_name_placeholder:"");
});
$(document).on('click','.remove-amazon-att',function(){
    $(this).parent().parent().remove();
    display_notice('success','attribute removed');
});


$("#pinfos").submit(function(e) {
 e.preventDefault(); // avoid to execute the actual submit of the form.

//var submit_btn = $('input[type="submit"]').get(0);
var formData = new FormData(this); // Currently empty
var submit_btn_val = $(this).find("input[type=submit]:focus" ).val();
formData.append('save',submit_btn_val);
//alert(submit_btn_val);
let product_sku=$('#product_sku').val(); // when product created redirect to exact same sku on product list
//return;
    $.ajax({
            type: "POST",
           // dataType:'json',
			data:  formData,
			contentType: false,
			cache: false,
			processData:false,
			url: '/product-sync/manage',
            beforeSend: function () {
                display_notice('info','processing','keep');
            },
           success: function(data)
           {
            if(data.status=='success')
            {
                
                if(isUpdate){
                   display_notice('success','updated');
                   setTimeout(function(){ location.reload(); }, 300);
                   }
                 else{
                 display_notice('success','record processed wait..');
                  setTimeout(function(){ location.href="/products?v"+new Date().getTime() + "&sku="+product_sku ; }, 1500);
                 }
                   
            }
            else if(data.msg)
                display_notice('failure',data.msg);
                else
                display_notice('failure','unable to process check input values properly');
           },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					
					display_notice('failure',errorThrown);
					//show_notification('failure',errorThrown,'','fa fa-warning');
			} 
         });


});

/// if shop checked unchecked
$(document).on('click','.shop-checkbox',function(){
let marketplace_checked=$(this).attr('data-marketplace');
if(marketplace_checked=="amazon")
{
if(this.checked)
toggle_attributes('show');
else
 toggle_attributes('hide');
   
}

});
$(function(){
Dropzone.discover();
  let show_amazon_att=$("#amzon_att_checked_flag").val();
  if(show_amazon_att=='1')
    toggle_attributes('show');
    else
    toggle_attributes('hide');
});

//hide ors how properties area based on shop clicked
function toggle_attributes(action)
{
    if(action=='hide'){
    $("#properties-tab").css('visibility','hidden');
      $("#pinfos").find('input[name^="p360[amazon-attributes]"]').prop("disabled", true);
    }else{
        $("#pinfos").find('input[name^="p360[amazon-attributes]"]').prop("disabled", false);
        $("#properties-tab").css('visibility','visible');
    }
}

///if approval btn clicked
$('.btn-approval').click(function(){
let field_id=$(this).attr('data-field-id');
if(field_id){

   $.ajax({
            type: "POST",
            dataType:'json',
			data:  {field_id:field_id},
			cache: false,
			url: '/product-sync/approve-product',
            beforeSend: function () {
                display_notice('info','processing','keep');
            },
           success: function(data)
           {
            if(data.status=='success')
                location.reload();
            else 
                display_notice('failure',data.msg);
           },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					
					display_notice('failure',errorThrown);
					//show_notification('failure',errorThrown,'','fa fa-warning');
			} 
         });
}
return;
});

//if btn rejection status clicked
$('.btn-reject').click(function(){
let field_id=$(this).attr('data-field-id');
iziToast.question({
    timeout: false,
    close: true,
    overlay: true,
    displayMode: 'once',
    id: 'question',
    zindex: 999,
    title: '',
    color:'#F2F7F8',
    message: '',
    position: 'center',
    buttons: [
        
        ['<button><b>Restore prev approved version</b></button>', function (instance, toast) {
 
           // instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
           reject_approval(field_id,'restore') 
 
        }, true],
        ['<button><b data-toggle="tooltip" title="Dont Restore previous version and reject this">Just reject this</b></button>', function (instance, toast) {
             reject_approval(field_id,'not_restore') 
           // instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
 
        }]
    ],
    onClosing: function(instance, toast, closedBy){
        console.info('Closing | closedBy: ' + closedBy);
    },
    onClosed: function(instance, toast, closedBy){
        console.info('Closed | closedBy: ' + closedBy);
    }
});
});

function reject_approval(field_id,status)  
{
    if(field_id){
    
       $.ajax({
                type: "POST",
                dataType:'json',
                data:  {field_id:field_id,status:status},
                cache: false,
                url: '/product-sync/reject-product-approval',
                beforeSend: function () {
                    display_notice('info','processing','keep');
                },
               success: function(data)
               {
                if(data.status=='success')
                    location.reload();
                else 
                    display_notice('failure',data.msg);
               },
                error: function(XMLHttpRequest, textStatus, errorThrown) 
                { 
                        
                        display_notice('failure',errorThrown);
                        //show_notification('failure',errorThrown,'','fa fa-warning');
                } 
             });
    }
    return;
}

// in other tab comment
$(document).on('click','.comment-remove',function(){
$(this).closest('tr').remove();
});

$('.comment-add-ez-com').click(function(){

let row=`<tr>
           <td>comment <br/>
            <a class="fa fa-trash text-red comment-remove" data-toggle="tooltip" title="remove"></a>
        </td>
        <td><textarea name="p360[ez-com][comments][]" class="form-control form-control-sm"></textarea></td>
        </tr>`;
$('.ez-com-comment-table').append(row);
});
EOD

);