// sales dashboards charts


$(function () {
    "use strict";
    //// in main dashboard sales by shop/ marketplace per month
    Morris.Bar({
        element: 'sales-by-shop-per-month-graph',
        data: sales_by_shop_per_month.data,
        xkey: 'y',
        ykeys: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        labels: sales_by_shop_per_month.labels,//['Amazon', 'Ebay', 'Walmart','Prestashop'],
        barColors:sales_by_shop_per_month.colors ,//['#55ce63', '#2f3d4a', '#009efb','#7460EE','#FF2D74'],
        hideHover: 'auto',
        gridLineColor: '#eef0f2',
        resize: true,
       // ymax: 20000
    });

    /////////////
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
    }).on('draw', function (data) {
        if (data.type === 'bar') {
            data.element.attr({
                style: 'stroke-width: 15px'
            });
        }
    });

    $.each(salesMarketplace, function(key_ar, value_ar) {
        var available_colors=["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"];
        var cat_list=new Array();  //categories name
        var prepared_values=new Array(); // cat names and values
        $.each(value_ar,function (item_ar, index_ar) {
            cat_list.push(item_ar);
            prepared_values.push({'name':item_ar,'value':index_ar});
        });
        // sales wise marketplace
        var doughnutChart = echarts.init(document.getElementById('sales-donute-'+key_ar));
        // specify chart configuration item and data
        var option = {
            tooltip: {
                trigger: 'item'
                , formatter: function (params) {
                    const yea = params[1];
                    const num = params[2];
                    return yea + ": " + nFormatter(num);
                }
            }
            , legend: {
                show: false
                , data: cat_list //['FC','GC','KA','MCC','Others','PC']
            }
            , toolbox: {
                show: false
                , feature: {
                    dataView: {
                        show: false
                        , readOnly: false
                    }
                    , magicType: {
                        show: false
                        , type: ['pie', 'funnel']
                        , option: {
                            funnel: {
                                x: '25%'
                                , width: '50%'
                                , funnelAlign: 'center'
                                , max: 1548
                            }
                        }
                    }
                    , restore: {
                        show: true
                    }
                    , saveAsImage: {
                        show: true
                    }
                }
            }
            , color:  available_colors.slice(0, cat_list.length) //; ["red", "green"]//["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"]
            , calculable: false
            , series: [
                {
                    name: key_ar //'Lazada'
                    , type: 'pie'
                    , radius: ['45%', '65%']
                    , itemStyle: {
                        normal: {
                            label: {
                                show: true
                            }
                            , labelLine: {
                                show: true
                            }
                        }
                        , emphasis: {
                            label: {
                                show: false
                                , position: 'center'
                                , textStyle: {
                                    fontSize: '25'
                                    , fontWeight: 'bold'
                                }
                            }
                        }
                    }
                    , data: prepared_values
                }
            ]
        };
// use configuration item and data specified to show chart
        doughnutChart.setOption(option, true), $(function () {
            function resize() {
                setTimeout(function () {
                    doughnutChart.resize()
                }, 100)
            }

            // $(window).on("resize", resize), $(".sidebartoggler").on("click", resize)
        });
        ///chart place

    }); // main loop ends


/// sales by shop /marketplace per month on main dashboard
    // Morris bar chart




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

//pie graph of marketplace/channel contribution in percent on main dashboard
if (sales_by_marketplace.hasOwnProperty('sales')) {
    let pie_colors=['#009efb','#1280AC','#94D0D7','#0000FF','#E67E22','#7460EE','#FF4261'];
    let sales=sales_by_marketplace.sales;
    let make_array=[];
    sales.forEach(function (item, index) {
        let make={
            y:item.percent_in_total_sale,
            'color':pie_colors[index],
            'label':item.channel
        };
        make_array.push(make);
    });
    var chart = new CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        title: {
            text: ""
        },
        data: [{
            type: "pie",
            startAngle: 240,
            yValueFormatString: "##0.00\"%\"",
            indexLabel: "{label} {y}",
            dataPoints:make_array
        }]
    });
    chart.render();
    $('.canvasjs-chart-credit').css('display','none');
}

