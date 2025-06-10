// sales dashboards charts


$(function () {
    "use strict";
// template chart
    new Chartist.Bar('.total-sales', {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec']
        , series: [
            
            StockValueB
            
           
           
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

});