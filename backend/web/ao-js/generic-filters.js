$(document).ready(function (e) {
    $('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass("filters-visible");
        if( hasClass ){
            $('.inputs-margin').removeClass("filters-visible");
            if ( $('.ci-sku-search').length ){
                $('.ci-sku-search').next(".select2-container").show();
            }
            if ( $('.ci-warehouse-search').length ){
                $('.ci-warehouse-search').next(".select2-container").show();
            }

        }else{
            $('.inputs-margin').addClass("filters-visible");
        }
    });
    $('.select-filter').change(function(){
        var filters = [];
        var index = 1;
        var filters_used = 0;
        $('.clear-filters').addClass('hide');
        $(".filter").each(function () {
            var filterField = $(this).attr('data-filter-field');
            var filterType = $(this).attr('data-filter-type');
            //console.log($(this).val());
            if ($(this).val()!='')
                filters_used=1;
            else
                filters_used=0;
            if (filters_used)
                $('.clear-filters').removeClass('hide');
            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter','pagename':pageName,'entitytype':entitytype,'performance':performance};

        callAjax(filterUrl, params);
    });

    $('#search_filters').click(function(){
        // filter data
        var filters = [];
        var index = 1;
        $(".filter").each(function () {
            var filterField = $(this).attr('data-filter-field');
            var filterType = $(this).attr('data-filter-type');

            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
        callAjax(filterUrl, params);
    });

    $("select[name='pdq-sel']").on('change',function () {
        var pid = $(this).val();
        var url = jsUrl+'?pdqs='+pid;
        window.location.href = url;
    });
    // default data

    var params = {'pagename':pageName,'entitytype':entitytype,'performance':performance};

    if ( pageName=='SkuPerformance' ){
        var filters = [];
        $(".filter").each(function () {
            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
            callAjax(filterUrl, params);
    }
    else{
        callAjax(defaultUrl, params);
    }
    // sort data
    $("th i").each(function () {
        $(this).on('click', function () {
            //$(this).addClass('tablesaw-sortable-ascending tablesaw-sortable-descending')
            var field = $(this).attr('data-field');
            var sort = $(this).attr('data-sort');
            params = {field: field, sort: sort, 'type': 'sort', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};

            //toggle sort value
            $('#generic-thead tr th i').removeClass();
            $('#generic-thead tr th i').addClass('fa fa-sort sort-arrows');
            if (sort == 'desc'){
                $(this).attr("data-sort", "asc");
                $(this).children().removeClass('fa-sort');
                $(this).children().addClass('fa-sort-asc');
                //$(this+' i').removeClass('fa-sort');
            }else{
                $(this).attr("data-sort", "desc");
                $(this).children().removeClass('fa-sort-asc');
                $(this).children().addClass('fa-sort-desc ');
            }
            callAjax(sortUrl, params);
        });
    });
    $('th').click(function(){
    });
});

$(document).keypress(function(event){
    if(event.keyCode == 13){
        var filters_used = 0;
        $('.clear-filters').addClass('hide');
        // filter data
        var filters = [];
        var index = 1;
        $(".filter").each(function () {
            var filterField = $(this).attr('data-filter-field');
            var filterType = $(this).attr('data-filter-type');
            if ($(this).val()!='')
                filters_used=1;
            else
                filters_used=0;
            if (filters_used)
                $('.clear-filters').removeClass('hide');
            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
        callAjax(filterUrl, params);
    }
});
$('.clear-filters').click(function(){
    $('.clear-filters').addClass('hide');

    $(".filter").val($(".filter option:first").val());
    $('.filters').val('');
    var filters = [];
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');
        var value = '';
        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':value});
    });
    if ( $('.ci-sku-search').length ){
        $('.ci-sku-search').val('').trigger('change')
    }
    if ( $('.ci-warehouse-search').length ){
        $('.ci-warehouse-search').val('').trigger('change')
    }
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
    callAjax(filterUrl, params);
});

function callAjax(defaultUrl, params) {

    if($('#record_per_page').val() === undefined)
    {
        var record_per_page = 10;
    }
    else
    {
        var record_per_page =  $('#record_per_page').val();
    }
    var records_per_page = record_per_page;
    params.records_per_page =  records_per_page ;// $('#records_per_page').val();
    if (typeof params.page_no == 'undefined') {
        params.page_no=1;
    }
    $.ajax({
        url: defaultUrl,
        data: params,
        beforeSend: function () {
            $(".gridData").html("<tr><td colspan='8'><img src='/theme1/images/icons/synchronize.png' style='height:15px'> Loading Data</td></tr>");
            $("#generic-tbody").remove();
            $('.remove_when_pagination_used').remove();
            $.blockUI({
                message: $('#displayBox')
            });
            //$("thead").after('<tbody><tr align="center"><td><img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif"></td></tr></tbody>');
        },
     success: function (data) {
            split_data = (data.split("|"));
            $("#generic-tbody").remove();
            $("tfoot").remove();
            $("#generic-thead").after((split_data[0]));
            $(".generic-table").after((split_data[1]));
            $.unblockUI({
                onUnblock: function(){ //alert('onUnblock');
                }
            });
            $( document ).trigger( "enhance.tablesaw" );
            if( $( ".btn-select select" ).val() == 'swipe' ){
                var trigger_val = 'swipe';
            }else if( $( ".btn-select select" ).val() == 'columntoggle' ){
                var trigger_val = 'columntoggle';
            }
            //alert( trigger_val );
            $('.btn-select select')
                .val(trigger_val)
                .trigger('change');
            //cost price active status save
            $(".cp-active-select").on('change',function() {
                save_cp($(this));
            });
            $(".cp-extra-cost-update").on('change',function() {
                save_promo_price($(this));
            });
            $(".cp-master-cotton-update").on('change',function() {
                save_master_cotton_price($(this));
            });
        },
        type: 'POST'
    });
}
$('#records_per_page').change(function(){
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');
        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
    callAjax(filterUrl, params);
});

function records_per_page()
{
    var records_page = $("#record_per_page").val();
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');
        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });

    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'records_per_page':records_page,'entitytype':entitytype,'performance':performance};
    callAjax(filterUrl, params);
}

function PageNo(page_no){

    var page_no = page_no;

    var records_page = $("#record_per_page").val();
    // filter data
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');

        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'page_no':page_no,'records_per_page':records_page,'pagename':pageName,'entitytype':entitytype,'performance':performance};
    callAjax(filterUrl, params);
}/**
 * Created by user on 5/18/2018.
 */
$(document).ready(function () {
    if($( ".mydatepicker" ).length){
        $('.mydatepicker, #datepicker').datepicker({
            format: 'yyyy-mm-dd',
            orientation: "bottom"
        });
    }
    function cb(start, end) {
        $('#mydatepicker_range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YY'));
    }
    if($( ".mydatepicker_range" ).length){

        var start = moment().subtract(29, 'days');
        var end = moment();
        $('.mydatepicker_range').daterangepicker({
            autoUpdateInput: false,
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
            ,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear',
            }
        }, cb);

        $('.mydatepicker_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            GetFilteredData();
        });

        $('.mydatepicker_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            GetFilteredData();
        });
        cb(start, end);

        $( ".mydatepicker_range" ).change(function () {
            GetFilteredData();
        });
    }

    function GetFilteredData(){
        var filters = [];
        $(".filter").each(function () {
            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'pagename':pageName,'entitytype':entitytype,'performance':performance};
        callAjax(filterUrl, params);
    }

    //console.log("HELLO")
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
    // This must be a hyperlink
    $("#export").click(function (event) {
        // var outputFile = 'export'
        var outputFile = window.prompt("What do you want to name your output file (Note: This won't have any effect on Safari)") || 'export';
        outputFile = outputFile.replace('.csv','') + '.csv'

        // CSV
        exportTableToCSV.apply(this, [$('#example23_wrapper > table'), outputFile]);

        // IF CSV, don't do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
    });
});
$(document).ready(function() {
    // Order by the grouping
    $('#example tbody').on('click', 'tr.group', function() {
        var currentOrder = table.order()[0];
        if (currentOrder[0] === 2 && currentOrder[1] === 'asc') {
            table.order([2, 'desc']).draw();
        } else {
            table.order([2, 'asc']).draw();
        }
    });
});
function showfilters(){
    if($('.filters-thead').hasClass('filters-hide')==true){
        $('.filters-thead').removeClass('filters-hide');
    }else{
        $('.filters-thead').addClass('filters-hide');
    }
}
if($('.dlm-admin-skus').length > 0)
{
    $('.dlm-admin-skus').DataTable( {
        "order": [[ 1, "asc" ]],
        "searching": true,
        "pageLength": 5,
        "bFilter" : false,
        "bLengthChange": false
    } );
}
function showAllRecords() {
    //alert();
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');
        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs, 'records_per_page' : 'All','page_no' : 'All','pagename':pageName,'entitytype':entitytype,'performance':performance};
    callAjax(filterUrl, params);
}

function save_cp(cur)
{
    var sku = cur.attr('data-sku');
    var active = cur.val();
    //var fields = $row.find('form').serialize();

    $.ajax({
        type: "post",
        url: "/cost-price/save",
        data: {sku:sku,active:active},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css('border-color','green');

        },
    });
}
function save_promo_price(cur)
{
    var sku = cur.attr('data-sku');
    var extra_cost = cur.val();

    $.ajax({
        type: "post",
        url: "/cost-price/update-extra-price",
        data: {sku:sku,extra_price:extra_cost},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css('border-color','green');
        }
    });
}
function save_master_cotton_price(cur)
{
    var sku = cur.attr('data-sku');
    var master_cotton = cur.val();

    $.ajax({
        type: "post",
        url: "/cost-price/update-master-cotton",
        data: {sku:sku,master_cotton:master_cotton},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css('border-color','green');
        }
    });
}