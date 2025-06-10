/**
 * Created by Abdullah on 1/17/2019.
 */
$(".show_more_information").on('click', function () {
    var sku = $(this).attr("data-sku-id");
    var row_status = $(this).attr("data-row-status");
    var date = $('[name="date"]').val();
    var current_obj = $(this);

    if (row_status=='closed'){
        $.ajax({
            async: false,
            type: "GET",
            url: "/pricing/pricing-sku",
            data: {'sku': sku,  'date': date},
            dataType: "html",
            beforeSend: function () {
            },
            success: function (data) {
                var curr_obj = current_obj;
                curr_obj.parent().parent().after(data);
                if ( row_status == 'closed' ){
                    var child_i=curr_obj.children("i");
                    child_i.removeClass('mdi-plus-circle-outline');
                    child_i.addClass('mdi-minus-circle-outline');
                    curr_obj.attr("data-row-status","opened")
                }
            },
        });
    }else{
        var _parent=current_obj;
        console.log(_parent.parent().parent().next().remove());
        var child_i=_parent.children("i");
        child_i.addClass('mdi-plus-circle-outline');
        child_i.removeClass('mdi-minus-circle-outline');
        _parent.attr("data-row-status","closed")
    }

    return false;
})
function pageRedirect() {
    window.location.href = "/pricing/sales-export";
}
