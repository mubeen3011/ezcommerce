/**
 * Created by user_PC on 2/8/2019.
 */

$('#filters').click(function () {
    var hasClass=$('.inputs-margin').hasClass("filters-visible");
    if( hasClass ){
        $('.inputs-margin').removeClass("filters-visible");
        $('th .select2-selection--multiple').css('display','block');
    }else{
        $('th .select2-selection--multiple').css('display','none');
        $('.inputs-margin').addClass("filters-visible");
    }

});
$(".white-modal-80").on('click',function () {
    $("#white-modal-80").removeClass('hide');
});
$(".btnClose").click (function () {
    $("#white-modal-80").dialog( "close" );
});

$("#export").click(function (event) {
    // var outputFile = 'export'
    var outputFile = window.prompt("What do you want to name your output file (Note: This won't have any effect on Safari)") || 'export';
    outputFile = outputFile.replace('.csv','') + '.csv'

    // CSV
    exportTableToCSV.apply(this, [$('.grid-view > table'), outputFile]);

    // IF CSV, don't do event.preventDefault() or return false
    // We actually need this to be a typical hyperlink
});

function exportTableToCSV($table, filename) {
    var $headers = $table.find('tr:has(th)')
        ,$rows = $table.find('tr:has(td)')

        // Temporary delimiter characters unlikely to be typed by keyboard
        // This is to avoid accidentally splitting the actual contents
        ,tmpColDelim = String.fromCharCode(11) // vertical tab character
        ,tmpRowDelim = String.fromCharCode(0) // null character

        // actual delimiter characters for CSV format
        ,colDelim = '","'
        ,rowDelim = '"\r\n"';

    // Grab text from table into CSV formatted string
    var csv = '"';
    csv += formatRows($headers.map(grabRow));
    csv += rowDelim;
    csv += formatRows($rows.map(grabRow)) + '"';

    // Data URI
    var csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

    // For IE (tested 10+)
    if (window.navigator.msSaveOrOpenBlob) {
        var blob = new Blob([decodeURIComponent(encodeURI(csv))], {
            type: "text/csv;charset=utf-8;"
        });
        navigator.msSaveBlob(blob, filename);
    } else {
        $(this)
            .attr({
                'download': filename
                ,'href': csvData
                //,'target' : '_blank' //if you want it to open in a new window
            });
    }

    //------------------------------------------------------------
    // Helper Functions
    //------------------------------------------------------------
    // Format the output so it has the appropriate delimiters
    function formatRows(rows){
        return rows.get().join(tmpRowDelim)
            .split(tmpRowDelim).join(rowDelim)
            .split(tmpColDelim).join(colDelim);
    }
    // Grab and format a row from the table
    function grabRow(i,row){

        var $row = $(row);
        //for some reason $cols = $row.find('td') || $row.find('th') won't work...
        var $cols = $row.find('td');
        if(!$cols.length) $cols = $row.find('th');

        return $cols.map(grabCol)
            .get().join(tmpColDelim);
    }
    // Grab and format a column from the table
    function grabCol(j,col){
        var $col = $(col),
            $text = $col.text();

        return $text.replace('"', '""'); // escape double quotes

    }
}
$( document ).trigger( "enhance.tablesaw" );
$('#submit-filters').click(function(){
    $.blockUI({
        message: $('#displayBox'),
        baseZ: 2000
    });
})
$('#offline_shop_import_dropdown').change(function () {
    var channel_id = $('#offline_shop_import_dropdown').val();
    if (channel_id==19){
        $('#po-number-div').removeClass('hide')
        $('input[name="po_number"]').attr('required',true)
    }else{
        $('#po-number-div').addClass('hide')
        $('input[name="po_number"]').attr('required',false)
    }
})
$(document).on('click','.list-group-item',function () {
    $('.list-group-item').removeClass('active');
    $('.list-group-item .justify-content-between h4').removeClass('text-white');
    $(this).addClass('active');
    $(this).children('.justify-content-between').children('h4').addClass('text-white');
    $.ajax({
        type: "get",
        url: "/sales/get-pick-up-dates",
        data: {order_number:$(this).attr('data-order-id'),address_id:$(this).attr('data-address-id')},
        dataType: "json",
        beforeSend: function () {
            $('.shopee-pickup-time').remove();
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            if ( data.msg == 0 ) {
                $('.shopee-shipment-confirm').attr('disabled',false);
            }
            $('.shopee-addresses').after(data.pickupTime);
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
        }
    });

})
$('.shipment-arrange').click(function () {

    var orderItemId = $(this).attr('data-order-item-id');
    var courier_id = $(this).attr('data-courier-id');
    var courier_type = $(this).attr('data-courier-type');
    var channel_id = $(this).attr('data-channel-id');
    var marketplace = $(this).attr('data-marketplace');
    var timestamp = + new Date();
    $.ajax({
        type: "get",
        url: "/sales/arrange-shipment",
        data: {order_number:$(this).attr('data-order-id'),order_item_id : orderItemId, courier_id : courier_id, courier_type : courier_type,
            channel_id : channel_id, marketplace : marketplace, timestamp : timestamp},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.shipment-header , .shipment-content').text('');
            $('.shipment-header').append(data.header);
            $('.shipment-content').append(data.content);
            $('.arrange-shipment-modal').modal('show');
        }
    });

})



$(document).on('click','.lazada-shipment-confirm',function () {


    var order_id = $('.list-group-item.active').attr('data-order-id');
    alert(order_id);
    $.ajax({
        type: "get",
        url: "/sales/shopee-init",
        data: {order_number:order_id},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            //console.log(data);

        }
    });
});
$(document).on('click','.lazada-save-invoice',function () {
    var invoice_number = $('#lazada_invoice_number').val();
    var provider = $('.ShippingProvider').val();
    var provider = provider.replace("Pickup: ","");
    var status = 1;
    //invoice-validation-status
    if (invoice_number == ''){
        status = 0;
        $('.invoice-validation-status').show();
    }
    else{
        status = 1;
        $('.invoice-validation-status').hide();
    }
    if ( provider == '' ) {
        status = 0;
        $('.shipment-providers-validation-status').show();
    }else{
        status = 1;
        $('.shipment-providers-validation-status').hide();
    }
    var tracking_code = $('.tracking-code').text();
    var order_id = $('.order-id').text();
    var channel_id = $('#channel_id').val();
    //alert(status);
    if ( status ){
        $.ajax({
            type: "get",
            url: "/sales/save-invoice-lazada",
            data: {order_id:order_id,tracking_code:tracking_code,invoice_number:invoice_number,ShippingProvider:provider,channel_id:channel_id},
            dataType: "json",
            beforeSend: function () {

            }
            ,
            success: function (data) {
                $('.lazada-save-invoice').remove();
                $('.lazada-order-fulfilment-items-table').remove();
                $('.courier_selection_span').append(data.content);
            }
        });
    }
});
$('.assign-warehouse').change(function () {

    var itemId = $(this).attr('data-item-pk');
    var warehouseId = $(this).val();
    $.ajax({
        type: "post",
        url: "/sales/assign-warehouse",
        data: {itemId:itemId,warehouseId:warehouseId},
        dataType: "json",
        beforeSend: function () {
        }
        ,
        success: function (data) {

            if (data.updated==1){
                display_notice('success','Updated Successfully');
            }else{
                display_notice('failure','Failed To Update');
            }
        }
    });
});
//$(".fedex-shipment-confirm").on('click',function () {
$(document).on('click','.fedex-shipment-confirm',function (e) {
    e.preventDefault();
    var status = FedExFormValidation();
    if(jQuery.inArray(0, status) == 0){
        return false;
    }

    var package_option = $('.fedex-package-option').val();
    var service_option = $('.fedex-service-type').val();
    var one_rate = $('.fedex_one_rate').val();


    var package_type = $('.fedex-package-type').val();
    //var shipping_type = $('.fedex-selected-shipping-type').val();
    var orderItemIds = $('.fedex-order-item-ids').val();
    var courierId = $('.courier-id').val();
    var warehouseId = $('.warehouse-id').val();
    var orderId = $('.order-id').val();
    var channelId = $('.channel-id').val();
    var marketplace = $('.marketplace').val();
    var channel_order_id = $('.channel_order_id').val();
    var ship_date = $('.fedex-ship-date').val();
    var package_weight_lbs = FedExGetPackageWeightLbs( package_type );
    var package_weight_once = FedExGetPackageWeightOnce( package_type );

    var address = $('input[name="cust_address"]').val();
    var state = $('input[name="cust_state"]').val();
    var city = $('input[name="cust_city"]').val();
    var zipcode = $('input[name="cust_zip"]').val();
    var country = $('input[name="cust_country"]').val();

    var customer_validation = $('.customer-validation').val();
    if ( customer_validation==0 ){
        alert("Please provide all the customer information in order to ship your order.");
        return false;
    }
    $.ajax({
        type: "post",
        url: "/sales/ship-now-fedex",
        data: {
            orderItemIds:orderItemIds,
            courierId:courierId,
            warehouseId:warehouseId,
            orderId:orderId,
            channelId:channelId,
            package_weight_lbs:package_weight_lbs,
            package_weight_once:package_weight_once,
            marketplace:marketplace,
            channel_order_id:channel_order_id,
            service_type:service_option,
            package_option:package_option,
            one_rate:one_rate,
            ship_date:ship_date,
            address:address,
            state:state,
            city:city,
            zipcode:zipcode,
            country:country
        },
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        }
        ,
        success: function (data) {

            console.log(data);
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            //console.log(data)
            $('.shipping-rate-fedex , .validate-address-fedex').remove();
            $('.package-details-fedex').remove();
            $('.fedex-tbl-order-items').after(data.response);
            if (data.status=='Success'){
                $('.courier_selection_span').empty();
                $('.fedex-shipment-confirm').remove();
                $('.courier_selection_span').after(data.response);
            }
/*            setTimeout(function() {
                location.reload();
            }, 15000);*/

        }
    });
});
function FedExFormValidation() {
    var status = [];
    var package_type = $('.fedex-package-type').val();
    var service_type = $('.fedex-service-type').val();
    var package_option = $('.fedex-package-option').val();
    var shipDate = $('.fedex-ship-date').val();

    if ( shipDate=='' ){
        $('.fedex-ship-date').parent().addClass('error');
        $('.fedex-ship-date-form-group').find( ".help-block" ).remove();
        $('.fedex-ship-date').after('<div class="help-block"><ul role="alert"><li>This is required</li></ul></div>');
        status.push(0);
    }
    else{
        $('.fedex-ship-date').parent().removeClass('error');
        $('.fedex-ship-date').next().remove();
        if ( validateDate(shipDate) == 0 ){ // 0 means date is not valid
            $('.fedex-ship-date').parent().addClass('error');
            $('.fedex-ship-date-form-group').find( ".help-block" ).remove();
            $('.fedex-ship-date').after('<div class="help-block"><ul role="alert"><li>Please enter the correct date format.</li></ul></div>');
            status.push(0);
        }else{
            $('.fedex-ship-date').parent().removeClass('error');
            $('.fedex-ship-date').next().remove();
        }
    }
    if (service_type==''){
        $('.fedex-service-type').parent().addClass('error');
        $('.fedex-service-type-form-group').find( ".help-block" ).remove();
        $('.fedex-service-type').after('<div class="help-block"><ul role="alert"><li>This is required</li></ul></div>');
        status.push(0);
    }else{
        $('.fedex-service-type').parent().removeClass('error');
        $('.fedex-service-type').next().remove();
    }
    if ( package_option=='' ){
        $('.fedex-package-option').parent().addClass('error');
        $('.fedex-package-option-form-group').find( ".help-block" ).remove();
        $('.fedex-package-option').after('<div class="help-block"><ul role="alert"><li>This is required</li></ul></div>');
        status.push(0);
    }else{
        $('.fedex-package-option').parent().removeClass('error');
        $('.fedex-package-option').next().remove();
    }
    if( package_type=='' ) {
        $('.fedex-package-type').parent().addClass('error');
        $('.fedex-package-type-form-group').find( ".help-block" ).remove();
        $('.fedex-package-type').after('<div class="help-block"><ul role="alert"><li>This is required</li></ul></div>');
        status.push(0);
    }else if ( $('.fedex-package-type').length==0 ){
        if ( $('.fedex-single-item-package-weight').val() < 1 || $('.fedex-single-item-package-weight').val() > 50 || $('.fedex-single-item-package-weight').val()=='' ){
            $('.fedex-single-item-package-weight').parent().addClass('error');
            $('.fedex-single-item-package-weight').parent().find(".help-block").remove();
            $('.fedex-single-item-package-weight').after('<div class="help-block"><ul role="alert"><li>Weight is required. Should be greater than 0 and less than 50</li></ul></div>');
            status.push(0);
        }else{

            $('.fedex-single-item-package-weight').parent().removeClass('error');
            $('.fedex-single-item-package-weight').next().remove();
        }

    }
    else{
        $('.fedex-package-type').parent().removeClass('error');
        $('.fedex-package-type').next().remove();
        if( package_type=='single_package' ){
            $('.fedex-multiple-item-package-group').show();
            if($('.fedex-multiple-item-package-weight').val()=='' || /*$('.fedex-multiple-item-package-weight').val()==0 ||*/ $('.fedex-multiple-item-package-weight').val()<0 ){
                $( ".fedex-multiple-item-package-group" ).find( ".help-block" ).remove();
                $('.fedex-multiple-item-package-group').addClass('error');
                $('.fedex-multiple-item-package-weight').after('<div class="help-block"><ul role="alert"><li>Package weight is required, Should be greater than 0</li></ul></div>');
                status.push(0);
            }else if( $('.fedex-multiple-item-package-weight').val()>50 ){
                $( ".fedex-multiple-item-package-group" ).find( ".help-block" ).remove();
                $('.fedex-multiple-item-package-group').addClass('error');
                $('.fedex-multiple-item-package-weight').after('<div class="help-block"><ul role="alert"><li>Package weight cannot be more than 50LB</li></ul></div>');
                status.push(0);
            }else{
                $('.fedex-multiple-item-package-group').removeClass('error');
                $('.fedex-multiple-item-package-weight').next().remove();
            }
        }else if ( package_type=='seperate_package' ){
            $('.fedex-multiple-item-package-group').hide();
            var package_weight = [];
            $('.fedex-package-items-weight').each(function (index) {

                if ( $( this ).val() == '' || $( this ).val() < 1 ){
                    $( this ).parent().addClass('error');
                    $( this ).parent().find( ".help-block" ).remove();
                    $( this ).after('<div class="help-block"><ul role="alert"><li>Required<br /> & Should be greater than 0</li></ul></div>');
                    package_weight.push($( this ).val());
                    status.push(0);
                }else if ( $( this ).val() > 50 ){
                    $( this ).parent().addClass('error');
                    $( this ).parent().find( ".help-block" ).remove();
                    $( this ).after('<div class="help-block"><ul role="alert"><li>Cannot more than 50LB</li></ul></div>');
                    status.push(0);
                }
                else{
                    $( this ).parent().removeClass('error');
                    $( this ).parent().find( ".help-block" ).remove();
                }
                //break;
            })
        }
    }
    return status;
}
function FedExGetPackageWeightLbs(package_type){
    var package_weight = [];
    if( package_type == 'single_package' )
    {
        package_weight[0] = $('.fedex-single-item-package-weight').val();
    }else if ( package_type == 'seperate_package' ){

        $('.fedex-package-items-weight').each(function (index) {
            package_weight.push($(this).val());
        });
    }else{
        package_weight[0] = $('.fedex-multiple-item-package-weight').val();
    }
    return package_weight;
}
function FedExGetPackageWeightOnce(package_type){
    var package_weight = [];
    //alert(package_type);
    if( package_type == 'single_package' )
    {
        package_weight[0] = $('.oz-weight-single-item').val();
    }
    else if ( package_type == 'seperate_package' ){

        $('.fedex-package-items-weight-once').each(function (index) {
            package_weight.push($(this).val());
        });
    }
    return package_weight;
}
$(document).on('click',".get-shipping-estimate-cost-fedex",function () {

    var status = FedExFormValidation();
    if(jQuery.inArray(0, status) == 0){
        return false;
    }
    var package_type = $('.fedex-package-type').val();
    //alert(package_type);
    var serviceType = $('.fedex-service-type').val();
    var packageOption = $('.fedex-package-option').val()
    var orderItemIds = $('.fedex-order-item-ids').val();
    var fedexOnerate = $('.fedex_one_rate').val();
    var courierId = $('.courier-id').val();
    var warehouseId = $('.warehouse-id').val();
    var orderId = $('.order-id').val();
    var channelId = $('.channel-id').val();
    var ship_date = $('#fedex-datepicker-autoclose').val();
    var package_weight_lbs = FedExGetPackageWeightLbs(package_type);
    var package_weight_once = FedExGetPackageWeightOnce(package_type);

    var address = $('input[name="cust_address"]').val();
    var state = $('input[name="cust_state"]').val();
    var city = $('input[name="cust_city"]').val();
    var zipcode = $('input[name="cust_zip"]').val();
    var country = $('input[name="cust_country"]').val();

    $.ajax({
        type: "get",
        url: "/sales/get-shipping-rates-fed-ex",
        data: {
            orderItemIds:orderItemIds,
            courierId:courierId,
            warehouseId:warehouseId,
            orderId:orderId,
            channelId:channelId,
            package_weight_lbs:package_weight_lbs,
            package_weight_once:package_weight_once,
            serviceType:serviceType,
            packageOption:packageOption,
            'serviceTypeOption':fedexOnerate,
            ship_date:ship_date,
            address:address,
            state:state,
            city:city,
            zipcode:zipcode,
            country:country

        },
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.shipping-rate-fedex').remove();
            $('.fedex-failed').remove();
            $('.fedex-warning').remove();
            $('.api-responses').text('');
            $('.api-responses').append(data.content);

        }
    });
});
$(document).on('change','.fedex-package-type',function () {
    $('.shipping-rate-fedex').remove()
    var package_type = $('.fedex-package-type').val();
    if (package_type=='seperate_package'){
        $('.fedex-weight-cl-th').show();
        $('.fedex-weight-cl-td').show();
        $('.fedex-multiple-item-package-group').hide();
    }
    else if (package_type=='single_package'){
        $('.fedex-weight-cl-th').hide();
        $('.fedex-weight-cl-td').hide();
        $('.fedex-multiple-item-package-group').show();
    }else if (package_type==''){
        $('.fedex-weight-cl-th').hide();
        $('.fedex-weight-cl-td').hide();
        $('.fedex-multiple-item-package-group').hide();
    }
});
$(document).on('change','.fedex-package-option',function () {
    var package_option = $('.fedex-package-option option:selected').text();
    var n = package_option.search("FedEx One Rate");
    $('.fedex_one_rate').remove();
    if( n!=-1 ){
        $('.fedex-shipping-rates-form').append('<input type="hidden" class="fedex_one_rate" value="FEDEX_ONE_RATE"/>')
    }
    return false;
});
$(document).on('keyup','.oz-weight-single-item',function () {
    var weight_in_once = $('.oz-weight-single-item').val();
    //alert(weight_in_once);
    if ( weight_in_once > 16 ){
        var weight_in_lbs = parseInt($('.lbs-weight-single-item').val()) + 1;
        $('.lbs-weight-single-item').val(weight_in_lbs);
        $('.oz-weight-single-item').val(1)
    }
});
$(document).on('change','.oz-weight-single-item',function () {
    var weight_in_once = $('.oz-weight-single-item').val();
    //alert(weight_in_once);
    if ( weight_in_once > 16 ){
        var weight_in_lbs = parseInt($('.lbs-weight-single-item').val()) + 1;
        $('.lbs-weight-single-item').val(weight_in_lbs);
        $('.oz-weight-single-item').val(1)
    }
});
$(document).on('change','.fedex-package-items-weight',function () {
    $('.fedex-package-items-weight').each(function (index) {

        if ( $( this ).val() == '' || $( this ).val() < 0 ){
            $( this ).parent().addClass('error');
            $( this ).parent().find( ".help-block" ).remove();
            $( this ).after('<div class="help-block"><ul role="alert"><li>Required<br /> & Should be greater than 0</li></ul></div>');
        }else if ( $( this ).val() > 50 ){
            $( this ).parent().addClass('error');
            $( this ).parent().find( ".help-block" ).remove();
            $( this ).after('<div class="help-block"><ul role="alert"><li>Cannot more than 50LB</li></ul></div>');
        }
        else{
            $( this ).parent().removeClass('error');
            $( this ).parent().find( ".help-block" ).remove();
        }
    })
});
$(document).on('click','.shopee-order-pickup',function () {

    $.ajax({
        type: "get",
        url: "/sales/shopee-order-fullfillment-get-address",
        data: {order_id:$(this).attr('data-order-id'),channel_id:$(this).attr('data-channel-id')},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
           $('.courier_selection_span').text('');
            if (data.status=='success'){
                $('.courier_selection_span').append(data.data);
            }


        }
    });
});
$(document).on('click','.shopee-order-dropoff',function () {
    $('.shopee-order-dropoff').css({'height' : 'auto'});
    $('.shopee-order-dropoff:hover').css({'opacity' : '1'});
    $('.shopee-order-pickup').addClass('hide');
    $('.show-list-of-states').removeClass('hide');
    $('.dropoff-confirm-div').removeClass('hide');

    $('.shopee-order-dropoff').removeClass('col-lg-3');
    $('.shopee-order-dropoff').addClass('col-lg-6');
});
/*$(document).ready(function () {
    $('.shopee-order-dropoff').hover(function(){
        $('.line').show();
    }, function() {
        $('.line').hide();
    });
})*/

$(document).on('click','.shopee-states-list',function () {

    $.ajax({
        type: "get",
        url: "/sales/shopee-order-fulfilment-get-states",
        data: {order_id:$(this).attr('data-order-id'),channel_id:$(this).attr('data-channel-id')},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            if (data.status=='success'){
                $('.shopee-order-state').parent().remove();
                $('.show-list-of-states').after(data.content);
            }


        }
    });
})
$(document).on('change','.shopee-order-state',function () {

    var state = $(this).val();
    var channel_id = $('.shopee-states-list').attr('data-channel-id');
    var order_id = $('.shopee-states-list').attr('data-order-id');

    $.ajax({
        type: "get",
        url: "/sales/shopee-order-fulfilment-get-branches",
        data: {order_id:order_id,channel_id:channel_id,state:state},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            if (data.status=='success'){
                //shopee-confirm-dropoff-button
                //$('.shopee-confirm-dropoff-button').remove();
                $('.shopee-order-branch').parent().remove();
                $('.shopee-order-state').parent().after(data.content);
            }


        }
    });
});
function ErrorOccured(){
    var html = '<div style="text-align: center;"><h2 class="sweet-alert-custom" style="color: red;">Failed</h2><p style="display: block;" class="sweet-alert-p-custom">Shipment was not successfuly arranged. Due to system error. Try again later.</p></div>';
    return html;
}
$(document).on('click','.shopee-confirm-dropoff-button',function () {

    //return false;
    var state = $(this).val();
    var channel_id = $('.shopee-states-list').attr('data-channel-id');
    var order_id = $('.shopee-states-list').attr('data-order-id');
    var branch_id = $('.shopee-order-branch').val();
    var state_id = $('.shopee-order-state').val();
    var type = 'dropoff';

    $.ajax({
        type: "get",
        url: "/sales/shopee-init",
        data: {order_number:order_id,channel_id:channel_id,branch_id:branch_id,state_id:state_id,type:type},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        error: function (error) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.courier_selection_span').text('');
            var errorHtml = ErrorOccured();
            $('.courier_selection_span').html(errorHtml);
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.courier_selection_span').text('');
            $('.courier_selection_span').append(data.content);
        }
    });
});

$(document).on('click','.shopee-shipment-confirm',function () {

    var pickup_time = $('.shopee_pickup_time').val();
    var address_id = $('.list-group-item.active').attr('data-address-id');
    var order_id = $('.list-group-item.active').attr('data-order-id');
    $.ajax({
        type: "get",
        url: "/sales/shopee-init",
        data: {order_number:order_id,address_id:address_id,pickup_time:pickup_time,type:'pickup'},
        dataType: "json",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        error: function (error) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.courier_selection_span').text('');
            var errorHtml = ErrorOccured();
            $('.courier_selection_span').html(errorHtml);
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.courier_selection_span').text('');
            $('.courier_selection_span').append(data.content);
        }
    });
});
function validateDate(date) {
    var timestamp = Date.parse(date);
    if (isNaN(timestamp) == false) {
        return 1;
    }else{
        return 0;
    }
}
$(document).on('click','.fedex-get-rates-accordian',function () {
    $('#fedex-datepicker-autoclose').datepicker({
        dateFormat: 'yy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        minDate: 0
    });
});
$(document).on('change','#fedex-datepicker-autoclose',function () {

});
$(document).on('change','.fedex-service-type',function () {
    $.ajax({
        type: "get",
        url: "/courier/get-fedex-packages",
        data: {service_type:$('.fedex-service-type').val()},
        dataType: "html",
        beforeSend: function () {
            $.blockUI({
                message: $('#displayBox'),
                baseZ: 2000
            });
        },
        success: function (data) {
            $.unblockUI({
                onUnblock: function(){
                }
            });
            $('.fedex-package-option').remove();
            $('.fedex-package-option-form-group').prepend(data);
            console.log(data);
        }
    });
});