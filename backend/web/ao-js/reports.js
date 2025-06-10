/**
 * Created by user_PC on 11/23/2018.
 */
function ShowFailedList(shop_name) {

    $('.failed-stock-modal-header').text('');
    $('.failed-stock-modal-header').text(shop_name);
    $.ajax({
        type: "GET",
        url: "/reports/fetch-failed-skus",
        data:  'shop_name='+shop_name,
        dataType: "json",
        success: function(msg)
        {
            $('#failed-sku-sync-tbody').text('');

            $.each(msg, function(k, v) {
                var html = '<tr>';
                html += '<td>'+v.Sku+'</td>';
                html += '<td>'+v.message+'</td>';
                html += '<td>'+v.detail_message+'</td>';
                html += '</tr>';
                $('#failed-sku-sync-tbody').append(html);
            });
            $('.failed-sku-stock-sync-list').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'csv'
                ]
            });
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $('.bs-example-modal-lg').modal('show');
        },
        beforeSend: function()
        {

            $.blockUI({
                message: $('#displayBox')
            });
            $('.failed-sku-stock-sync-list').DataTable().destroy();
        }
    });
}