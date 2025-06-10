$(function(){
    $("#export-table-csv").click(function(){
        $('select').remove();
        $('input').remove();
        $(".export-csv").tableToCSV();
        location.reload(true);
    });

});
function update_manual_stock(val,sku) {
    $.ajax({
        type: "GET",
        url: "/cost-price/update-manual-stock",
        data:  'value='+val+'&sku='+sku,
        dataType: "json",
        success: function(msg)
        {
            if ( msg.status==1 ){
                $.toast({
                    heading: 'Manual stock has updated',
                    text: 'Manual stock of sku ('+msg.sku+') has been updated as '+msg.value,
                    position: 'top-right',
                    loaderBg:'#ff6849',
                    icon: 'success',
                    hideAfter: 3500,
                    stack: 6
                });
            }else{
                $.toast({
                    heading: 'Manual stock not updated successfuly',
                    text: 'Some error is occuring and manual stock not updated.',
                    position: 'top-right',
                    loaderBg:'#ff6849',
                    icon: 'error',
                    hideAfter: 3500
                });
            }
        },
        beforeSend: function()
        {

        }
    });
}
$(document).on('mouseenter', ".iffyTip", function () {
    var current = $(this);
    if (this.offsetWidth < this.scrollWidth && !current.attr('title')) {
        current.tooltip({
            title: current.text(),
            placement: "bottom"
        });
        current.tooltip('show');
    }
});
$('input').click(function(){
    if ( $(this).attr('data-filter-type') == 'operator' && $(this).val() == '' ){
        $(this).val('=');
    }
})
$('input').blur(function(){
    if ( $(this).attr('data-filter-type') == 'operator' && $(this).val() == '=' ){
        $(this).val('');
    }
})
function lead_edit(id) {
    window.location = '/deals-maker/update?id=' + id;
}function view_sales_details(id) {
    window.location = '/sales/item-detail?id=' + id;
}
function edit_excluded_skus( shop_id ) {
    $('#shop_id').val(shop_id);
    $.ajax({
        type: "GET",
        url: "/channels/get-excluded-skus",
        data:  'shop_id='+shop_id,
        dataType: "json",
        success: function(msg)
        {
            $('#reason').text('');
            $('#reason').append(msg.excluded.reason);
            if (msg.excluded.stocks_sync==1)
                $('.stocks-unsync').prop('checked', true);
            else
                $('.stocks-unsync').prop('checked', false);
            if (msg.excluded.price_sync==1)
                $('.price-unsync').prop('checked', true);
            else
                $('.price-unsync').prop('checked', false);
            $.each(msg.excluded.sku_stocks, function( index, value ) {
                //console.log( index + ": " + value );
                $('#stock_skus_list').tagsinput('add', value);
            });
            $.each(msg.excluded.sku_price, function( index, value ) {
                //console.log( index + ": " + value );
                $('#price_skus_list').tagsinput('add', value);
            });
            $('#responsive-modal').modal('toggle');
            //console.log(msg);
        },
        beforeSend: function()
        {
            $('#stock_skus_list').tagsinput('removeAll');
            $('#price_skus_list').tagsinput('removeAll');
        }
    });
}
$('.price-skus-choose').change(function () {
    $('#price_skus_list').tagsinput('add', this.value);
})
$('.stocks-skus-choose').change(function () {
    $('#stock_skus_list').tagsinput('add', this.value);
})
$('#update-skus').click(function () {
    var data = $('#form-data').serialize();
    var reason = $('.reason').val();
    if (reason==''){
        alert('Reason cannot be blank');
        return false;
    }
    $.ajax({
        type: "GET",
        url: "/channels/update-excluded-skus",
        data:  data,
        dataType: "text",
        success: function(msg)
        {
            if (msg==1)
                alert('Updated successfully');
            else
                alert('Error occured');
        },
        beforeSend: function()
        {

        }
    });
});
