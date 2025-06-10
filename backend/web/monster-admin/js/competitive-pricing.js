/**
 * Created by user on 5/29/2018.
 */
$(document).ready(function () {
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
    $('.cp-save').click(function (event) {
        event.preventDefault();
        var isValid = true;
        var $row = $(this).parents('tr');

        var sla = $row.find("input[name='seller_1']");
        var slb = $row.find("input[name='seller_2']");
        var slc = $row.find("input[name='seller_3']");

        var lpa = $row.find("input[name='low_price_1']");
        var lpb = $row.find("input[name='low_price_2']");
        var lpc = $row.find("input[name='low_price_3']");

        if(lpa.val() !== '' && sla.val() === '' )
        {
            sla.css('border-color', 'red');

        } else {
            sla.css('border-color', '');
        }

        if(sla.val().length < 2)
        {
            sla.css('border-color', 'red');

        } else {
            sla.css('border-color', '');
        }

        if(lpb.val() !== '' && slb.val() === '')
        {
            slb.css('border-color', 'red');

        } else {
            slb.css('border-color', '');
        }

        if(slb.val().length < 2)
        {
            slb.css('border-color', 'red');

        } else {
            slb.css('border-color', '');
        }


        if(lpc.val() !== '' && slc.val() === '' )
        {
            slc.css('border-color', 'red');

        } else {
            slc.css('border-color', '');
        }

        if(slc.val().length < 2)
        {
            slc.css('border-color', 'red');

        } else {
            slc.css('border-color', '');
        }


        /*var lp = $row.find('input.numc').each(function() {
           if($(this).val() === '') {
                $(this).css('border-color', 'red');
            } else {
                $(this).removeAttr('style');
            }
        });
        var sn = $row.find('input.only_alphanumric').each(function() {
            if($(this).val() === '') {
                $(this).css('border-color', 'red');
            } else {
                $(this).removeAttr('style');
            }
        });*/
        /*if(lp.val().length < 0 && sn.val().length < 0 )
        {
            lp.css('background-color', 'red');
            sn.css('background-color', 'red');
            isValid = false;
        }*/

        if(isValid)
        {
            var fields = $row.find('form').serialize();
            $.ajax({
                type: "post",
                url: "/competitive-pricing/save-prices",
                data: fields,
                dataType: "json",
                beforeSend: function () {
                    // $("#basket :input, #basket select").attr("disabled", true);
                },
                success: function (data) {
                    if (data.ch_1 == 'Yes')
                        $row.find('td.ch1_txt').css('background-color', 'lightgreen');
                    if (data.ch_2 == 'Yes')
                        $row.find('td.ch2_txt').css('background-color', 'lightgreen');
                    if (data.ch_3 == 'Yes')
                        $row.find('td.ch3_txt').css('background-color', 'lightgreen');

                    $row.find('td.ch1_txt').html(data.ch_1);
                    $row.find('td.ch2_txt').html(data.ch_2);
                    $row.find('td.ch3_txt').html(data.ch_3);
                },
            });
        }

    });


    $('.tg-kr94').find('input').blur(function (e) {
        e.preventDefault();
        $(this).closest('tr').find('.cp-save').trigger('click');
    });

    $('.tg-kr94').find('select').on('change', function (e) {
        e.preventDefault();
        $(this).closest('tr').find('.cp-save').trigger('click');
    });

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
    $("#export").click(function (event) {
        // var outputFile = 'export'
        var outputFile = window.prompt("What do you want to name your output file (Note: This won't have any effect on Safari)") || 'export';
        outputFile = outputFile.replace('.csv','') + '.csv'

        // CSV
        exportTableToCSV.apply(this, [$('#example23_wrapper > table'), outputFile]);

        // IF CSV, don't do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
    });
    function showfilters(){
        if($('.filters-thead').hasClass('filters-hide')==true){
            $('.filters-thead').removeClass('filters-hide');
        }else{
            $('.filters-thead').addClass('filters-hide');
        }

    }
    $('#table').DataTable( {
        "order": [[ 3, "desc" ]],
        "searching": false,
        "pageLength": 25
    } );
    $('tbody').scroll(function(e) {
        $('thead').css("left", -$("tbody").scrollLeft()); //fix the thead relative to the body scrolling
        $('thead th:nth-child(1)').css("left", $("tbody").scrollLeft()); //fix the first cell of the header
        $('tbody td:nth-child(1)').css("left", $("tbody").scrollLeft()); //fix the first column of tdbody

    });
    $('.mydatepicker').datepicker(
        {
            dateFormat: 'yyyy-mm-dd',//check change
            changeMonth: true,
            changeYear: false
        }
    );
});
