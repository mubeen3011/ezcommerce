/**
 * Created by user on 6/21/2018.
 */

if ($('.select2')[0]) {
    $(".select2").select2({theme: "classic"});
}
if ($('.start_datetime')[0]) {
    $(".start_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        startDate: new Date(),
        todayBtn: true
    }).on('changeDate', function (selected) {
        var minDate = new Date(selected.date.valueOf());
        $('.end_datetime').datetimepicker('setStartDate', minDate);
    });
}
if ($('.end_datetime')[0]) {
    $(".end_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        startDate: new Date(),
        autoclose: true,
        todayBtn: true,
        minDate: 0
    });

}

// update ROI base on budget
$("#dealsmaker-budget").on("change",function(e){
    e.preventDefault();
    var budget = $(this).val();
    var re = /^[0-9]\d{0,9}(\.\d{1,3})?%?$/;
    var actualSales = $("#dealsmaker-actual_sales").val();
    var dealId = $("#deal_id").val();
    var as = actualSales.replace(/,/g, '');

    if(!budget.match( re ))
    {
        alert("Only numbers with decimal places");
        return false;
    } else {
        var roi = (budget / as ) * 100;
        roi = roi.toFixed(2)+"%";
        $("#dealsmaker-roi").val(roi);
        // ajax call to update budget
        $.ajax({
            async: false,
            type: "post",
            url: "/deals-maker/update-deal-budget",
            data: {'deal_id':dealId,'budget':budget},
            dataType: "json",
            beforeSend: function () {
            },
            success: function (data) {
                if(data.msg == 1)
                    alert("Budget value updated.");
            },
        });
    }

});

$(".add-dm-sku").on('click', function () {
    //alert('.add-dm-sku');
    if ( $('.discount-type').val()=='Percentage' ){
        var price_readonly = 'readonly';
    }else{
        var price_readonly = '';
    }
    //alert();
    var len = $('.dm-multi-sku tr').length;
    var skulen = len / 2;

    //reasons array
    var reasons = ['Competitor Top', 'Focus SKUs', 'Philips Campaign',
        'Flash Sale', 'Shocking Deal', 'Aging Stocks', 'EOL', 'Competitive Pricing',
        'Outright', 'Others'];

    var sku = $(".multi-select-sku").val();

    var sku_text = $(".multi-select-sku option:selected").text();
    var skuName = $(".multi-select-sku option:selected").text();
    var price = $(".dm-price").val();
    var qty = $(".dm-qty").val();
    var subsidy = $(".dm-subsidy").val();
    if (subsidy == '') {
        $(".dm-subsidy").val('0');
        subsidy = '0';
    }
    if (qty == '') {
        $(".dm-qty").val('1');
        qty = '1';
    }
    var reason = $(".dm-reason").val();
    var margin_per = $(".dm-margin-per").val();
    var margin_rm = $(".dm-margin-rm").val();
    var cstock = $(".dm-cstock").val();
    var skuExistsCount = 0;
    $('input.skunames').each(function () {
        var skuExists = $(this).val();
        if (skuName == skuExists)
            skuExistsCount = 1;
    });
    var marginPer = margin_per.replace("%", "");
    marginPer = marginPer.replace(",", "");
    var start_date = $("#dealsmaker-start_date").val();
    var end_date = $("#dealsmaker-end_date").val();
    if (skuExistsCount == 0) {


        if (sku != '' && price != '' && qty != '' && reason != '' && isNumber(price) && isNumber(qty) && isNumber(subsidy)) {
            if(start_date != '' && end_date != '') {

                if ($('.additional-skus').val()==''){
                    $('.additional-skus').val( JSON.stringify([sku]) );
                }else{
                    var additional_skus_json = $('.additional-skus').val();
                    var additional_skus = $.parseJSON(additional_skus_json);
                    if(!$.inArray(sku, additional_skus) !== -1){
                        additional_skus.push(sku);
                    }
                    $('.additional-skus').val(JSON.stringify(additional_skus));
                }

                var options = '';
                for (var i = 0; i < reasons.length; i++) {
                    selected = (reason == reasons[i] ) ? 'selected' : '';
                    options += "<option " + selected + " value='" + reasons[i] + "'>" + reasons[i] + "</option>";
                }

                var html = "<td><a style='color: #54667a;' href='javascript:;' data-sku-id='" + sku + "'  class='dm-more up'><i class='mdi mdi-plus-circle-outline' data-toggle='tooltip' title='More info' style='font-size: 20px;'></i></a></td>"
                    + "<td class='td-sku-id'><input type='text' class='skunames form-control form-control-sm' data-sku-id='" + sku + "' name='DM[s_" + sku + "][sku]' readonly value='" + skuName + "'></td> "
                    + "<td class='td-sku-price'><input "+ price_readonly +" type='text' class='deal-td-width form-control list-sku-price form-control-sm' name='DM[s_" + sku + "][price]'  value='" + price + "'></td> "
                    + "<td class='td-sku-subsidy'><input type='text' class='deal-td-width form-control list-sku-subsidy form-control-sm' name='DM[s_" + sku + "][subsidy]'  value='" + subsidy + "'></td> "
                    + "<td class='td-sku-stock'><input type='text' readonly class='deal-td-width form-control form-control-sm' name='DM[s_" + sku + "][stock]'  value='" + cstock + "'></td> "
                    + "<td class='td-sku-target'><input type='text' class='deal-td-width form-control form-control-sm' name='DM[s_" + sku + "][qty]'  value='" + qty + "'></td> "
                    + "<td class='td-sku-margin-percentage'><input type='text' readonly class='deal-td-width form-control form-control-sm' name='DM[s_" + sku + "][margin_per]'  value='" + margin_per + "'></td> "
                    + "<td class='td-sku-margin-amount'><input type='text' readonly class='deal-td-width form-control form-control-sm' name='DM[s_" + sku + "][margin_rm]'  value='" + margin_rm + "'></td> "
                    + "<td><select  class='form-control form-control-sm' style='width: auto;' name='DM[s_" + sku + "][reason]' >" + options + "</select><input type='hidden' name='DM[s_"+sku+"][sku]' value='"+sku_text+"'/> </td>"
                    + "<td>&nbsp;<a href='javascript:;'  data-sku-id='" + sku + "' class='dm-delete'><i class='glyph-icon icon-trash' style='font-size:20px;color: red;'></i></a> </td> ";


            //alert(html);
                $(".dm-multi-sku tbody").append("<tr  class='row-" + sku + " line-item-row'>" + html + "</tr>");
                $('.dm-multi-sku tbody .atleast-one-sku-required').remove();
                //$('.dm-multi-sku tbody .atleast-one-sku-required').remove();
//            $(".multi-select-sku").clear();
                $(".dm-price").val("");
                $(".dm-qty").val("");
                $(".dm-reason").val("");
                $(".dm-margin-per").val("");
                $(".dm-cstock").val("");
                $(".dm-margin-rm").val("");
                $(".dm-subsidy").val("");

                /*$(".dm-multi-sku input").each(function () {
                 $(this).on('change', function () {
                 var dynamicSku = "DM[s_" + sku + "]";
                 calculateSku($(this), dynamicSku);
                 })
                 });*/
                var isExists = $('.dm-multi-sku thead tr tr:gt(0)').length;
                //alert(isExists);
                //alert(isNewRecord);
                if (isExists == 0 && isNewRecord) {
                    $(".btn-draft,.btn-req").removeClass('hide');
                } else {
                    $(".btn-draft,.btn-req").removeClass('hide');
                }
                /*} else {
                 alert(skuName + ' cannot be added as its margin are positives');
                 }*/
                /*else {
                 alert(checkApproveSku())
                 }*/
            }
            else {
                alert("Start Date and End Date cannot be blank");
            }

        }
        else {
            alert("Values cannot be blank or string");
        }
    } else {
        alert(skuName + " already exists!!");
    }

});

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function checkApproveSku() {

}

$(".dm-multi-sku").on('click', 'a.dm-delete', function () {
    var sku = $(this).attr("data-sku-id");
    var sku_text = $(this).parent().parent().children('.td-sku-id').children('input').val();
    var option = new Option(sku_text, sku);
    $(".main-sku-selector").append(option);
    var additional_skus_json = $('.additional-skus').val();
    var additional_skus_array = JSON.parse(additional_skus_json);
    if ( !$.isEmptyObject(additional_skus_array) ){
        additional_skus_array.splice(additional_skus_array.indexOf(sku), 1);
        $('.additional-skus').val( JSON.stringify(additional_skus_array) );
    }
    //console.log();
    var result = confirm("Want to delete this SKU?");
    if (result) {
        $(this).closest('tr').remove();
        $('.more-' + sku).remove();
        if ($('#demo-foo-row-toggler tbody tr').length==0){
            //<tr class="atleast-one-sku-required"><td colspan="100" align="center" style="color:red">PLEASE SELECT AT LEAST SINGLE SKU</td></tr>
            $('#demo-foo-row-toggler tbody').append('<tr class="atleast-one-sku-required"><td colspan="100" align="center" style="color:red">PLEASE SELECT AT LEAST SINGLE SKU</td></tr>');
            $('.btn-req').addClass('hide');
            $('.btn-draft').addClass('hide');
        }
        var isExists = $('.dm-multi-sku thead tr tr:gt(0)').length;
        if (isExists == 0) {
            //$(".btn-draft,.btn-req").addClass('hide');
        } else {
            //$(".btn-draft,.btn-req").removeClass('hide');
        }
    }
});

$(".dm-multi-sku").on('click', 'a.dm-more', function () {

    //alert('.dm-multi-sku');
    var sku = $(this).attr("data-sku-id");

    //alert($(this).hasClass('up'));
    if ($(this).hasClass('up')) {
        //alert('up');
        $(this).removeClass("up").addClass("down");
        $('tr.more-' + sku).removeClass('hide');
        $("i", this).removeClass("mdi mdi-plus-circle-outline").addClass("mdi mdi-minus-circle-outline");
        var dynamicSku = "DM[s_" + sku + "]";

        if (dynamicSku == 0) {
            //alert('dynamicSku=0');
            var channel = $("#dealsmaker-channel_id").val();
            var sku = $("#multi-sku-sel").val();
            var price = $("#dealsmaker-deal_price").val();
            var subsidy = $("#dealsmaker-deal_subsidy").val();
            var qty = $("#dealsmaker-deal_qty").val();
            var calculate_in_form = 1;
        } else {
            //alert('dynamicSku=1');
            var channel = $("#dealsmaker-channel_id").val();
            var sku = $("input[name='" + dynamicSku + "[sku]']").attr("data-sku-id");
            var price = $("input[name='" + dynamicSku + "[price]']").val();
            var subsidy = $("input[name='" + dynamicSku + "[subsidy]']").val();
            var qty = $("input[name='" + dynamicSku + "[qty]']").val();
            calculate_in_form= 0;
        }
        if (price==''){
            alert('Please enter deal price for this sku first.');
            return false;
        }


        $.ajax({
            async: true,
            type: "post",
            url: "/deals-maker/calculate",
            data: {'sku_id': sku, 'channel': channel, price: price, subsidy: subsidy, qty: qty, calculate_in_form:calculate_in_form},
            dataType: "json",
            beforeSend: function () {
            },
            success: function (data) {
                if(calculate_in_form==1){
                    $(obj).closest('tr').find(".dm-margin-per").val(data.sales_margins);
                    $(obj).closest('tr').find(".dm-margin-rm").val(data.sales_margins_rm);
                    if (data.stocks.current_stocks)
                        $(obj).closest('tr').find(".dm-cstock").val(data.stocks.current_stocks);
                }else{
                    $('.row-'+sku).after(data.content);
                }
            },
        });
        //console.log(content);
    } else {
        //alert('down');
        $(this).removeClass("down").addClass("up");
        $('.more-information-'+sku).remove();
        $("i", this).removeClass("mdi mdi-minus-circle-outline").addClass("mdi mdi-plus-circle-outline");
    }
});

/*function calculateSku(obj, isDyn) {
 if (isDyn == 0) {
 var channel = $("#dealsmaker-channel_id").val();
 var sku = $("#multi-sku-sel").val();
 var price = $("#dealsmaker-deal_price").val();
 var subsidy = $("#dealsmaker-deal_subsidy").val();
 var qty = $("#dealsmaker-deal_qty").val();
 } else {
 var channel = $("#dealsmaker-channel_id").val();
 var sku = $("input[name='" + isDyn + "[sku]']").attr("data-sku-id");
 var price = $("input[name='" + isDyn + "[price]']").val();
 var subsidy = $("input[name='" + isDyn + "[subsidy]']").val();
 var qty = $("input[name='" + isDyn + "[qty]']").val();
 }
 if (isDyn==0){
 var calculate_in_form = 1;
 }else{
 calculate_in_form= 0;
 }

 if (channel > 0) {
 alert('in channel if');
 $.ajax({
 async: true,
 type: "post",
 url: "/deals-maker/calculate",
 data: {'sku_id': sku, 'channel': channel, price: price, subsidy: subsidy, qty: qty, calculate_in_form:calculate_in_form},
 dataType: "json",
 beforeSend: function () {
 },
 success: function (data) {
 if(calculate_in_form==1){
 $(obj).closest('tr').find(".dm-margin-per").val(data.sales_margins);
 $(obj).closest('tr').find(".dm-margin-rm").val(data.sales_margins_rm);
 if (data.stocks.current_stocks)
 $(obj).closest('tr').find(".dm-cstock").val(data.stocks.current_stocks);
 }else{
 //return data;
 }
 },
 });
 }
 else {
 alert("Please select Channel");
 }

 }*/
function getLineSkuMargins() {
    var sku = $('.main-sku-selector').val();
    var channel = $('#dealsmaker-channel_id').val();
    var price = $('.main-sku-price').val();
    var subsidy = $('.main-sku-subsidy').val();
    var target = $('.main-sku-target').val();
    var calculate_in_form = 1;

    if( channel=='' ){
        alert('Please select shop first');
        return false;
    }
    if (sku==null || sku==''){
        alert('Please select Sku First');
        return false;
    }


    $.ajax({
        async: true,
        type: "post",
        url: "/deals-maker/calculate",
        data: {'sku_id': sku, 'channel': channel, price: price, subsidy: subsidy, qty: target, calculate_in_form:calculate_in_form},
        dataType: "json",
        beforeSend: function () {
        },
        success: function (data) {
            if(calculate_in_form==1){
                $('.main-sku-margin-percentage').val(data.sales_margins);
                $('.main-sku-margin-amount').val(data.sales_margins_rm);
                if (typeof data.stocks.total_stocks.message == 'undefined') {
                    $('.main-sku-stock').val(data.stocks.total_stocks);
                    // your code here
                }else{
                    $('.main-sku-stock').val(0);
                }
            }else{
                return data;
            }
        },
    });
}
$(".main-sku-price , .main-sku-subsidy").change(function () {
    getLineSkuMargins();
});
$(document).on('change','.list-sku-price',function () {
    var price = $(this).val();
    var subsidy = $(this).parent().parent().children('.td-sku-subsidy').children('.list-sku-subsidy').val();
    var sku = $(this).parent().parent().children('.td-sku-id').children('.skunames').attr('data-sku-id');
    var target = $(this).parent().parent().children('.td-sku-target').children('input').val();
    var channel = $('#dealsmaker-channel_id').val();
    var calculate_in_form = 1;

    $.ajax({
        async: true,
        type: "post",
        url: "/deals-maker/calculate",
        data: {'sku_id': sku, 'channel': channel, price: price, subsidy: subsidy, qty: target, calculate_in_form:calculate_in_form},
        dataType: "json",
        beforeSend: function () {
        },
        success: function (data) {
            $('.row-'+sku+' .td-sku-margin-percentage input').val(data.sales_margins);
            $('.row-'+sku+' .td-sku-margin-amount input').val(data.sales_margins_rm);
        },
    });

});
$(document).on('change','.list-sku-subsidy',function () {
    var subsidy = $(this).val();
    var price = $(this).parent().parent().children('.td-sku-price').children('.list-sku-price').val();
    var sku = $(this).parent().parent().children('.td-sku-id').children('.skunames').attr('data-sku-id');
    var target = $(this).parent().parent().children('.td-sku-target').children('input').val();
    var channel = $('#dealsmaker-channel_id').val();
    var calculate_in_form = 1;

    $.ajax({
        async: true,
        type: "post",
        url: "/deals-maker/calculate",
        data: {'sku_id': sku, 'channel': channel, price: price, subsidy: subsidy, qty: target, calculate_in_form:calculate_in_form},
        dataType: "json",
        beforeSend: function () {
        },
        success: function (data) {
            $('.row-'+sku+' .td-sku-margin-percentage input').val(data.sales_margins);
            $('.row-'+sku+' .td-sku-margin-amount input').val(data.sales_margins_rm);
        },
    });

});
/*$(".dm-multi-sku .sku-add-main input").each(function () {
 alert('sku-add-main');
 $(this).on('change', function () {
 alert('sku-add-main');
 var sku = $(this).attr('data-sku-id');
 if ( $('#multi-sku-sel').val() == '' ){
 alert( 'Please select the sku first' );
 }
 alert('this.change');
 if (typeof sku !== typeof undefined && sku !== false) {
 alert('if');
 var dynamicSku = "DM[" + sku + "]";
 calculateSku($(this), dynamicSku);
 } else {
 alert('else');
 calculateSku($(this), 0);
 }

 })
 });*/
if ($('.dlm-admin-skus-2').length > 0) {
    $('.dlm-admin-skus-2').DataTable(
        {
            "order": [[5, "asc"]],
            "searching": true,
            "pageLength": 25,
            "bFilter": false,
            "bLengthChange": false
        }
    );
}
/// load skus base on shop and category
$("#dealsmaker-channel_id").on('change', function () {

    var shop = $(this).val();
    var category = $("#dealsmaker-category").val();
    var discount_type = $('#dealsmaker-discount_type').val();
    var discount = $('#dealsmaker-discount').val();
    reloadSkus(shop, category, discount_type, discount);
});
function refreshCategoryDropdown() {
    var selected = $('.category-selector').val();
    if(selected != null)
    {
        if(selected.indexOf('all')>=0){
            $(this).val('all').select2();
        }
    }
}
function reloadSkus(shop, cat, discountType, discount) {

    //if (isNewRecord) {
    var additional_skus = JSON.parse($('.additional-skus').val());
    if( additional_skus=='' ){
        additional_skus='NoSkus';
    }
    if (shop == '' && cat != ''){
        alert('Please select Shop');
    }
    else if (cat != '' || (!$.isEmptyObject(additional_skus) && additional_skus!='NoSkus') ) {

        // ajax call
        $.ajax({
            async: true,
            type: "post",
            url: "/deals-maker/load-skus-by-shop",
            dataType: "json",
            data: {'channel': shop, 'category': cat, 'discount_type': discountType, 'discount': discount, 'additionalSkus': additional_skus},
            beforeSend: function () {
                //$(".dm-multi-sku").find("tr:gt(0)").remove();
                $(".dm-multi-sku").find(".category-row").remove();
                $.blockUI({
                    message: $('#displayBox')
                });
            },
            success: function (data) {
                $.unblockUI({
                    onUnblock: function(){ //alert('onUnblock');
                    }
                });
                //console.log(data);
                //$(".dm-multi-sku").find("tr:gt(0)").remove();
                $(".dm-multi-sku").find(".category-added").remove();
                $(".dm-multi-sku").find(".line-item-row").remove();
                if (data != '') {
                    //alert('deleting all category-added classes divs');
                    //$('.category-added').remove();

                    $(".dm-multi-sku tbody").append(data.content);
                    $(".dm-multi-sku input").each(function () {
                        $(this).on('change', function () {
                            var sku = $(this).attr('data-sku-id');
                            if (typeof sku !== typeof undefined && sku !== false) {
                                var dynamicSku = "DM[" + sku + "]";
                                calculateSku($(this), dynamicSku);
                            } else {
                                calculateSku($(this), 0);
                            }

                        })
                    });
                    var target = $('#demo-foo-row-toggler');
                    $('html,body').animate({
                        scrollTop: target.offset().top
                    }, 1000);
                    $('.btn-req').show();
                    $('#save-as-draft').show();
                    $.each(data.skuList, function( index, value ) {
                        $(".main-sku-selector option[value='"+value+"']").remove();

                    });
                }
                else
                    alert('No SKUS under this Category')

            }
        });

    }
    else if ( cat === undefined || cat.length == 0 ){
        //alert('delete all category rows');
        $('.category-added').remove();

    }
    //}
}

// once sku added show buttons
var isExists = $('.dm-multi-sku thead tr tr:gt(0)').length;
if (isExists == 0 && isNewRecord) {
    $(".btn-draft,.btn-req").addClass('hide');
} else {
    $(".btn-draft,.btn-req").removeClass('hide');
}
$('input[name="deals_csv_import"]').change(function () {
    if (isNewRecord){
        $('.btn-req').removeClass('hide');
        $('#save-as-draft').removeClass('hide');
    }
})
$('.update-live-price').click(function () {
    var deal_id = $('#deal_id').val();
    $.ajax({
        async: true,
        type: "get",
        url: "/deals-maker/match-shop-price-with-deals",
        data: "deal_id="+deal_id,
        dataType: "text",
        beforeSend: function () {
            $('.update-live-price').addClass('hide');
            $('.spinner-loader').removeClass('hide');
        },
        success: function (data) {
            //alert(data);
            location.reload();
        }
    });
    return false;
})


$("#duplicate_deal_link").on('click', function () {
    $("#duplicate_deal_name").val("");
    $("#duplicate_deal_start_date").val("");
    $("#duplicate_deal_end_date").val("");
});

$("#btn_duplicate_deal").on('click', function () {
    $("#btn_duplicate_deal").attr("disabled", true);
    var duplicate_deal_name =  $("#duplicate_deal_name").val();
    var duplicate_deal_start_date = $("#duplicate_deal_start_date").val();
    var duplicate_deal_end_date = $("#duplicate_deal_end_date").val();
    var old_deal_id = $("#old_deal_id").val();
    var validate = DuplicateDealFormValidations(duplicate_deal_name,duplicate_deal_start_date,duplicate_deal_end_date);
    if(validate){
        var formdata = {oldDealId:old_deal_id,newDealName:duplicate_deal_name,newDealStartDate:duplicate_deal_start_date,newDealEndDate:duplicate_deal_end_date};
        $.ajax({
            type: "POST",
            url: "/deals-maker/duplicate-deal",
            data: formdata,
            dataType: "json",
            success: function (data) {
                console.log(data);
                if(data.status==1){
                    alert("Deal duplicated successfully");
                    $('#form_duplicate_deal .row').append('<div class="form-group col-md-12" style="text-align: center;"><a target="_blank" href="/deals-maker/update?id='+data.deal_id+'">Click Here to open Duplicate Deal</a></div>');
                    //$('#responsive-modal').modal('hide');
                }else {
                    $('#form_duplicate_deal .row').append('<div class="form-group col-md-12" style="text-align: center;color:red">Something went wrong</div>');
                    $("#btn_duplicate_deal").attr("disabled", false);
                }
            }
        });
    }else{
        $("#btn_duplicate_deal").attr("disabled", false);
    }
});

function DuplicateDealFormValidations(duplicate_deal_name,duplicate_deal_start_date,duplicate_deal_end_date){

    if(duplicate_deal_name== "" && duplicate_deal_start_date=="" && duplicate_deal_start_date == ""){
        alert("Please fill all form fields");
        return false;
    }
    if(duplicate_deal_name== ""){
        alert("Please provide deal name");
        return false;
    }
    if(!ValidateAlphaNumeric(duplicate_deal_name)){
        return false;
    }
    if(duplicate_deal_start_date== ""){
        alert("Please provide start date");
        return false;
    }
    if(duplicate_deal_end_date== ""){
        alert("Please provide end date");
        return false;
    }
    return true;
}

$('#duplicate_deal_name').keyup(function () {
    ValidateAlphaNumeric(this.value);
});

function ValidateAlphaNumeric(text){
    if (text.match(/[^a-zA-Z0-9 ]/g)) {
        text = text.replace(/[^a-zA-Z0-9 ]/g, '');
        alert("Enter only alphanumeric characters.");
        return false;
    }else{
        return true;
    }
}
$('.deal_channel').change(function () {

    var channel_id = $('.deal_channel').val();

    $.ajax({
        async: false,
        type: "post",
        url: "/deals-maker/get-marketplace",
        data: {'channel_id':channel_id},
        dataType: "text",
        success: function (marketplace) {
            $('.deal-channel-sku-list').select2('destroy');
            $('.deal-channel-sku-list').text('');
            if ( marketplace == 'prestashop' )
            {
                $('.customer-type').prop('disabled', false);
            }
            else {
                if ( marketplace=='shopee' ){
                    $.ajax({
                        async: false,
                        type: "post",
                        url: "/deals-maker/shopee-discount-list-dropdown",
                        data: {'channel_id':channel_id},
                        dataType: "html",
                        success: function (dropdown) {
                            $('.last-row').prepend( dropdown )
                        },
                    });
                }else{
                    $('.shopee-discount-dropdown').remove();
                }
                $('.customer-type').val('B2C');
                $('.customer-type').prop('disabled', true);
            }
            $.ajax({
                async: false,
                type: "post",
                url: "/deals-maker/get-channel-available-skus",
                data: {'channel_id':channel_id},
                dataType: "text",
                success: function (data) {
                    $('.deal-channel-sku-list').append(data);
                    $(".deal-channel-sku-list").select2({theme: "classic"});
                },
            });
        },
    });




});
$('#dealsmaker-discount').keyup(function () {

    if ( $(this).val()<1 && $(this).val()!='' ){
        $('#dealsmaker-discount').val(1);
    }

});
$('.discount-type').change(function () {
    var discount_type = $(this).val();
    //alert(discount_type);
    if( discount_type=='' ){
        $('.discount-input').prop('required',false);
    }else{
        $('.discount-input').prop('required',true);
    }

});

$(".category-selector , .discount-input , .discount-type").on('change', function(){
    // alert();
    if ( $('.discount-type').val()!='' && $('.discount-input').val()=='' ){
        return false;
    }
    var selected = $(this).val();

    if(selected != null)
    {
        if(selected.indexOf('all')>=0){
            $(this).val('all').select2();
        }
    }
    var category = [];

    var category = $('.category-selector').val();
    //var categoryIds = $('.category-selector').val().split(',');

    var discount_type = $('#dealsmaker-discount_type').val();
    var discount = $('#dealsmaker-discount').val();
    var shop = $("#dealsmaker-channel_id").val();

    if ( $('.main-sku-selector').val()!='' && $('.main-sku-price').val()!='' ){
        percentageLinesku($('.main-sku-selector').val());
        //alert('if');
    }else{
        //alert('else');
    }
    var line_item_sku = ( $('.line-item-row').length );
    //alert(line_item_sku);
    if (category.length === 0 && line_item_sku === 0 ) {
        $('.category-added').remove();
        if ($('#demo-foo-row-toggler tbody tr').length==0){
            //<tr class="atleast-one-sku-required"><td colspan="100" align="center" style="color:red">PLEASE SELECT AT LEAST SINGLE SKU</td></tr>
            $('#demo-foo-row-toggler tbody').append('<tr class="atleast-one-sku-required"><td colspan="100" align="center" style="color:red">PLEASE SELECT AT LEAST SINGLE SKU</td></tr>');
            $('.btn-req').addClass('hide');
            $('.btn-draft').addClass('hide');
        }
        $(".dm-multi-sku").find(".category-row").remove();
        $('.cat').remove();
    } else {
        //alert('else');
        reloadSkus(shop, category, discount_type, discount);
    }

});
$('#dealsmaker-discount_type').change(function () {
    if ( $(this).val() == 'Percentage' ){
        $('#dealsmaker-deal_price').attr('readonly','readonly');
    }else{
        $('#dealsmaker-deal_price').removeAttr('readonly','readonly');
    }
    if ( $(this).val()!='' ){
        $('#dealsmaker-discount').parent().parent().show();
    }else{
        $('#dealsmaker-discount').parent().parent().hide();
    }
});
function percentageLinesku(sku_id) {
    var discount_type = $('.discount-type').val();
    var discount = $('#dealsmaker-discount').val();
    if (discount_type=='Percentage'){
        $('#dealsmaker-deal_price').val('');
        $.ajax({
            async: false,
            type: "post",
            url: "/deals-maker/apply-percentage",
            data: {'sku_id':sku_id,'discount':discount},
            dataType: "json",
            success: function (data) {
                $('#dealsmaker-deal_price').val(data.deal_price);
                getLineSkuMargins();
            }
        });
    }
}
$('.main-sku-selector').change(function () {
    percentageLinesku($(this).val());
});
