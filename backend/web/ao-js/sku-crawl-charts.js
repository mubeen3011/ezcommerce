/**
 * Created by user_PC on 5/14/2020.
 */

$( marketplaceList ).each(function( index , marketplace ) {

    $(function () {

        var ykeys = [];

        $( channelListDataset[marketplace]['graph_points'] ).each(function( index , values ){
            ykeys.push(values);
        });

        var labels=[];
        $( channelListDataset[marketplace]['graph_labels'] ).each(function( index , values ){
            labels.push(values);
        });
        var colors=[];
        $( channelListDataset[marketplace]['colors'] ).each(function( index , values ){
            colors.push(values);
        });
        "use strict";
        Morris.Area({
            element: marketplace+'-morris-area-chart',
            data: channelListDataset[marketplace]['dataset'],
            xkey: 'period',
            ykeys: ykeys,
            labels: labels,
            pointSize: 3,
            fillOpacity: 0,
            pointStrokeColors:colors,
            behaveLikeLine: true,
            gridLineColor: '#e0e0e0',
            lineWidth: 3,
            hideHover: 'auto',
            lineColors: colors,
            resize: true
        });


    });
});


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

// Dashboard 1 Morris-chart
