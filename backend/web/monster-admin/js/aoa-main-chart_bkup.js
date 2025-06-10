// sales dashboards charts


$(function () {
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


    // sales wise marketplace
    var doughnutChart = echarts.init(document.getElementById('sales-donute-0'));
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
            , data: ['FC','GC','KA','MCC','Others','PC']
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
        , color: ["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"]
        , calculable: false
        , series: [
            {
                name: 'Lazada'
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
                , data: [
                {
                    value: salesMarketplace['lazada']['fc']
                    , name: 'FC'
                },
                {
                    value: salesMarketplace['lazada']['gc']
                    , name: 'GC'
                }
                , {
                    value: salesMarketplace['lazada']['ka']
                    , name: 'KA'
                }
                , {
                    value: salesMarketplace['lazada']['mcc']
                    , name: 'MCC'
                },{
                    value: salesMarketplace['lazada']['others']
                    , name: 'Others'
                },{
                    value: salesMarketplace['lazada']['pc']
                    , name: 'PC'
                }

            ]
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

    // 2
    var doughnutChart = echarts.init(document.getElementById('sales-donute-1'));
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
            ,  data: ['FC','GC','KA','MCC','Others','PC']
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
        , color: ["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"]
        , calculable: false
        , series: [
            {
                name: 'Shopee'
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
                , data: [
                {
                    value: salesMarketplace['shopee']['fc']
                    , name: 'FC'
                },
                {
                    value: salesMarketplace['shopee']['gc']
                    , name: 'GC'
                }
                , {
                    value: salesMarketplace['shopee']['ka']
                    , name: 'KA'
                }
                , {
                    value: salesMarketplace['shopee']['mcc']
                    , name: 'MCC'
                },{
                    value: salesMarketplace['shopee']['others']
                    , name: 'Others'
                },{
                    value: salesMarketplace['shopee']['pc']
                    , name: 'PC'
                }

            ]
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

    //3
    var doughnutChart = echarts.init(document.getElementById('sales-donute-2'));
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
            , data: ['FC','GC','KA','MCC','Others','PC']
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
        , color: ["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"]
        , calculable: false
        , series: [
            {
                name: 'Street'
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
                , data:  [
                {
                    value: salesMarketplace['street']['fc']
                    , name: 'FC'
                },
                {
                    value: salesMarketplace['street']['gc']
                    , name: 'GC'
                }
                , {
                    value: salesMarketplace['street']['ka']
                    , name: 'KA'
                }
                , {
                    value: salesMarketplace['street']['mcc']
                    , name: 'MCC'
                },{
                    value: salesMarketplace['street']['others']
                    , name: 'Others'
                },{
                    value: salesMarketplace['street']['pc']
                    , name: 'PC'
                }

            ]
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


    //4
    var doughnutChart = echarts.init(document.getElementById('sales-donute-3'));
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
            , data: ['FC','GC','KA','MCC','Others','PC']
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
        , color: ["#4CB7A5", "#82CDC0", "#51CBBD", "#03A89E","#19FBED", "#7AC5CD"]
        , calculable: false
        , series: [
            {
                name: 'Shop'
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
                , data: [
                {
                    value: salesMarketplace['shop']['fc']
                    , name: 'FC'
                },
                {
                    value: salesMarketplace['shop']['gc']
                    , name: 'GC'
                }
                , {
                    value: salesMarketplace['shop']['ka']
                    , name: 'KA'
                }
                , {
                    value: salesMarketplace['shop']['mcc']
                    , name: 'MCC'
                },{
                    value: salesMarketplace['shop']['others']
                    , name: 'Others'
                },{
                    value: salesMarketplace['shop']['pc']
                    , name: 'PC'
                }

            ]
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