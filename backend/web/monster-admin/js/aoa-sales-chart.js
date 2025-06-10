// sales dashboards charts
if(typeof setTarget !== 'undefined') {
    var salesTarget = AmCharts.makeChart("sales-target-div", {
        "theme": "light",
        "type": "gauge",
        "axes": [{
            "topTextFontSize": 10,
            "axisColor": "#31d6ea",
            "axisThickness": 1,
            "endValue": setTarget,
            "gridInside": true,
            "inside": true,
            "radius": "60%",
            "valueInterval": setTarget,
            "tickColor": "#67b7dc",
            "startAngle": -90,
            "endAngle": 90,
            "bottomText": "USD " + setTarget,
            // "bottomTextYOffset": -20,
            //"unit": "%",
            "bandOutlineAlpha": 0,
            "bands": [{
                "color": "#0080ff",
                "endValue": setTarget,
                "innerRadius": "105%",
                "radius": "170%",
                "gradientRatio": [0.5, 0, -0.5],
                "startValue": 0
            }, {
                "color": "#3cd3a3",
                "endValue": setVal,
                "innerRadius": "105%",
                "radius": "170%",
                "gradientRatio": [0.5, 0, -0.5],
                "startValue": 0
            }],

        }],

        "arrows": [
            {
                "value": setVal
            }
        ]
    });
}
if(typeof monthlySales !== 'undefined') {
    var monthChart = AmCharts.makeChart("month-chart-div", {
        "type": "serial",
        "theme": "light",
        "marginRight": 80,
        "autoMarginOffset": 20,
        "marginTop": 7,
        "valueAxes": [{
            "autoGridCount":false,
            "guides": [{
                "dashLength": 6,
                "inside": true,
                "label": "last 3 months day avg.:"+ currency +avgDaySales,
                "lineAlpha": 1,
                "value": avgDaySales
            },{
                "dashLength": 2,
                "inside": true,
                "label": "last 6 months day avg.:" + currency +avgSixMonthDaySales,
                "lineAlpha": 1,
                "value": avgSixMonthDaySales
            }],
            "position": "left"
        }],
        "balloon": {
            "borderThickness": 1,
            "shadowAlpha": 0
        },
        "graphs": [{
            "bullet": "round",
            "id": "g1",
            "bulletBorderAlpha": 1,
            "bulletColor": "#298CB4",
            "lineColor":"#298CB4",
            "bulletSize": 7,
            "lineThickness": 2,
            "title": "Sales Value",
            "type": "smoothedLine",
            "useLineColorForBulletBorder": true,
            "valueField": "value",
            "balloonText": "<span style='font-size:12px;'>"+currency +" [[value]]</span>",
            "fillColors":"#298CB4"

        }],
        "chartScrollbar": {},
        "chartCursor": {
            "pan": true,
            "valueLineEnabled": true,
            "valueLineBalloonEnabled": true,
            "cursorAlpha": 1,
            "cursorColor": "#258cbb",
            "limitToGraph": "g1",
            "valueLineAlpha": 0.2,
            "valueZoomable": true
        },

        "categoryField": "date",
        "categoryAxis": {
            "parseDates": true,
            "dashLength": 1,
            "minorGridEnabled": true
        },
        "export": {
            "enabled": true
        },
        "dataProvider": monthlySales
    });
}
if(typeof mpSales !== 'undefined') {
    var shopChart = AmCharts.makeChart("shop-chart-div", {
        "theme": "light",
        "type": "serial",
        "dataProvider": mpSales,
        "valueAxes": [{
            "unit": "",
            "position": "left",
            "title": "",
        }],
        "startDuration": 1,
        "graphs": [{
            "balloonText": "[[category]]: <b>"+ currency +" [[value]]</b>",
            "fillAlphas": 0.9,
            "lineAlpha": 0.2,
            "title": "Sales",
            "type": "column",
            "clustered": false,
            "columnWidth": 0.5,
            "valueField": "sales",
            "labelText": currency + "[[value]]",
            "showHandOnHover": clickEv,
            "fillColors":"#298CB4"
        }],
        "listeners": [{
            "event": "clickGraphItem",
            "method": function (e) {
                //var cat = encodeURI(e.item.category);
                if(clickEv) {
                    if (e.item.category=='Offline'){
                        return false;
                    }
                    var cat = e.item.category;
                    location.href = encodeURI("/sales/report-by-marketplace?mp=" + cat.toLowerCase() /*+ "&date="+postDate*/);
                }

            }
        }],
        "plotAreaFillAlphas": 0.1,
        "categoryField": "marketplace",
        "categoryAxis": {
            "gridPosition": "start"
        },
        "export": {
            "enabled": false
        }

    });
}
if(typeof yearSales !== 'undefined') {
    //console.log(yearSales);
    var shopChart = AmCharts.makeChart("year-chart-div", {
        "theme": "light",
        "type": "serial",
        "dataProvider": yearSales,
        "valueAxes": [{
            "unit": "",
            "position": "left",
            "title": "",
        }],
        "startDuration": 1,
        "graphs": [{
            "balloonText": "[[category]]:"+" <b> "+ currency +" [[value]]</b>",
            "fillAlphas": 0.9,
            "lineAlpha": 0.2,
            "title": "Sales",
            "type": "column",
            "clustered": false,
            "columnWidth": 0.5,
            "valueField": "sales",
            "labelText": currency + " [[value]]",
            "showHandOnHover": clickEv,
            "fillColors":"#1280AC"
        }],
        "listeners": [{
            "event": "clickGraphItem",
            "method": function (e) {
                //var cat = encodeURI(e.item.category);
                /*if(clickEv) {
                    var cat = e.item.category;
                    ncat = cat.toLowerCase().replace("quarter ","")
                    location.href = encodeURI("/sales/report-by?type="+cType+"&month=" + ncat + "&year="+cYear+"&mp="+cMarketplace+"&shop="+cShop+"&cat="+cCategory);
                }*/

            }
        }],
        "plotAreaFillAlphas": 0.1,
        "categoryField": "monthly",
        "categoryAxis": {
            "gridPosition": "start"
        },
        "export": {
            "enabled": false
        }

    });
}
//target sales graph
if(targets){
new Chartist.Bar('.sales-target-chart', {
    //labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul','Aug','Sep','Oct','Nov','Dec'],
    labels: targets.display,
    series: [
        targets.sales,
        targets.targets
    ]
}, {
    high:targets.max_sales
    , low: 1
    , fullWidth: true
    , plugins: [
        Chartist.plugins.tooltip()
    ]
    ,
    axisX: {
        // On the x-axis start means top and end means bottom
        position: 'bottom'
    },

    axisY: {
        // On the y-axis start means left and end means right

    }
});
}
/////////////////////
$(document).ready(function(){

    //// in main dashboard sales by shop/ marketplace per month
    Morris.Bar({
        element: 'sales-by-shop-per-month-graph',
        data: sales_by_shop_per_month.data,
        xkey: 'y',
        ykeys: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        labels: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        barColors:['#298CB4', '#2F3D4A', '#009efb','#7460EE','#FF2D74'],
        hideHover: 'auto',
        gridLineColor: '#eef0f2',
        resize: true
    });
    ////////////////
    $('#morris-month-select').change(function () {
        var data = $('#quarter_graph_params').serialize();
        $.ajax({
            type: "POST",
            url: "/sales/get-weeks",
            data:  data,
            dataType: "json",
            success: function(msg)
            {
                //console.log(msg);
                var Weeks = new Array();
                var WeekOptions='';
                WeekOptions += '<option></option>';
                var a = 1;
                $.each((msg), function(index, element) {
                    console.log(element);
                    WeekOptions += '<option value="'+element.WEEK+'">Week '+a+'</option>';
                    a++;
                });
                if (a==1)
                {
                    $('#morris-weeks-dropdown').text('');
                    $('#morris-weeks-dropdown').html('<option>Records not found</option>');
                    $('.morris-week').removeClass('hide');
                }else{
                    $('#morris-weeks-dropdown').text('');
                    $('#morris-weeks-dropdown').html(WeekOptions);
                    $('.morris-week').removeClass('hide');
                }



            },
            beforeSend: function()
            {

            }
        });
    })
    var morris_bar_chart = Morris.Bar({
        element: 'morris-bar-chart',
        data: default_graph_sales_quarter,
        xkey: 'y',
        ykeys: ['a','b','c'],
        labels: ['LZD', 'ELS','SHP'],
        hideHover: 'auto',
        resize: true
    });
    $("#update_graph").on("click", function (e) {
        var data = $('#quarter_graph_params').serialize();
        $.ajax({
            type: "POST",
            url: "/sales/update-quarter-data",
            data:  data,
            dataType: "json",
            success: function(msg)
            {
                console.log(msg);
                $.unblockUI({
                    onUnblock: function(){
                    }
                });

                var Ykeys= new Array();
                $.each($.parseJSON(msg.yKeys), function(index, element) {
                    Ykeys.push(element);
                });
                var Labels = new Array();
                $.each($.parseJSON(msg.labels), function(index, element) {
                    Labels.push(element);
                });
                /*var Weeks = new Array();
                var WeekOptions='';
                var a = 1;
                WeekOptions += '<option></option>';
                $.each($.parseJSON(msg.Weeks), function(index, element) {
                    WeekOptions += '<option value="'+index+'">Week '+a+'</option>';
                    a++;
                });
                if(a>1){
                    $('#morris-weeks-dropdown').html(WeekOptions);
                    $('.morris-week').removeClass('hide');
                }*/


                //alert(WeekOptions);
                //console.log(Labels);
                $("#morris-bar-chart").empty();
                Morris.Bar({
                    element: 'morris-bar-chart',
                    data: $.parseJSON(msg.bars_data),
                    xkey: 'y',
                    ykeys: Ykeys,
                    labels: Labels,
                    hideHover: 'auto',
                    //gridLineColor: '#eef0f2',
                    resize: true
                });
            },
            beforeSend: function()
            {
                $.blockUI({
                    message: $('#displayBox')
                });
            }
        });
    })
})
