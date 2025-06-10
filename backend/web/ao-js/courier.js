/**
 * Created by user_PC on 1/30/2020.
 */
//// to keep track of current selected order
var selected_order={
    order_id:null,
    selected_type:null,
    order_item_pk:null
};

var selected_address={
    fname:null,
    lname:null,
    phone:null,
    email:null,
    address:null,
    city:null,
    state:null,
    zip:null,
    country:null
};
var selected_warehouse={
    name:null,
    phone:null,
    email:null,
    address:null,
    city:null,
    state:null,
    zip:null,
    country:null
};

var selected_courier_id=null;
var select_courier_type=null;
var selected_order_dimensions={
    pkg_length:null,
    pkg_width:null,
    pkg_height:null,
    pkg_weight:null, // pound area LB
    pkg_weight_oz:null // ounce area OZ
};

var selected_service={
    service_id:null,
    service_name:null
};

var selected_package={
    package_id:null,
    package_name:null
};

var cleared_step={   // if all steps are cleared then can ship
    address_validation_step:0,
    order_step:0,
    shipping_rates_step:0
};

var usps_services=null;  // for usps services fetched against package
//// ship now clicked

$('.ship-now-btn').click(function(){
    flush_current_selection(); // flush old session of order selection when new order clicked
    var selected_type=$(this).attr('data-ship-entity');
    var order_id=$(this).attr('data-order-id');
    var order_item_pk=null;
    if(selected_type=='order_item') {
        order_item_pk=$(this).attr('data-item-id');
    }
    if(selected_type && order_id)
    {
        selected_order.order_id=order_id;
        selected_order.selected_type=selected_type;
        selected_order.order_item_pk=order_item_pk;
        $.ajax({
            type: "POST",
            url: '/courier/courier-selection',
            data: {order_id:order_id,selected_type:selected_type,order_item_pk:order_item_pk},
            dataType: 'json',
            beforeSend: function(){
                $('.courier_selection_span').html('<center>Wait Loading ...</center>');
            },
            success: function(msg){
                $('.courier_selection_span').html(msg.data);
                if(msg.address)
                {
                    selected_address.fname=msg.address.customer_fname;
                    selected_address.lname=msg.address.customer_lname;
                    selected_address.phone=msg.address.customer_number;
                    selected_address.email=msg.address.shipping_email;
                    selected_address.address=msg.address.customer_address;
                    selected_address.city=msg.address.customer_city;
                    selected_address.state=msg.address.customer_state;
                    selected_address.zip=msg.address.customer_postcode;
                    selected_address.country=msg.address.customer_country;
                }
                // console.log(selected_address);

            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                display_notice('failure',errorThrown);
            }
        });
    }

    return;
});



////////////////////////////select courier /////////////
$(document).on('click','.select-courier',function(){

    let courier_id=$(this).attr('data-courier-id');
    let courier_type=$(this).attr('data-courier-type');
    selected_courier_id=courier_id;
    selected_courier_type=courier_type;
    var current_btn=$(this);
    if(courier_id)
    {
        $.ajax({
            type: "POST",
            url: '/courier/courier-selected',
            data: {courier_id:courier_id,order_id:selected_order.order_id,selected_type:selected_order.selected_type,order_item_pk:selected_order.order_item_pk},
            dataType: 'json',
            beforeSend: function(){
                current_btn.html('<span class="fa fa-spinner fa-spin"></span>');
            },
            success: function(msg){
                //console.log(msg);
                if(msg.status=='success')
                {
                    cleared_step.address_validation_step=1;
                }
                else
                {
                    cleared_step.address_validation_step=0;
                }
                $('.courier_selection_span').html(msg.data);
                iziToast.destroy();
                if(msg.warehouse)
                {
                    selected_warehouse.name=msg.warehouse.name;
                    selected_warehouse.phone=msg.warehouse.phone;
                    selected_warehouse.address=msg.warehouse.address;
                    selected_warehouse.city=msg.warehouse.city;
                    selected_warehouse.state=msg.warehouse.state;
                    selected_warehouse.zip=msg.warehouse.zipcode;
                    selected_warehouse.country=msg.warehouse.country;
                }
                current_btn.html('Select');
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {

                display_notice('failure',errorThrown);
                current_btn.html('Select');
            }
        });

    }


});

////if address suggestion drop down clicked
$(document).on('change','.address_suggestions',function(){
    let city=$('option:selected', this).attr('data-att-city');
    let state=$('option:selected', this).attr('data-att-state');
    let zip=$('option:selected', this).attr('data-att-zip');
    $('#cust_add_city').val(city);
    $('#cust_add_state').val(state);
    $('#cust_add_zip').val(zip);
});

///// if validate customer address clicked
$(document).on('click','.validate_customer_address',function(){

    let cust_fname=$('input[name="cust_name"]').val();
    let cust_address=$('input[name="cust_address"]').val();
    let cust_city=$('#cust_add_city').val();
    let cust_state=$('#cust_add_state').val();
    let cust_zip=$('#cust_add_zip').val();
    let cust_country=$('#cust_add_country').val();
    let cust_phone=$('input[name="cust_phone"]').val();
    let cust_email=$('input[name="cust_email"]').val();
    //
    selected_address.fname=cust_fname;
    selected_address.address=cust_address;
    selected_address.city=cust_city;
    selected_address.state=cust_state;
    selected_address.zip=cust_zip;
    selected_address.country=cust_country;
    selected_address.email=cust_email;
    selected_address.phone=cust_phone;
    var current_btn=$(this); // current button
    $.ajax({
        type: "POST",
        url: '/courier/courier-selected',
        data: {customer_address:selected_address,courier_id:selected_courier_id,order_id:selected_order.order_id,selected_type:selected_order.selected_type,order_item_pk:selected_order.order_item_pk},
        dataType: 'json',
        beforeSend: function(){
            current_btn.html('<span class="fa fa-spinner fa-spin"></span>');
            //display_notice('info','Wait progressing','keep');
        },
        success: function(msg){
            if(msg.status=='success')
            {
                cleared_step.address_validation_step=1;
                display_notice('success','updated');
            }
            else
            {
                cleared_step.address_validation_step=0;
                // display_notice('failure',msg.msg);
            }
            $('.courier_selection_span').html(msg.data);
            current_btn.html('Validate Address Again');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {

            display_notice('failure',errorThrown);
            current_btn.html('Validate Address Again');
        }
    });
});

/////////////////////get shipping rates btn clicked//////////
$(document).on('click','.get-shipping-rates-btn',function(){

    selected_service.service_id=$('option:selected', '#ups_service_type').val();
    selected_service.service_name=$('option:selected', '#ups_service_type').text();

    selected_package.package_id=$('option:selected', '#package_type_dd').val();
    selected_package.package_name=$('option:selected', '#package_type_dd').text();

    ///for lcs enable this field
    //let order_total=$('#order_total_input').val(); // total amount of order
    /////////

    selected_order_dimensions.pkg_length=$('#package_length').val();
    selected_order_dimensions.pkg_height=$('#package_height').val();
    selected_order_dimensions.pkg_width=$('#package_width').val();
    selected_order_dimensions.pkg_weight=$('#package_weight').val(); //pound area
    selected_order_dimensions.pkg_weight_oz=$('#package_weight_oz').val(); //ounce area
    var ship_date=$('.ship_date_input').val(); // for usps

    var current_btn=$(this);
    $.ajax({
        type: "POST",
        url: '/courier/get-shipping-rates',
        data: {ship_date:ship_date,service:selected_service,package_type:selected_package,dimensions:selected_order_dimensions,customer_address:selected_address,'warehouse':selected_warehouse,courier_id:selected_courier_id,order_id:selected_order.order_id,selected_type:selected_order.selected_type,order_item_pk:selected_order.order_item_pk},
        dataType: 'json',
        beforeSend: function(){

            current_btn.html('<span class="fa fa-spinner fa-spin"></span>');
            usps_services=null; // usps services and addons set to null
        },
        success: function(msg){
            if(msg.status=='success')
            {
                display_notice('success','updated');
                cleared_step.shipping_rates_step=1;
                if(msg.hasOwnProperty('services')){ // for usps
                    usps_services=msg.services; // usps services fetched against package
                }
            }
            else
            {
                display_notice('failure',msg.msg);
                cleared_step.shipping_rates_step=0;
            }
            $('.shipping_rates_display').html(msg.data);

            current_btn.html('Get shipping rates');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {

            display_notice('failure',errorThrown);
            current_btn.html('Get shipping rates');
        }
    });
});

// final shipping from submit
$(document).on('submit','#ship_now_submit_form',function(e){
    e.preventDefault();
    var step_validation=check_steps_before_ship(); // check if address validated and shipping rates validated
    if(step_validation['status']==='failure')
    {
        display_notice('failure',step_validation['msg']);
        return;
    }
    var submit_btn=".btn_submit_shipment";
    $.ajax({
        type: "POST",
        url: $(this).attr('action'),
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function(){
            $(submit_btn).html("<span class='fa fa-spinner fa-spin'></span>");
            $(submit_btn).attr("disabled", true);
            display_notice('info','be patience it may take little time','keep');
        },
        success: function(msg){
            if(msg.status=="success") {
                $('.courier_selection_span').html(msg.data);
                display_notice('success','responded');
            } else {
                display_notice('failure',msg.msg);
            }
            $(submit_btn).html('<i class="fa fa-ship"> SHIP NOW </i>');
            $(submit_btn).removeAttr("disabled");

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            $(submit_btn).html('<i class="fa fa-ship"> SHIP NOW </i>');
            $(submit_btn).removeAttr("disabled");
            display_notice('failure',errorThrown);
        }
    });
});

function check_steps_before_ship()
{
    if(cleared_step.address_validation_step===0)
        return {'status':'failure','msg':'Address validation step should be cleared'};

    if(cleared_step.shipping_rates_step===0 && selected_courier_type!="internal"  && selected_courier_type!="lcs" && selected_courier_type!="blueex" && selected_courier_type!="tcs") // internal courier type dont need ftehing shipping rates
        return {'status':'failure','msg':'First fetch and validate shipping rates'};


    return {'status':'success','msg':'steps cleared'};
}
///if usps service dropdown change
$(document).on('change','#usps_service_type',function(){
    var usps_service_code=$(this).val();
    var usps_service_name=$('option:selected', this).attr('data-usps-service-name');
    var usps_service_amount=$('option:selected', this).attr('data-usps-service-amount');
    if(usps_service_code)
    {

         $('#usps-service-name-input').val(usps_service_name); // hidden inputs service name
         $('#usps-service-amount-input').val(usps_service_amount); // hidden inputs service amount
         make_usps_addons(usps_service_code); // if addons attached with service populate that
    }

});

// make usps addons checkboxes and populate in span
function make_usps_addons(service_code)
{
    $('.additional-addons-span-usps-inputs').html('');// empty addons inputs made for form on checkbox checked
    let html="";
    usps_services[service_code]["addons"].forEach(function (item, index) {
        html +='<div class="col-sm-3">';
            html  += '<div class="checkbox checkbox-success">';
            html  += '<input id="checkbox'+item.code+'" type="checkbox" class="usps-addon-checkbox" data-usps-addon-code="'+item.code+'" data-usps-addon-amount="'+item.amount+'">';
            html  += '<label for="checkbox'+item.code+'"> ' + item.code + "( $ " + item.amount + " )" +  '  </label>';
        html  += '</div></div>';
    });
    $('.additional-addons-span-usps').html(html);
}

////// if addon of usps checked fetch code and amount and make inputs for forms
$(document).on('change','input[type=checkbox]',function(){
    var $boxes_addons = $('input[class=usps-addon-checkbox]:checked'); // all checked addons
    let html="";
    $boxes_addons.each(function(){ // make hidden inputs addon code and amount for form submit
        let code=$(this).attr('data-usps-addon-code');
        let amount=$(this).attr('data-usps-addon-amount');
        html +='<input type="hidden" name="addon_code[]" value="' + code + '">';
        html +='<input type="hidden" name="addon_amount[]" value="' + amount + '">';
    });
    //console.log($boxes_addons);
    $('.additional-addons-span-usps-inputs').html(html);
});
//// cancel or refund of shipping clicked
$('.cancel_shipping_btn').click(function(){
    let selected_order_id=$(this).attr('data-order-id');
    let selected_tracking_number=$(this).attr('data-tracking-number');
    if(selected_order_id!='undefined' && selected_tracking_number!='undefined' )
    {
        if(!confirm('Are You Sure'))
            return;

        $.ajax({
            type: "POST",
            url: '/courier/shipping-cancellation-requested',
            data: {order_id_pk:selected_order_id,tracking_number:selected_tracking_number},
            dataType: 'json',
            beforeSend: function(){
                display_notice('info','wait may take longer..','keep');
            },
            success: function(msg){
                if(msg.status=='success')
                {
                    display_notice('success',msg.msg);
                    location.reload();
                }
                else
                {
                    display_notice('failure',msg.msg);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {

                display_notice('failure',errorThrown);
            }
        });
    }
    else
    {
        display_notice('failure','unable to process for now');
    }

});

// if clicked on ship now , clear previous order details if clicked
function flush_current_selection()
{
    flush_order_selected();
    flush_customer_selected();
    flush_pkg_dimensions();
    flush_steps_register();
    flush_warehouse_selected();

    selected_courier_id=null;
    selected_courier_type=null;
    selected_service.service_id=null;
    selected_service.service_name=null;

    selected_package.package_id=null;
    selected_package.package_name=null;

}

function flush_steps_register()
{
    cleared_step.address_validation_step=0;
    cleared_step.order_step=0;
    cleared_step.shipping_rates_step=0;
}
function flush_order_selected()
{
    selected_order.order_id=null;
    selected_order.selected_type=null;
    selected_order.order_item_pk=null;
}
function flush_customer_selected()
{
    selected_address.city=null;
    selected_address.state=null;
    selected_address.zip=null;
    selected_address.country=null;
    selected_address.fname=null;
    selected_address.lname=null;
    selected_address.phone=null;
    selected_address.email=null;
}
function flush_warehouse_selected()
{
    selected_warehouse.name=null;
    selected_warehouse.phone=null;
    selected_warehouse.address=null;
    selected_warehouse.city=null;
    selected_warehouse.state=null;
    selected_warehouse.zip=null;
    selected_warehouse.country=null;
    selected_warehouse.email=null;
}
function flush_pkg_dimensions()
{
    selected_order_dimensions.pkg_length=null;
    selected_order_dimensions.pkg_width=null;
    selected_order_dimensions.pkg_height=null;
    selected_order_dimensions.pkg_weight=null; //pound area LB
    selected_order_dimensions.pkg_weight_oz=null; //ounce area OZ
}
/// if shipping rates tab clicked in courier attach calendar for ship date input
$(document).on('click','.shipping_rates_accordian',function(){
    $('#datepicker-autoclose').datepicker({
        dateFormat: 'yy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
});

/***
 * if oz value changes
 */

$(document).on('change','#package_weight_oz',function(){
    let oz_input=$(this).val();
    let lb_input=$("#package_weight").val();
    let lb_val=parseInt(lb_input);
    let oz_val=parseInt(oz_input);
    let final_oz_val=0;
    let final_pound_val=0;
    if(oz_val >=16){ // 16 oz= 1 lb
        if(oz_val > 16){
            let pound_part=~~(oz_val/16);
            final_oz_val=(oz_val%16);
            final_pound_val= parseInt(pound_part) +lb_val;
        } else {
            final_pound_val=lb_val+1 };

        $('#package_weight').val(final_pound_val);
        $('#package_weight_oz').val(final_oz_val);
    }

});
/****************************bulk shipping**************************/
/////bulk checkbox
$(".checkbox_order").change(function() {
    toggle_bulk_ship_btn(); // if morethan 1 check box checked then show bulk order btn
});

/*****bulk ship btn hide display****/
function toggle_bulk_ship_btn()
{
    if(check_bulk_checked_boxes() >= 2)
        $('#bulk_ship_btn').css('display','block');
    else
        $('#bulk_ship_btn').css('display','none');
}

/*****check how many checkboxes checked***/
function check_bulk_checked_boxes()
{
    let count=0;
    $.each($(".checkbox_order:checked"), function(){
        count++;
    });
    return count;
}

/***** get order ids of checked items for bulk shipment ***/
function get_bulk_checked_orders()
{
    let order_ids=[];
    $.each($(".checkbox_order:checked"), function(){
        order_ids.push($(this).attr('data-order-id'));
    });
    return order_ids;

}

/*********bulk ship btn clicked*********/
$(document).on('click','#bulk_ship_btn',function(){
    let order_ids=get_bulk_checked_orders(); // get order ids checked
    $.ajax({
        type: "POST",
        url: '/courier/bulk-courier-list',
        data: {order_ids},
        dataType: 'json',
        beforeSend: function(){
            $('.bulk_courier_selection_span').html('<center>Wait Loading ...</center>');
        },
        success: function(msg){
            $('.bulk_courier_selection_span').html(msg.data);

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            display_notice('failure',errorThrown);
        }
    });
});

/*******************bulk courier / courier selected from list**************/
$(document).on('click','.select-courier-bulk',function(){
    let order_ids=get_bulk_checked_orders(); // get order ids checked
    let courier_id=$(this).attr('data-courier-id');
    var current_btn=$(this);
    $.ajax({
        type: "POST",
        url: '/courier/courier-selected-for-bulk-ship',
        data: {order_ids,courier_id},
        dataType: 'json',
        beforeSend: function(){
            current_btn.html('<span class="fa fa-spinner fa-spin"></span>');
        },
        success: function(msg){
            if(msg.status=="success")
                location.reload();

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            display_notice('failure',errorThrown);
        }
    });
});
/*********************/
$('.shipment-failure-reason-btn').click(function(){
    let order_id=$(this).attr('data-order-id');
    $.ajax({
        type: "POST",
        url: '/order-shipment/get-bulk-order-progress-detail',
        data: {order_id},
        dataType: 'json',
        beforeSend: function(){
            $('.bulk-shipment-process-span').html('<span class="fa fa-spinner fa-spin"></span>');
        },
        success: function(data){
            $('.bulk-shipment-process-span').html(data.data);

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            display_notice('failure',errorThrown);
        }
    });
});
/*********************/
$(document).on('click','.action_shipping_queue_btn',function(){
    let id=$(this).attr('data-id');
    let action=$(this).attr('data-action');
    $.ajax({
        type: "POST",
        url: '/order-shipment/shipping-queue-order-change',
        data: {id,action},
        dataType: 'json',
        beforeSend: function(){
            display_notice('info','processing...');
        },
        success: function(data){
            display_notice(data.status,data.msg);
            if(data.status=="success")
                $('#shipment_process_modal').modal('hide');

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            display_notice('failure',errorThrown);
        }
    });
});
/*********************/
/*********************/