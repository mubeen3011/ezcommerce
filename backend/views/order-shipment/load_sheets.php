<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Order load sheets';
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
                    <h5>Load sheets</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<span class="listing-page">



    <div class="card">
 <div class="card-body">
    <!--------------------->
<div class="row">
    <div class="col-12">
        <!-- Tab panes -->
    <button data-toggle="modal" href="#load_sheet_modal" class="btn btn-info btn-sm generate_sheet_btn">Generate Load sheet</button>
        <div class="tab-pane active table-responsive" id="home" role="tabpanel">

            <!-------table------->
            <table id="myTable" class="table table-bordered ">
                <thead>
                <tr>

                    <th>no</th>
                    <th>Sheet id</th>
                    <th>Created_at</th>
                    <th>Courier</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (isset($data) && !empty($data)) {
                    $count=0;
                    foreach($data as $sheet){
                        ?>

                        <tr>

                            <td><?= ++$count;?></td>
                            <td><?= $sheet['sheet_id']; ?></td>
                            <td><?= $sheet['created_at']; ?></td>
                            <td><?= $sheet['courier_name']; ?></td>
                            <td>

                                <a  target="_blank" href="/order-shipment/download-sheet?sheet_id=<?= $sheet['sheet_id'];?>&&courier=<?= $sheet['courier_id']?>" style="cursor:pointer" data-toggle="tooltip" title="PDF">
                                    <i   class="fa fa-file-pdf-o text-danger"></i>
                                </a>
                                &nbsp; | &nbsp;
                                <a target="_blank" href="/order-shipment/downloadsheet-invoices?sheet_pk_id=<?= $sheet['id'];?>&&courier=<?= $sheet['courier_id']?>" style="cursor:pointer" data-toggle="tooltip" title="packing slips">
                                    <i   class="fas fa-file-invoice text-info"></i>
                                </a>
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

            </table>
            <!-------table------->

        </div>

    </div>

</div>
     <!-------------------->

</div>
</div>

</span>
<div id="displayBox" style="display: none;">
    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
</div>

<!-- sample modal content -->
<div id="load_sheet_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Generate Load Sheet </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body load_sheet_span">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<?php
$this->registerJs(<<< EOT_JS_CODE
$(function(){
    $('.generate_sheet_btn').on('click',function(){
    $.ajax({
            type: "POST",
            url: '/order-shipment/sheet-pending-orders',
            //data: {order_ids},
            dataType: 'json',
            beforeSend: function(){
                $('.load_sheet_span').html('<center>Wait Loading ...</center>');
            },
            success: function(data){
                $('.load_sheet_span').html(data.data);

            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                display_notice('failure',errorThrown);
            }
        });
    });
    /*******************/
    $(document).on('click','.check_all_sheets',function(){
        if($(this).is(':checked')){
            $("input:checkbox[class=load_sheet_checkbox]").each(function() {
                $(this).attr('checked', "checked");
            });
        }else{
            $("input:checkbox[class=load_sheet_checkbox]").each(function() {
               $(this).attr('checked', false);
            });
        }
        
    });
    /************************/
    $(document).on('click','.generate_sheet_submit',function(){
        let order_ids=[];
        $.each($(".load_sheet_checkbox:checked"), function(){
                order_ids.push($(this).attr('data-order-id'));
            });
        
         if(order_ids.length > 0){
             $.ajax({
                    type: "POST",
                    url: '/order-shipment/generate-load-sheet',
                    data: {order_ids},
                    dataType: 'json',
                    beforeSend: function(){
                        $('.generate_sheet_submit').html('<span class="fa fa-spinner fa-spin"></span>');
                    },
                    success: function(data){
                       if(data.status=="success")
                          location.reload();
                        else
                            display_notice('failure',data.msg);
                            
                       $('.generate_sheet_submit').html('Generate');
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        display_notice('failure',errorThrown);
                        $('.generate_sheet_submit').html('Generate');
                    }
                });
         } else{
             display_notice('failure','select atleast 1 order');
         }
            
    });
 });

EOT_JS_CODE
);
?>