function cb(start, end) {
    if(isPstDat == "")
    {
        $('.input-daterange-datepicker').val(start.format('YYYY-MM-DD') +  ' to '  + end.format('YYYY-MM-DD'));
    }
}
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
/*$(".input-daterange-datepicker").on('change', function () {
    var enc = window.btoa($('.input-daterange-datepicker').val());
    $('.input-daterange-datepicker').val(enc);
    $("#dfilter").submit();
});*/
$(function () {

    //////
    Morris.Bar({
        element: 'sales-by-shop-per-month-graph',
        data: sales_by_shop_per_month.data,
        xkey: 'y',
        ykeys: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        labels: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        barColors:sales_by_shop_per_month.colors,//['#298CB4', '#2f3d4a', '#009efb','#7460EE','#FF2D74'],
        hideHover: 'auto',
        gridLineColor: '#eef0f2',
        resize: true,
        // ymax: 20000
    });
    /**************************category sale graph data get on doc load**********************************/
    // get category sales chart data on document load
    $.ajax({
        type: "POST",
        url: '/sales/sales-by-category/',
        data: {mp:mp},
        dataType: 'json',
        beforeSend: function(){
            $('#morris-area-chart').html('<span class="fa fa-spinner fa-spin fa-10x text-center"></span>');
        },
        success: function(data){
            if((data.hasOwnProperty('dataset')))
            {
                $('#morris-area-chart').html(''); // clear chart area first
                let cats = [];
                let cat_names=[];
                let dots_fill=[];
                $.each(data.categories, function(i, category_name) {
                    cats.push(i);
                    cat_names.push(category_name);
                    dots_fill.push('<li><h6 style="font-size:10px"><i class="fa fa-circle m-r-5 text-inverse" style="color:' + data.colors[i] + ' !important"></i>' + category_name + '</h6></li>');
                });

                let colorList=[];
                $.each(data.colors, function(catAlias, HexaColor) {
                    colorList.push(HexaColor);
                });
                $('.categories-colors-dots-span').html(dots_fill);
                populate_cat_chart(data.dataset,cats,cat_names,colorList); // category chart
            } else {
                $('#morris-area-chart').html('<h4 class="text-center ">No Record Found</h4>');
            }


        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            $('#morris-area-chart').html('<h4 class="text-center">' + errorThrown + '</h4>');
            display_notice('failure',errorThrown);
        }
    });
    /**************************category sale graph filter ajax call **********************************/
    /// sales by cat filter when applies
    $('.sales_by_cat_filter_box').submit(function(e){
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function(){
                $('.btn-cat-filter').html('<span class="fa fa-spinner fa-spin"></span>');
            },
            success: function(data){
                if((data.hasOwnProperty('dataset')))
                {
                    $('#morris-area-chart').html(''); // clear chart area first
                    let cats = [];
                    let cat_names=[];
                    let dots_fill=[];
                    $.each(data.categories, function(i, category_name) {
                        cats.push(i);
                        cat_names.push(category_name);
                        dots_fill.push('<li><h6 style="font-size:10px"><i class="fa fa-circle m-r-5 text-inverse" style="color:' + data.colors[i] + ' !important"></i>' + category_name + '</h6></li>');
                    });

                    let colorList=[];
                    $.each(data.colors, function(catAlias, HexaColor) {
                        colorList.push(HexaColor);
                    });
                    $('.categories-colors-dots-span').html(dots_fill);
                    populate_cat_chart(data.dataset,cats,cat_names,colorList); // category chart


                } else{
                    display_notice('failure','unable to load data fo given filter');
                }
                $('.btn-cat-filter').html('Apply');
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {

                display_notice('failure',errorThrown);
                $('.btn-cat-filter').html('Apply');
            }
        });
    });
    /**************************category sale graph populate**********************************/
    function populate_cat_chart(dataset,cats,cat_names,colorList)
    {
        "use strict";
        Morris.Area({
            element: 'morris-area-chart',
            data: dataset,
            xkey: 'period',
            ykeys: cats,
            labels: cat_names,
            pointSize: 3,
            xLabels: "month",
            fillOpacity: 0,
            pointStrokeColors:colorList,
            behaveLikeLine: true,
            gridLineColor: '#e0e0e0',
            lineWidth: 3,
            hideHover: 'auto',
            lineColors: colorList,
            resize: true
        });
    }
    "use strict";
// template chart
    new Chartist.Bar('.total-sales', {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec']
        , series: [
            salesForcastD
            , salesForcastA
            , salesForcastB
        ]
    }, {
        high: maxv
        , low: minv
        , fullWidth: true
        , plugins: [
            Chartist.plugins.tooltip()]
        , stackBars: false
        , axisX: {
            showGrid: true
        }
        , axisY: {
            labelInterpolationFnc: function (value) {
                // let divider=maxv < 1000 ? 100:1000;
                return (value / 1000) + 'k';
            }
        }
    }).on('draw', function (data) {if (data.type === 'bar') {
            data.element.attr({
                style: 'stroke-width: 15px'
            });
        }
    });







});


function nFormatter(num, digits) {
    var si = [
        {value: 1, symbol: ""},
        {value: 1E3, symbol: "k"},
        {value: 1E6, symbol: "M"},
        {value: 1E9, symbol: "G"},
        {value: 1E12, symbol: "T"},
        {value: 1E15, symbol: "P"},
        {value: 1E18, symbol: "E"}
    ];
    var rx = /\.0+$|(\.[0-9]*[1-9])0+$/;
    var i;
    for (i = si.length - 1; i > 0; i--) {
        if (num >= si[i].value) {
            break;
        }
    }
    return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
}

$('.show_items').on('click',function(){
    let id_pk=$(this).attr('data-id-pk');
    $(this).toggleClass('fa-plus fa-minus')
    $('.child_row_' + id_pk).toggle();
});
//for top 10 cntributors
$('.show_items_tp').on('click',function(){
    let id_pk=$(this).attr('data-id-pk');
    $(this).toggleClass('fa-plus fa-minus')
    $('.child_row_tp_' + id_pk).toggle();
});
////filter
$('#sales_by_shop_filter_btn').click(function(){
    $('.sales_by_shop_filter_box').toggle();
});

$('#sales_by_cat_filter_btn').click(function(){
    $('.sales_by_cat_filter_box').toggle();
});

/// scroller
$('#owl-carousel-product').owlCarousel({
    loop:true,
    margin:10,
    nav:true,
    // items:3,
    dots: false,
    responsiveClass:true,
    navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
    responsive:{
        0:{
            items:1
        },
        600:{
            items:3
        },
        1000:{
            items:3
        }
    }
});

///////
$('#owl-carousel-dealers').owlCarousel({
    loop:true,
    margin:10,
    nav:true,
    items:1,
    dots: false,
    navText:["<div class='nav-btn next-slide'></div>"]
});
////////////////
$('#owl-carousel-platform').owlCarousel({
    loop:true,
    margin:10,
    nav:true,
    //items:2,
    dots: false,
    responsiveClass:true,
    navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
    responsive:{
        0:{
            items:1,
        },
        600:{
            items:1,
        }
    }
});

/// save graphs as image
// save image graph shop/marketplace
$('.save_image_bg').click(function() {
    html2canvas(document.getElementById('per_month_all_shops_graph')).then(canvas => {
        var w = document.getElementById("per_month_all_shops_graph").offsetWidth;
        var h = document.getElementById("per_month_all_shops_graph").offsetHeight;
        save_image_as(canvas.toDataURL("image/jpeg"),new Date().toISOString().slice(0, 10) + 'mp_bar.jpg');
    }).catch(function(e) {
        console.log(e.message);
    });

});
$('.save_image_cat_line').click(function() {
    html2canvas(document.getElementById('sales-by-cat-line-graph')).then(canvas => {
        var w = document.getElementById("sales-by-cat-line-graph").offsetWidth;
        var h = document.getElementById("sales-by-cat-line-graph").offsetHeight;
        save_image_as(canvas.toDataURL("image/jpeg"),new Date().toISOString().slice(0, 10) + 'cat_line.jpg');
    }).catch(function(e) {
        console.log(e.message);
    });


});

function save_image_as(uri, filename) {

    var link = document.createElement('a');
    if (typeof link.download === 'string') {

        link.href = uri;
        link.download = filename;

        //Firefox requires the link to be in the body
        document.body.appendChild(link);

        //simulate click
        link.click();

        //remove the link when done
        document.body.removeChild(link);

    } else {

        window.open(uri);

    }
}
//////////////////////////

