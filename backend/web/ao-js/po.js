/**
 * Created by user_PC on 12/18/2018.
 */

$(document).ready(function () {
    /******************************
     BOTTOM SCROLL TOP BUTTON
     ******************************/

        // declare variable
    var scrollTop = $(".scrollTop");

    $(window).scroll(function () {
        // declare variable
        var topPos = $(this).scrollTop();

        // if user scrolls down - show scroll to top button
        if (topPos > 300) {
            $(scrollTop).css("opacity", "1");

        } else {
            $(scrollTop).css("opacity", "0");
        }

    }); // scroll END

    //Click event to scroll to top
    $(scrollTop).click(function () {
        $('html, body').animate({
            scrollTop: 400
        }, 900);
        return false;

    }); // click() scroll top EMD
});

// manage orders PO
$(".po_code").on('change', function () {
    var seq = $('option:selected', this).attr('data-sq');
    $("input[name='po_seq']").val(seq);

});

$(".add-prd").on('click', function () {
    var warehouse = $(this).attr('data-warehouse');
    //alert(warehouse);
    //var already_exsit_skus = $("input[name=SkusAlreadyIncludedInPo]").val();
    var already_exsit_skus = $("input[name='SkusAlreadyIncludedInPo[]']").map(function(){return $(this).val();}).get();
    //$("input[name=SkusAlreadyIncludedInPo[]]").val();
    var category = $('.po-cateogry :selected').val();
    //console.log(already_exsit_skus);
    $.ajax({
        async: true,
        type: "post",
        url: "/stocks/add-product-line",
        data: {'warehouseId': warehouse, 'already_in_list_sku': already_exsit_skus},
        dataType: "html",
        beforeSend: function () {
            $('.sku-add-body').html('');
        },
        success: function (data) {
            $('#addSkuInPOModal').modal('show');
            $('.sku-add-body').html(data);
            $(".sku-selects").select2();
            $(".btn-check").on('click', function () {
                var a = confirm('Are you sure ?');
                if (a != true) {
                    return false;
                }
                var skus = $('.sku-selects option:selected').val();
                //alert(skus);
                var sku_quantity = $(".sku-quantity").val();
                var warehouseId = $('input[name="warehouseId"]').val();
                $.ajax({
                    async: true,
                    type: "post",
                    url: "/stocks/add-sku-details",
                    data: {'sku': skus, 'sku_quantity': sku_quantity, 'warehouseId' : warehouseId},
                    dataType: "html",
                    beforeSend: function () {
                    },
                    success: function (data) {

                        $(".sku-selects").select2();
                        $(".po-sku-list").append(data);
                        $("html, body").animate({scrollTop: $(document).height()}, 1000);
                        $('#addSkuInPOModal').modal('hide');
                        $(".sku-selects option[value='" + skus + "']").remove();

                    },
                });

            });
            $(".btn-cancel").on('click', function () {
                var warehouse = $(this).attr('data-warehouse');
                $(".temp-tr-" + warehouse).html("");
            });
        },
    });
});

$('.close-jq-toast-single').click(function () {
    $('.sku-nofitication').addClass('hide');
})

$('.po_chk:checkbox').click(function () {

    if ($(this).is(":checked")) {
        $('.chk').prop('checked', true);

    } else {
        $('.chk').prop('checked', false);
    }
    $('input[name="po_skus[]"]').on('change', function () {
        Populate2()
    }).change();
});
$('.po_blip_chk:checkbox').click(function () {
    if ($(this).is(":checked")) {
        $('.chk2').prop('checked', true);

    } else {
        $('.chk2').prop('checked', false);
    }
    $('input[name="blip_po_skus[]"]').on('change', function () {
        Populate3()
    }).change();
})
$('.po_f909_chk:checkbox').click(function () {
    if ($(this).is(":checked")) {
        $('.chk3').prop('checked', true);

    } else {
        $('.chk3').prop('checked', false);
    }
    $('input[name="blip_po_skus[]"]').on('change', function () {
        Populate4()
    }).change();
});
$('.po_f909_4_chk:checkbox').click(function () {
    if ($(this).is(":checked")) {
        $('.chk4').prop('checked', true);

    } else {
        $('.chk4').prop('checked', false);
    }
    $('input[name="blip_po_skus[]"]').on('change', function () {
        Populate4()
    }).change();
});
$(".btn-po").on('click', function () {

    // alert($(this).val());
    if ($(this).val() == "Finalize") {

        var r = confirm("Are you sure to finalize this PO?");
        if (r == true) {
            var warehouse = $('input[name="warehouse"]').val();
            $("#po_isis").submit();
            $(this).hide();

        } else {

            return false;
        }
    } else {
        $("#po-form").submit();
    }

});
$('.po-cateogry').change(function () {
    var selected_warehouse = $(this).next().val();
    //alert(selected_warehouse);
    var a = confirm('Are you sure ? Page will reload may effect your changes');
    if (a) {
        window.location.href = '?category=' + $(this).val() + '&selected_warehouse=' + selected_warehouse;
    }

    //$('#update-po-category').submit();
});

function SkuClick(skuid_id) {
    var skuid = $('#' + skuid_id);
    var status = (skuid.attr('data-status'));
    //alert(status);
    if (status == 'close') {
        skuid.removeClass('mdi-minus');
        skuid.addClass('mdi-plus');
        skuid.css('color', 'green');
        $('.' + skuid_id).addClass('hide');
        skuid.attr('data-status', 'open');
    } else if (status == 'open') {
        skuid.addClass('mdi-minus');
        skuid.removeClass('mdi-plus');
        skuid.css('color', 'red');
        $('.' + skuid_id).removeClass('hide');
        skuid.attr('data-status', 'close');
    }
}


function ShowExtraInformation(sku_id, warehouse,poId,bundle) {
    //alert(sku);
    $('#extra-info-sku-id').text(sku_id);
    if ($('input[name="po_id"]').length)
        var po_id = $('input[name="po_id"]').val();
    else
        var po_id = '';

    $.ajax({
        async: true,
        type: "get",
        url: "/stocks/get-sku-extra-information",
        data: 'sku_id=' + sku_id + '&warehouse=' + warehouse + '&poId='+poId+'&bundle='+bundle,
        dataType: "html",
        beforeSend: function () {
            $('.extra-information').text('');
        },
        success: function (data) {
            $('.extra-information').text('');
            $('.extra-information').html(data);
        },
    });
}

$(".add-bundle").on('click', function () {

    var warehouse = $(this).attr('data-warehouse');
    //alert(warehouse);
    var already_exsit_skus = $("input[name='BundleAlreadyIncludedInPo[]']").map(function(){return $(this).val();}).get();
    if (already_exsit_skus.length == 0){
        already_exsit_skus = '[]';
    }
    var category = $('.po-cateogry :selected').val();
    //console.log(already_exsit_skus);
    $.ajax({
        async: true,
        type: "post",
        url: "/stocks/add-product-line-bundle",
        data: {'for': warehouse, 'already_in_list_bundle': already_exsit_skus, 'category': category},
        dataType: "html",
        beforeSend: function () {
            $('.bundle-add-body').html('');
        },
        success: function (data) {
            $('#addBundleInPOModal').modal('show');
            $('.bundle-add-body').html(data);
            //$(".temp-tr-"+warehouse).html(data);
            $(".bundle-selects").select2();
            $("input[name='isis_sku']").focus();
            $(".btn-check-bundle").on('click', function () {
                var bundle_tr_count = $('.bundle-tr').length;
                var a = confirm('Are you sure ?');
                if (a != true) {
                    return false;
                }
                //alert(warehouse+'_sku');
                var bundle = $("." + warehouse + "_bundle :selected").val();
                if (bundle == -1) {
                    alert('Please select bundle to add.');
                    return false;
                }
                console.log("." + warehouse + "_bundle");
                console.log(bundle);
                //alert(skus);
                var sku_quantity = $(".bundle-quantity-" + warehouse).val();
                var bundle_in_html_count = $('.Bundle-Div').length;
                var bundle_ids = $('#bundle_already_in_po' + warehouse).val();
                $.ajax({
                    async: true,
                    type: "post",
                    url: "/stocks/add-bundle-details",
                    data: {
                        'bundle': bundle,
                        'warehouseId': warehouse,
                        'isPO': isPo,
                        'sku_quantity': sku_quantity,
                        'bunle_tr_count': bundle_tr_count,
                        'bundle_in_html': bundle_in_html_count
                        ,
                        'bundle_ids': bundle_ids
                    },
                    dataType: "html",
                    beforeSend: function () {
                    },
                    success: function (data) {
                        $(".po-sku-list").append(data);
                        $("html, body").animate({scrollTop: $(document).height()}, 1000);
                        $('#addBundleInPOModal').modal('hide');
                        $(".bundle-selects option[value='" + bundle + "']").remove();
                    },
                });

            });
            $(".btn-cancel").on('click', function () {
                var warehouse = $(this).attr('data-warehouse');
                $(".temp-tr-" + warehouse).html("");
            });
        },
    });
});

function foc_binded_items(binded_foc_item, quantity) {
    //alert(binded_foc_item);
    $('.bundle-field-' + binded_foc_item).val(quantity);
    //alert(quantity);
}

$('#update-io-er').click(function () {
    var io_er = $('input[name="po_io_fbl"]').val();
    var po_id = $('input[name="po_id"]').val();
    $.ajax({
        type: "GET",
        url: "/stocks/update-io-er",
        data: 'er-io=' + io_er + '&po_id=' + po_id,
        dataType: "text",
        success: function (msg) {
            if (msg == '1') {
                $('#er-io-update-status').fadeIn();
                $('#er-io-update-status').fadeOut(2500);
                $('.er_no_submit').removeClass('hide');
            }
        },
        beforeSend: function () {

        }
    });
});

$(".btn-import").click(function () { // bCheck is a input type button
    var fileName = $("#csv").val();
    var valid = true;
    if (fileName) { // returns true if the string is not empty
        var extension = fileName.replace(/^.*\./, '');

        // Iff there is no dot anywhere in filename, we would have extension == filename,
        // so we account for this possibility now
        if (extension == fileName) {
            extension = '';
        } else {
            // if there is an extension, we convert to lower case
            // (N.B. this conversion will not effect the value of the extension
            // on the file upload.)
            extension = extension.toLowerCase();
        }
        switch (extension) {
            case 'csv':
                valid = true;
                break;

            default:
                // Cancel the form submission
                alert("invaldi file extension -- import only CSV");
                valid = false;
        }
        if (valid) {
            var fileToUpload = new FormData();
            fileToUpload.append( 'fileToSave' , $( '#csv' )[0].files[0] );
            fileToUpload.append( 'po_id' , $("#po_id").val());
            fileToUpload.append( 'io_number' , $("#io_number").val());
            $.ajax({
                type: "POST",
                url: "/stocks/fbl-io-import",
                data: fileToUpload,
                contentType: false,
                cache: false,
                processData:false,
                dataType: "text",
                success: function (msg) {
                    window.location.reload();
                },
                beforeSend: function () {
                    $('#er-io-loading').fadeIn();
                }
            });
        }
    } else { // no file was selected
        alert("no FBL inbound csv file selected");
        valid = false
    }
});
function operator_filter(operator,target_class,number) {
    if (operator=='<'){
        $( "."+target_class ).each(function (i) {
            var num_val = parseInt($( this ).text());
            if ( num_val >= number ){
                $( this ).parent().hide();
            }

        })
    }
    else if( operator=='>' ){
        $( "."+target_class ).each(function (i) {
            var num_val = parseInt($( this ).text());
            if ( num_val <= number ){
                $( this ).parent().hide();
            }

        })
    }
    else if ( operator=='=' ){
        $( "."+target_class ).each(function (i) {
            var num_val = parseInt($( this ).text());
            if ( num_val != number ){
                $( this ).parent().hide();
            }

        })
    }
}
$('#sku_filter,#status_filter,#deals_target_filter,#variations_filter,#threshold_filter,#transit_days_threshold_filter,#current_stock_filter,#stock_in_transit_filter,#master_carton_filter,#suggested_order_qty_filter,#philips_stocks_filter').keyup(function (e) {

    //if(key == '13'){
        $('.status_td').parent().show();
        var sku_filter = $('#sku_filter').val();
        var status_filter = $('#status_filter').val();
        var variations_filter = $('#variations_filter').val();
        var threshold_filter = $('#threshold_filter').val();
        var transit_days_threshold_filter = $('#transit_days_threshold_filter').val();
        var deals_target_filter = $('#deals_target_filter').val();
        var current_stock_filter = $('#current_stock_filter').val();
        var stock_in_transit = $('#stock_in_transit_filter').val();
        var suggested_order_qty_filter = $('#suggested_order_qty_filter').val();
        $('.sku_td:not(:contains('+ sku_filter +'))').parent().hide();
        $('.variation_parent').hide();
        if ( status_filter!='' ){
            $('.status_td:not(:contains('+ status_filter +'))').parent().hide();
        }
        if ( variations_filter!='' ){
            $('.variations_td:not(:contains('+ variations_filter +'))').parent().hide();
        }
        if ( threshold_filter!='' ){
            var operator = threshold_filter.substring(0,1);
            var number = threshold_filter.slice(1,10);
            operator_filter(operator,'threshold_td',number);
        }
        if ( transit_days_threshold_filter!='' ){
            var operator = transit_days_threshold_filter.substring(0,1);
            var number = transit_days_threshold_filter.slice(1,10);
            operator_filter(operator,'transit_days_threshold_td',number);
        }
        if ( deals_target_filter!='' ){
            var operator = deals_target_filter.substring(0,1);
            var number = deals_target_filter.slice(1,10);
            operator_filter(operator,'deals_target_td',number);
        }
        if ( current_stock_filter!='' ){
            var operator = current_stock_filter.substring(0,1);
            var number = current_stock_filter.slice(1,10);
            operator_filter(operator,'current_stock_td',number);
        }
        if ( stock_in_transit!='' ){
            var operator = stock_in_transit.substring(0,1);
            var number = stock_in_transit.slice(1,10);
            operator_filter(operator,'stock_in_transit_td',number);
        }
        if ( suggested_order_qty_filter!='' ){
            //alert(suggested_order_qty_filter);
            var operator = suggested_order_qty_filter.substring(0,1);
            var number = suggested_order_qty_filter.slice(1,10);
            operator_filter(operator,'suggested_order_qty_td',number);
        }


    //}
});
// Form will not submit on enter key on any field.
$('form').keypress(function(e) {
    if (e.which == 13) {
        return false;
    }
});
function UpdateFinalQty(sku,qty,po_id) {
    $.ajax({
        type: "get",
        url: "/stocks/update-final-order-quantity",
        data: {'sku': sku, 'qty': qty, 'po_id': po_id},
        dataType: "html",
        beforeSend: function () {
        },
        success: function (data) {
        },
    });

}
function BundleAddRemove(bundleId,element) {
    var checkBoxes = $(".bundle-checkbox-"+bundleId);
       // alert(element.checked);
    if (element.checked){ // on
        checkBoxes.attr('Checked','Checked');
    }
    else{ // off
        checkBoxes.removeAttr('Checked');

    }
}