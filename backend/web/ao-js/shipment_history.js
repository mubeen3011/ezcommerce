/////for shipment hostory in modal when plus minus clcked
$(document).on('click','.show_ship_history_plus',function(){
    let id_pk=$(this).attr('data-id-pk');
    $(this).toggleClass('fa-plus fa-minus')
    $('#item-shipment-record-' + id_pk).toggle();
});
var curent_selected_shippment_history={
    order_id:null,
    selected_type:null,
    order_item_pk:null,


};
$('.ship-history-btn').click(function(){

    let selected_type=$(this).attr('data-ship-entity');
    let order_id=$(this).attr('data-order-id');
    let order_item_pk=null;
    if(selected_type=='order_item') {
        order_item_pk=$(this).attr('data-item-id');
    }
    if(selected_type && order_id)
    {
        curent_selected_shippment_history.order_id=order_id;
        curent_selected_shippment_history.selected_type=selected_type;
        curent_selected_shippment_history.order_item_pk=order_item_pk;
        load_shipment_history();

    }

    return;
});

function load_shipment_history()
{
    $.ajax({
        type: "POST",
        url: '/courier/shipment-history',
        data: {order_id:curent_selected_shippment_history.order_id,selected_type:curent_selected_shippment_history.selected_type,order_item_pk:curent_selected_shippment_history.order_item_pk},
        dataType: 'json',
        beforeSend: function(){
            $('.shipment-history-content').html('<center>Wait Loading ...</center>');
        },
        success: function(msg){
            $('.shipment-history-content').html(msg.data);

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            display_notice('failure',errorThrown);
        }
    });
}
$(document).on('change','.change_courier_status_manually',function(e){
    e.preventDefault();
    let selected=$(this).val();
    let id_pk=$(this).attr('data-id-pk');
    if(confirm('Are U sure') && id_pk && selected){
        $.ajax({
            type: "POST",
            url: '/courier/change-courier-status',
            data: {order_item_pk:id_pk,courier_status:selected},
            dataType: 'json',
            beforeSend: function(){
                display_notice('info','progressing...');
            },
            success: function(msg){
                if(msg.status=="success")
                    load_shipment_history();
                display_notice(msg.status,msg.msg);

            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                display_notice('failure',errorThrown);
            }
        });
    }
});