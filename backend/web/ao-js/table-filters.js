$(document).ready(function (e) {


    $("select[name='pdq-sel']").on('change',function () {
        var pid = $(this).val();
        var url = '/stocks/all?pdqs='+pid;

        window.location.href = url;
    });

    // default data

    var params = {'pdqs': pdqs};
    callAjax(defaultUrl, params);
    // sort data
    $("th i").each(function () {
        $(this).on('click', function () {
            var field = $(this).attr('data-field');
            var sort = $(this).attr('data-sort');

            params = {field: field, sort: sort, 'type': 'sort', 'pdqs': pdqs};

            //toggle sort value
            if (sort == 'desc')
                $(this).attr("data-sort", "asc");
            else
                $(this).attr("data-sort", "desc");
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

});

$(document).keypress(function(event){
    var filters_used = 0;

    if(event.keyCode == 13){
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
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs};
        callAjax(filterUrl, params);
    }
});


function callAjax(defaultUrl, params) {
    params.records_per_page=$('#records_per_page').val();
    if (typeof params.page_no !== 'undefined') {
    }else{
        params.page_no=1;
    }
    $.ajax({
        url: defaultUrl,
        data: params,
        beforeSend: function () {
            $(".gridData").html("<tr><td colspan='8'><img src='/theme1/images/icons/synchronize.png' style='height:15px'> Loading Data</td></tr>");
            $.blockUI({
                message: $('#displayBox')
            });
            $("tbody").remove();
        },
        success: function (data) {
            split_data = (data.split("|"));
            $("tbody").remove();
            $("#pagination-footer").remove();
            $("thead").after('<tbody>'+(split_data[0])+'</tbody>');
            $("table").after((split_data[1]));
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

        },
        type: 'POST'
    });
}
$('#records_per_page').change(function(){
    // filter data
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');

        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs};
    callAjax(filterUrl, params);
})
$('.clear-filters').click(function(){

    $('.clear-filters').addClass('hide');

    $(".filter").val($(".filter option:first").val());
    $('.filter').val('');
    var filters = [];
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');
        var value = '';
        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':value});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs};
    callAjax(filterUrl, params);
})
function showAllRecords() {
    //alert();
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');

        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs, 'records_per_page' : 'All','page_no' : 'All'};
    callAjax(filterUrl, params);
}
function PageNo(page_no){
    var page_no = page_no;
    // filter data
    var filters = [];
    var index = 1;
    $(".filter").each(function () {
        var filterField = $(this).attr('data-filter-field');
        var filterType = $(this).attr('data-filter-type');

        filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
    });
    var params = {filters: filters,'type': 'filter', 'pdqs': pdqs,'page_no':page_no};
    callAjax(filterUrl, params);
}
$(document).ready(function () {
    $('#search_filters').click(function (){

        // filter data
        var filters = [];
        var index = 1;
        $(".filter").each(function () {
            var filterField = $(this).attr('data-filter-field');
            var filterType = $(this).attr('data-filter-type');

            filters.push({'filter-field':$(this).attr('data-filter-field'),'filterType':$(this).attr('data-filter-type'),'val':$(this).val()});
        });
        var params = {filters: filters,'type': 'filter', 'pdqs': pdqs};
        callAjax(filterUrl, params);
    })

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
        exportTableToCSV.apply(this, [$('.table-responsive > table'), outputFile]);

        // IF CSV, don't do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
    });
});