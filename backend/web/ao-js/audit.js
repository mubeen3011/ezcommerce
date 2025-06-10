/**
 * Created by user_PC on 4/14/2020.
 */
$('.input-daterange-datepicker').daterangepicker({
    locale: {
        format: 'YYYY-MM-DD',
        separator: ' to ',
    },
    autoUpdateInput: false,
    buttonClasses: ['btn', 'btn-sm'],

    applyClass: 'btn-danger',
    cancelClass: 'btn-inverse',
    ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
}, function(start, end, label) {
    $('.input-daterange-datepicker').val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
});
if($('input[name="Search[show_category]"]:checked').length > 0)
{
    $(".cat").removeAttr('disabled');
} else {
    $(".cat").attr('disabled',true);
}

$('input[name="Search[show_category]"]').click(function() {
    if (this.checked) {
        $(".cat").removeAttr('disabled');
    } else {
        $(".cat").attr('disabled',true);
    }
});
$('#submit_report').click(function(){
    $.blockUI({
        message: $('#displayBox')
    });
});
$('.show-all').click(function(){
    $.blockUI({
        message: $('#displayBox')
    });
});