/**
 * Created by user_PC on 5/29/2020.
 */
var date = new Date(), y = date.getFullYear(), m = date.getMonth();
var firstDay = new Date(y, m, 1);
var lastDay = new Date(y, m + 1, 0);
start = moment(firstDay);
end = moment(lastDay);
function cb(start, end) {
    if(isPstDat == "")
    {
        $('.input-daterange-datepicker').val(start.format('YYYY-MM-DD') +  ' to '  + end.format('YYYY-MM-DD'));
    }
}
if(isPstDat == "") {
    $('.input-daterange-datepicker').daterangepicker({
        startDate: start,
        endDate: end,
        locale: {
        format: 'YYYY-MM-DD',
        separator:  ' to ' ,
    },
    buttonClasses: ['btn', 'btn-sm'],

    applyClass: 'btn-danger',
    cancelClass: 'btn-inverse',
    ranges: {
    Today: [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
},cb);
cb(start, end);

} else {
    $('.input-daterange-datepicker').daterangepicker({

    locale: {
        format: 'YYYY-MM-DD',
        separator:  ' to '
    },
    buttonClasses: ['btn', 'btn-sm'],

    applyClass: 'btn-danger',
    cancelClass: 'btn-inverse',
    ranges: {
    Today: [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
});

}
$(".input-daterange-datepicker").on('change', function () {
    $("#dfilter").submit();
});

$('.multi-select-items').multipleSelect();

$('#filters').click(function () {
var hasClass=$('.filters-box').is(":visible");
if( hasClass ){
    $(".filters-box").hide();
}else{
    $(".filters-box").show();
}

});
$('.marketplace-filter').change(function () {
    //alert($('.marketplace-filter').val());
    $.ajax({
        type: "get",
        url: "/sales/marketplace-shops",
        data: {marketplace:$('.marketplace-filter').val()},
        dataType: "html",
        beforeSend: function () {

        },
        success: function (options) {
            $('.shop-options').remove();
            $('.shop-filter').append(options);
        }
    });
})