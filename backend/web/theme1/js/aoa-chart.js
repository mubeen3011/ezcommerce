var chart1 = AmCharts.makeChart("sku-chart_22", {
    "type": "serial",
    "theme": "light",
    "dataProvider": lazadaData,
    "valueAxes": [{
        "id": "v1",
        "axisColor": "#FF6600",
        "axisAlpha": 0,
        "dashLength": 4,
        "position": "left"
    }],
    "graphs": [{
        "valueAxis": "v1",
        "bulletSize": 12,
        "lineColor": "#FF6600",
        "customBullet": "/theme1/images/money-icon.png",
        "customBulletField": "customBullet",
        "valueField": "value",
        "title": "Lowest Price",
        "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Seller:[[seller]]</span><br><span style='font-size:14px'>Lowest Price:RM[[value]]</span>",
    },
        {
            "valueAxis": "v1",
            "lineColor": "#B0DE09",
            "bullet": "square",
            "bulletBorderThickness": 1,
            "hideBulletsCount": 30,
            "title": "Sale Price",
            "valueField": "price",
            "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Sale Price:RM[[price]]</span>",
        }],

    "marginTop": 20,
    "marginRight": 70,
    "marginLeft": 40,
    "marginBottom": 20,
    "chartCursor": {
        "graphBulletSize": 1.5,
        "zoomable": false,
        "valueZoomable": true,
        "cursorAlpha": 0,
        "valueLineEnabled": true,
        "valueLineBalloonEnabled": true,
        "valueLineAlpha": 0.2
    },
    "autoMargins": false,
    "dataDateFormat": "YYYY-MM-DD",
    "categoryField": "date",
    "valueScrollbar": {
        "offset": 30
    },
    "categoryAxis": {
        "parseDates": true,
        "axisAlpha": 0,
        "gridAlpha": 0,
        "inside": true,
        "tickLength": 0
    },
    "export": {
        "enabled": true,
        "fileName": "lazada-" + filename,
    }
});
var chart2 = AmCharts.makeChart("sku-chart_24", {
    "type": "serial",
    "theme": "light",
    "dataProvider": ShopeeData,
    "valueAxes": [{
        "id": "v1",
        "axisColor": "#FF6600",
        "axisAlpha": 0,
        "dashLength": 4,
        "position": "left"
    }],
    "graphs": [{
        "valueAxis": "v1",
        "bulletSize": 12,
        "lineColor": "#FF6600",
        "customBullet": "/theme1/images/money-icon.png",
        "customBulletField": "customBullet",
        "valueField": "value",
        "title": "Lowest Price",
        "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Seller:[[seller]]</span><br><span style='font-size:14px'>Lowest Price:RM[[value]]</span>",
    },
        {
            "valueAxis": "v1",
            "lineColor": "#B0DE09",
            "bullet": "square",
            "bulletBorderThickness": 1,
            "hideBulletsCount": 30,
            "title": "Sale Price",
            "valueField": "price",
            "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Sale Price:RM[[price]]</span>",
        }],

    "marginTop": 20,
    "marginRight": 70,
    "marginLeft": 40,
    "marginBottom": 20,
    "chartCursor": {
        "graphBulletSize": 1.5,
        "zoomable": false,
        "valueZoomable": true,
        "cursorAlpha": 0,
        "valueLineEnabled": true,
        "valueLineBalloonEnabled": true,
        "valueLineAlpha": 0.2
    },
    "autoMargins": false,
    "dataDateFormat": "YYYY-MM-DD",
    "categoryField": "date",
    "valueScrollbar": {
        "offset": 30
    },
    "categoryAxis": {
        "parseDates": true,
        "axisAlpha": 0,
        "gridAlpha": 0,
        "inside": true,
        "tickLength": 0
    },
    "export": {
        "enabled": true,
        "fileName": "shopee-" + filename,
    }
});
var chart3 = AmCharts.makeChart("sku-chart_24", {
    "type": "serial",
    "theme": "light",
    "dataProvider": ElStreetData,
    "valueAxes": [{
        "id": "v1",
        "axisColor": "#FF6600",
        "axisAlpha": 0,
        "dashLength": 4,
        "position": "left"
    }],
    "graphs": [{
        "valueAxis": "v1",
        "bulletSize": 12,
        "lineColor": "#FF6600",
        "customBullet": "/theme1/images/money-icon.png",
        "customBulletField": "customBullet",
        "valueField": "value",
        "title": "Lowest Price",
        "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Seller:[[seller]]</span><br><span style='font-size:14px'>Lowest Price:RM[[value]]</span>",
    },
        {
            "valueAxis": "v1",
            "lineColor": "#B0DE09",
            "bullet": "square",
            "bulletBorderThickness": 1,
            "hideBulletsCount": 30,
            "title": "Sale Price",
            "valueField": "price",
            "balloonText": "<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:14px'>Sale Price:RM[[price]]</span>",
        }],

    "marginTop": 20,
    "marginRight": 70,
    "marginLeft": 40,
    "marginBottom": 20,
    "chartCursor": {
        "graphBulletSize": 1.5,
        "zoomable": false,
        "valueZoomable": true,
        "cursorAlpha": 0,
        "valueLineEnabled": true,
        "valueLineBalloonEnabled": true,
        "valueLineAlpha": 0.2
    },
    "autoMargins": false,
    "dataDateFormat": "YYYY-MM-DD",
    "categoryField": "date",
    "valueScrollbar": {
        "offset": 30
    },
    "categoryAxis": {
        "parseDates": true,
        "axisAlpha": 0,
        "gridAlpha": 0,
        "inside": true,
        "tickLength": 0
    },
    "export": {
        "enabled": true,
        "fileName": "11street-" + filename,
    }
});


// pie charts
var chart = AmCharts.makeChart("sku-pchart_22", {
    "type": "pie",
    "theme": "light",
    "dataProvider": lazadaPData,
    "valueField": "value",
    "titleField": "seller",
    "outlineAlpha": 0.4,
    "depth3D": 15,
    "balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
    "angle": 30,
    "export": {
        "enabled": true,
        "fileName": "lazada-seller-" + filename,
    }
});

var chart2 = AmCharts.makeChart("sku-pchart_24", {
    "type": "pie",
    "theme": "light",
    "dataProvider": ShopeePData,
    "valueField": "value",
    "titleField": "seller",
    "outlineAlpha": 0.4,
    "depth3D": 15,
    "balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
    "angle": 30,
    "export": {
        "enabled": true,
        "fileName": "shopee-seller-" + filename,
    }
});
var chart3 = AmCharts.makeChart("sku-pchart_24", {
    "type": "pie",
    "theme": "light",
    "dataProvider": ElStreetPData,
    "valueField": "value",
    "titleField": "seller",
    "outlineAlpha": 0.4,
    "depth3D": 15,
    "balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
    "angle": 30,
    "export": {
        "enabled": true,
        "fileName": "11street-seller-" + filename,
    }
});
