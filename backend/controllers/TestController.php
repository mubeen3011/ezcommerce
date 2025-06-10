<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/22/2019
 * Time: 2:17 PM
 */
namespace backend\controllers;
use backend\util\AmazonSellerPartnerUtil;
use backend\util\AmazonspUtil;
use backend\util\AmazontestUtil;
use backend\util\BackmarketUtil;
use backend\util\BlueExUtil;
use backend\util\CatalogUtil;
use backend\util\CourierUtil;
use backend\util\DarazUtil;
use backend\util\GlobalMobileUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\LCSUtil;
use backend\util\LogUtil;
use backend\util\MagentoUtil;
use backend\util\OrderUtil;
use backend\util\PrestashopUtil;
use backend\util\ProductsUtil;
use backend\util\QuickbookUtil;
use backend\util\ShopeeUtil;
use backend\util\TestUtil;
use backend\util\UpsUtil;
use backend\util\WarehouseUtil;
use common\models\Category;
use common\models\ChannelsProducts;
use common\models\Couriers;
use common\models\CustomersAddress;
use common\models\EzcomToWarehouseProductSync;
use common\models\EzcomToWarehouseSync;
use common\models\GlobalMobilesCatMapping;
use common\models\OrderItems;
use common\models\Orders;
use common\models\ProductCategories;
use common\models\Products;
use common\models\Settings;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use yii;
use backend\util\AmazonUtil;
use backend\util\DealsUtil;
use backend\util\SkuVault;
use backend\util\WalmartUtil;
use common\models\Channels;
use Dompdf\Options;
use Dompdf\Dompdf;
class TestController extends \backend\controllers\MainController{

    public static function actionCreatePoSample(){
        $json = '{  
   "PoNumber":"String",
   "SupplierName":"String",
   "OrderDate":"0001-01-01T00:00:00.0000000Z",
   "TermsName":"String",
   "OrderCancelDate":"0001-01-01T00:00:00.0000000Z",
   "Payments":[  
      {  
         "PaymentName":"String",
         "Amount":0,
         "Note":"String"
      }
   ],
   "SentStatus":"Undefined",
   "PaymentStatus":"Undefined",
   "ShippingCarrierClass":{  
      "CarrierName":"String",
      "ClassName":"String"
   },
   "ShipToWarehouse":"String",
   "ShipToAddress":"String",
   "ArrivalDueDate":"0001-01-01T00:00:00.0000000Z",
   "RequestedShipDate":"0001-01-01T00:00:00.0000000Z",
   "TrackingInfo":"String",
   "PublicNotes":"String",
   "PrivateNotes":"String",
   "LineItems":[  
      {  
         "SKU":"String",
         "Quantity":0,
         "QuantityTo3PL":0,
         "Cost":0,
         "PrivateNotes":"String",
         "PublicNotes":"String",
         "Variant":"String",
         "Identifier":"String"
      }
   ],
   "TenantToken":"String",
   "UserToken":"String"
}';
        echo '<pre>';
        print_r(json_decode($json,1));
        die;
    }
    public function actionCreatePo(){
        SkuVault::createPO();
    }
    public function actionPdf()
    {
        $dompdf = new Dompdf();
        $html='<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>A simple, clean, and responsive HTML invoice template</title>
    
    <style>
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        font-size: 16px;
        line-height: 24px;
        font-family: \'Helvetica Neue\', \'Helvetica\', Helvetica, Arial, sans-serif;
        color: #555;
    }
    
    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
    }
    
    .invoice-box table td {
        padding: 5px;
        vertical-align: top;
    }
    
    .invoice-box table tr.total td:nth-child(2) {
        text-align: right;
    }
    
    .invoice-box table tr.top table td {
        padding-bottom: 20px;
    }
    
    .invoice-box table tr.top table td.title {
        font-size: 45px;
        line-height: 45px;
        color: #333;
    }
    
    .invoice-box table tr.information table td {
        padding-bottom: 40px;
    }
    
    .invoice-box table tr.heading td {
        background: #eee;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    .invoice-box table tr.heading td {
        background: none;
        border: 1px solid black;
        font-weight: bold;
    }
    .invoice-box table tr.details td {
        padding-bottom: 20px;
    }
    
    .invoice-box table tr.item td{
        border-bottom: 1px solid #eee;
    }
    
    .invoice-box table tr.item.last td {
        border-bottom: none;
    }
    
    .invoice-box table tr.total td:nth-child(2) {
       /* border-top: 2px solid #eee;*/
        font-weight: bold;
    }
    
    @media only screen and (max-width: 600px) {
        .invoice-box table tr.top table td {
            width: 100%;
            display: block;
            text-align: center;
        }
        
        .invoice-box table tr.information table td {
            width: 100%;
            display: block;
            text-align: center;
        }
    }
    
    /** RTL **/
    .rtl {
        direction: rtl;
        font-family: Tahoma, \'Helvetica Neue\', \'Helvetica\', Helvetica, Arial, sans-serif;
    }
    
    .rtl table {
        text-align: right;
    }
    
    .rtl table tr td:nth-child(2) {
        text-align: left;
    }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td >
                                26345 lawrence dearborn heights, MI 4812
                            </td>
                            
                           
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                        
                            <td colspan="2">
                                Ship To:104 Shobe Lane,Clifton<br>
, CO , 81520 , US
                            </td>
                            
                        </tr>
                    </table>
                </td>
                <td colspan="2">
                    <table>
                        <tr>
                            
                             <td>
                                Order #: 123<br>
                                Date: January 1, 2015<br>
                                Ship Date: February 1, 2015
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>
                    Item
                </td>
                <td>
                    Description
                </td><td>
                    Price
                </td>
                <td>
                    Qty
                </td>
            </tr>
            
            <tr class="item">
                <td>
                    Website design
                </td>
                <td>
                    Adidas Hybrid 100 Boxing and Kickboxing Gloves for Women & Men
                </td>
                <td>
                    $300.00
                </td>
                <td>
                    2
                </td>
            </tr>
            
            <tr class="item">
                <td>
                    Hosting (3 months)
                </td>
                 <td>
                    Adidas Hybrid 100 Boxing and Kickboxing Gloves for Women & Men
                </td>
                <td>
                    $300.00
                </td>
                <td>
                    2
                </td>
            </tr>
            
            <tr class="item last">
                <td>
                    Domain name (1 year)
                </td>
                 <td>
                    Adidas Hybrid 100 Boxing and Kickboxing Gloves for Women & Men
                </td>
                <td>
                    $300.00
                </td>
                <td>
                    2
                </td>
            </tr>
            
           
            <tr class="total">
                <td ></td>
                <td colspan="2"> Sub Total:</td>
                
                <td>
                    $385.00
                </td>
            </tr>
            <tr class="total">
                <td ></td>
                <td colspan="2">Tax:</td>
                
                <td>
                    $385.00
                </td>
            </tr>
            <tr class="total">
                <td></td>
                <td colspan="2">shipping:</td>
                
                <td>
                    $385.00
                </td>
            </tr>
            <tr class="total">
                <td ></td>
                <td colspan="2">Total:</td>
                
                <td>
                    $385.00
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
';
     //  echo $html; die();
        $dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
        $dompdf->render();

// Output the generated PDF to Browser
        //$dompdf->stream();
        $pdf = $dompdf->output();      // gets the PDF as a string
        error_reporting(E_ERROR | E_PARSE);
        $result=file_put_contents("shipping-labels/bilalss.pdf", $pdf);
        if($result)
            echo "found";
        else
            echo "not";
    }
    public function actionPdf_bootstrap()
    {
        //return $this->render('index');
        $dompdf = new Dompdf();
      // $html=$this->render('index');
        $html='<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">

    <title>Responsive Invoice template - Bootsnipp.com</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <style type="text/css">
    #invoice{
    padding: 30px;
}

.invoice {
    position: relative;
    background-color: #FFF;
    min-height: 680px;
    padding: 15px
}

.invoice header {
    padding: 10px 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #3989c6
}

.invoice .company-details {
    text-align: right
}

.invoice .company-details .name {
    margin-top: 0;
    margin-bottom: 0
}

.invoice .contacts {
    margin-bottom: 20px
}

.invoice .invoice-to {
    text-align: left
}

.invoice .invoice-to .to {
    margin-top: 0;
    margin-bottom: 0
}

.invoice .invoice-details {
    text-align: right
}

.invoice .invoice-details .invoice-id {
    margin-top: 0;
    color: #3989c6
}

.invoice main {
    padding-bottom: 50px
}

.invoice main .thanks {
    margin-top: -100px;
    font-size: 2em;
    margin-bottom: 50px
}

.invoice main .notices {
    padding-left: 6px;
    border-left: 6px solid #3989c6
}

.invoice main .notices .notice {
    font-size: 1.2em
}

.invoice table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px
}

.invoice table td,.invoice table th {
    padding: 15px;
    background: #eee;
    border-bottom: 1px solid #fff
}

.invoice table th {
    white-space: nowrap;
    font-weight: 400;
    font-size: 16px
}

.invoice table td h3 {
    margin: 0;
    font-weight: 400;
    color: #3989c6;
    font-size: 1.2em
}

.invoice table .qty,.invoice table .total,.invoice table .unit {
    text-align: right;
    font-size: 1.2em
}

.invoice table .no {
    color: #fff;
    font-size: 1.6em;
    background: #3989c6
}

.invoice table .unit {
    background: #ddd
}

.invoice table .total {
    background: #3989c6;
    color: #fff
}

.invoice table tbody tr:last-child td {
    border: none
}

.invoice table tfoot td {
    background: 0 0;
    border-bottom: none;
    white-space: nowrap;
    text-align: right;
    padding: 10px 20px;
    font-size: 1.2em;
    border-top: 1px solid #aaa
}

.invoice table tfoot tr:first-child td {
    border-top: none
}

.invoice table tfoot tr:last-child td {
    color: #3989c6;
    font-size: 1.4em;
    border-top: 1px solid #3989c6
}

.invoice table tfoot tr td:first-child {
    border: none
}

.invoice footer {
    width: 100%;
    text-align: center;
    color: #777;
    border-top: 1px solid #aaa;
    padding: 8px 0
}

@media print {
    .invoice {
        font-size: 11px!important;
        overflow: hidden!important
    }

    .invoice footer {
        position: absolute;
        bottom: 10px;
        page-break-after: always
    }

    .invoice>div:last-child {
        page-break-before: always
    }
}    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        window.alert = function(){};
        var defaultCSS = document.getElementById(\'bootstrap-css\');
        function changeCSS(css){
            if(css) $(\'head > link\').filter(\':first\').replaceWith(\'<link rel="stylesheet" href="\'+ css +\'" type="text/css" />\'); 
            else $(\'head > link\').filter(\':first\').replaceWith(defaultCSS); 
        }
        $( document ).ready(function() {
          var iframe_height = parseInt($(\'html\').height()); 
          window.parent.postMessage( iframe_height, \'https://bootsnipp.com\');
        });
    </script>
</head>
<body>
    <!--Author      : @arboshiki-->
<div id="invoice">

    <div class="toolbar hidden-print">
        <div class="text-right">
            <button id="printInvoice" class="btn btn-info"><i class="fa fa-print"></i> Print</button>
            <button class="btn btn-info"><i class="fa fa-file-pdf-o"></i> Export as PDF</button>
        </div>
        <hr>
    </div>
    <div class="invoice overflow-auto">
        <div style="min-width: 600px">
            <header>
                <div class="row">
                    <div class="col">
                        <a target="_blank" href="https://lobianijs.com">
                            <img src="http://lobianijs.com/lobiadmin/version/1.0/ajax/img/logo/lobiadmin-logo-text-64.png" data-holder-rendered="true" />
                            </a>
                    </div>
                    <div class="col company-details">
                        <h2 class="name">
                            <a target="_blank" href="https://lobianijs.com">
                            Arboshiki
                            </a>
                        </h2>
                        <div>455 Foggy Heights, AZ 85004, US</div>
                        <div>(123) 456-789</div>
                        <div>company@example.com</div>
                    </div>
                </div>
            </header>
            <main>
                <div class="row contacts">
                    <div class="col invoice-to">
                        <div class="text-gray-light">INVOICE TO:</div>
                        <h2 class="to">John Doe</h2>
                        <div class="address">796 Silver Harbour, TX 79273, US</div>
                        <div class="email"><a href="mailto:john@example.com">john@example.com</a></div>
                    </div>
                    <div class="col invoice-details">
                        <h1 class="invoice-id">INVOICE 3-2-1</h1>
                        <div class="date">Date of Invoice: 01/10/2018</div>
                        <div class="date">Due Date: 30/10/2018</div>
                    </div>
                </div>
                <table border="0" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="text-left">DESCRIPTION</th>
                            <th class="text-right">HOUR PRICE</th>
                            <th class="text-right">HOURS</th>
                            <th class="text-right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="no">04</td>
                            <td class="text-left"><h3>
                                <a target="_blank" href="https://www.youtube.com/channel/UC_UMEcP_kF0z4E6KbxCpV1w">
                                Youtube channel
                                </a>
                                </h3>
                               <a target="_blank" href="https://www.youtube.com/channel/UC_UMEcP_kF0z4E6KbxCpV1w">
                                   Useful videos
                               </a> 
                               to improve your Javascript skills. Subscribe and stay tuned :)
                            </td>
                            <td class="unit">$0.00</td>
                            <td class="qty">100</td>
                            <td class="total">$0.00</td>
                        </tr>
                        <tr>
                            <td class="no">01</td>
                            <td class="text-left"><h3>Website Design</h3>Creating a recognizable design solution based on the company\'s existing visual identity</td>
                            <td class="unit">$40.00</td>
                            <td class="qty">30</td>
                            <td class="total">$1,200.00</td>
                        </tr>
                        <tr>
                            <td class="no">02</td>
                            <td class="text-left"><h3>Website Development</h3>Developing a Content Management System-based Website</td>
                            <td class="unit">$40.00</td>
                            <td class="qty">80</td>
                            <td class="total">$3,200.00</td>
                        </tr>
                        <tr>
                            <td class="no">03</td>
                            <td class="text-left"><h3>Search Engines Optimization</h3>Optimize the site for search engines (SEO)</td>
                            <td class="unit">$40.00</td>
                            <td class="qty">20</td>
                            <td class="total">$800.00</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2">SUBTOTAL</td>
                            <td>$5,200.00</td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2">TAX 25%</td>
                            <td>$1,300.00</td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2">GRAND TOTAL</td>
                            <td>$6,500.00</td>
                        </tr>
                    </tfoot>
                </table>
                <div class="thanks">Thank you!</div>
                <div class="notices">
                    <div>NOTICE:</div>
                    <div class="notice">A finance charge of 1.5% will be made on unpaid balances after 30 days.</div>
                </div>
            </main>
            <footer>
                Invoice was created on a computer and is valid without the signature and seal.
            </footer>
        </div>
        <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
        <div></div>
    </div>
</div>	<script type="text/javascript">
	 $(\'#printInvoice\').click(function(){
            Popup($(\'.invoice\')[0].outerHTML);
            function Popup(data) 
            {
                window.print();
                return true;
            }
        });	</script>
</body>
</html>';
        $dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
        $dompdf->render();

// Output the generated PDF to Browser
        //$dompdf->stream();
        $pdf = $dompdf->output();      // gets the PDF as a string

        $result = file_put_contents("shipping-labels/bilal.pdf", $pdf);
    }
    public function actionTrackingTest()
    {
        /*$params = [];
        $params['channelId'] = '21';
        $params['customer_order_id']='4252063781321';
        $params['channel_order_number'] = '2801556051627';
        $params['channel_order_item_id'] = '2';
        $params['order_item_id_PK'] = '1738';
        $params['tracking_number'] = '9449011899564088711011';
        $params['courier_name'] = 'USPS';
        $params['shipping_date'] = '2020-06-04T05:30:15.000Z';
        $UpdateTrackingAndMarkItemShipped = WalmartUtil::ship_order_test($params);
        print_r($UpdateTrackingAndMarkItemShipped);
        die();*/
        $ok='<?xml version="1.0" encoding="UTF-8"?>
<ns3:order xmlns:ns3="http://walmart.com/mp/v3/orders" xmlns:ns2="http://walmart.com/mp/orders" xmlns:ns4="com.walmart.services.common.model.money" xmlns:ns5="com.walmart.services.common.model.name" xmlns:ns6="com.walmart.services.common.model.address" xmlns:ns7="com.walmart.services.common.model.address.validation" xmlns:ns8="http://walmart.com/">
   <ns3:purchaseorderid>2801556051627</ns3:purchaseorderid>
   <ns3:customerorderid>4252063781321</ns3:customerorderid>
   <ns3:customeremailid>9620D8BE80F64732AE8077D06167C814@relay.walmart.com</ns3:customeremailid>
   <ns3:orderdate>2020-06-02T04:16:42.000Z</ns3:orderdate>
   <ns3:shippinginfo>
      <ns3:phone>9704346349</ns3:phone>
      <ns3:estimateddeliverydate>2020-06-11T19:00:00.000Z</ns3:estimateddeliverydate>
      <ns3:estimatedshipdate>2020-06-03T03:00:00.000Z</ns3:estimatedshipdate>
      <ns3:methodcode>Value</ns3:methodcode>
      <ns3:postaladdress>
         <ns3:name>Momin Hamid</ns3:name>
         <ns3:address1>104 Shobe Lane</ns3:address1>
         <ns3:city>Clifton</ns3:city>
         <ns3:state>CO</ns3:state>
         <ns3:postalcode>81520</ns3:postalcode>
         <ns3:country>USA</ns3:country>
         <ns3:addresstype>RESIDENTIAL</ns3:addresstype>
      </ns3:postaladdress>
   </ns3:shippinginfo>
   <ns3:orderlines>
      <ns3:orderline>
         <ns3:linenumber>1</ns3:linenumber>
         <ns3:item>
            <ns3:productname>Adidas Boxing Hand Wrap - AIBA Approved</ns3:productname>
            <ns3:sku>ADIBP031Blue3,5M</ns3:sku>
         </ns3:item>
         <ns3:charges>
            <ns3:charge>
               <ns3:chargetype>PRODUCT</ns3:chargetype>
               <ns3:chargename>ItemPrice</ns3:chargename>
               <ns3:chargeamount>
                  <ns3:currency>USD</ns3:currency>
                  <ns3:amount>6.95</ns3:amount>
               </ns3:chargeamount>
               <ns3:tax>
                  <ns3:taxname>Tax1</ns3:taxname>
                  <ns3:taxamount>
                     <ns3:currency>USD</ns3:currency>
                     <ns3:amount>0.37</ns3:amount>
                  </ns3:taxamount>
               </ns3:tax>
            </ns3:charge>
         </ns3:charges>
         <ns3:orderlinequantity>
            <ns3:unitofmeasurement>EACH</ns3:unitofmeasurement>
            <ns3:amount>1</ns3:amount>
         </ns3:orderlinequantity>
         <ns3:statusdate>2020-06-02T07:26:04.849Z</ns3:statusdate>
         <ns3:orderlinestatuses>
            <ns3:orderlinestatus>
               <ns3:status>Shipped</ns3:status>
               <ns3:statusquantity>
                  <ns3:unitofmeasurement>EACH</ns3:unitofmeasurement>
                  <ns3:amount>1</ns3:amount>
               </ns3:statusquantity>
               <ns3:trackinginfo>
                  <ns3:shipdatetime>2020-06-02T07:06:02.000Z</ns3:shipdatetime>
                  <ns3:carriername>
                     <ns3:carrier>USPS</ns3:carrier>
                  </ns3:carriername>
                  <ns3:methodcode>Value</ns3:methodcode>
                  <ns3:trackingnumber>9449011899564088711024</ns3:trackingnumber>
                  <ns3:trackingurl>https://www.walmart.com/tracking?tracking_id=9449011899564088711024&amp;order_id=2801556051627</ns3:trackingurl>
               </ns3:trackinginfo>
            </ns3:orderlinestatus>
         </ns3:orderlinestatuses>
         <ns3:fulfillment>
            <ns3:fulfillmentoption>S2H</ns3:fulfillmentoption>
            <ns3:shipmethod>VALUE</ns3:shipmethod>
            <ns3:pickupdatetime>2020-06-05T19:00:00.000Z</ns3:pickupdatetime>
         </ns3:fulfillment>
      </ns3:orderline>
      <ns3:orderline>
         <ns3:linenumber>2</ns3:linenumber>
         <ns3:item>
            <ns3:productname>Adidas Boxing Hand Wrap - for Men, Women, Unisex</ns3:productname>
            <ns3:sku>ADIBP03Pink3,5M</ns3:sku>
         </ns3:item>
         <ns3:charges>
            <ns3:charge>
               <ns3:chargetype>PRODUCT</ns3:chargetype>
               <ns3:chargename>ItemPrice</ns3:chargename>
               <ns3:chargeamount>
                  <ns3:currency>USD</ns3:currency>
                  <ns3:amount>5.95</ns3:amount>
               </ns3:chargeamount>
               <ns3:tax>
                  <ns3:taxname>Tax1</ns3:taxname>
                  <ns3:taxamount>
                     <ns3:currency>USD</ns3:currency>
                     <ns3:amount>0.31</ns3:amount>
                  </ns3:taxamount>
               </ns3:tax>
            </ns3:charge>
         </ns3:charges>
         <ns3:orderlinequantity>
            <ns3:unitofmeasurement>EACH</ns3:unitofmeasurement>
            <ns3:amount>1</ns3:amount>
         </ns3:orderlinequantity>
         <ns3:statusdate>2020-06-02T04:26:06.981Z</ns3:statusdate>
         <ns3:orderlinestatuses>
            <ns3:orderlinestatus>
               <ns3:status>Acknowledged</ns3:status>
               <ns3:statusquantity>
                  <ns3:unitofmeasurement>EACH</ns3:unitofmeasurement>
                  <ns3:amount>1</ns3:amount>
               </ns3:statusquantity>
            </ns3:orderlinestatus>
         </ns3:orderlinestatuses>
         <ns3:fulfillment>
            <ns3:fulfillmentoption>S2H</ns3:fulfillmentoption>
            <ns3:shipmethod>VALUE</ns3:shipmethod>
            <ns3:pickupdatetime>2020-06-05T19:00:00.000Z</ns3:pickupdatetime>
         </ns3:fulfillment>
      </ns3:orderline>
   </ns3:orderlines>
   <ns3:shipnode>
      <ns3:type>SellerFulfilled</ns3:type>
   </ns3:shipnode>
</ns3:order>';

        ////json test
        /*$response='{ "errors":{ "error":[ { "code":"INVALID_REQUEST_CONTENT.GMP_ORDER_API", "field":"data", "description":"INVALID_REQUEST_CONTENT :: Failed when called shipConfirm API for 2801556051627. Qty OverShipped : Some or All Line(s)/Qty cannot be Shipped due to invalid Lines Or invalid Qty Or Qty more than allowed", "info":"Request content is invalid.", "severity":"ERROR", "category":"DATA", "causes":[

 ], "errorIdentifiers":{ "entry":[

 ] } } ] } }';
        $response='{ "order":{ "purchaseOrderId":"11582892579982", "customerOrderId":"9770000000000", "customerEmailId":"021BF7DB19EE4DCCAC4F4BAE3F2B7382@relay.walmart.com", "orderDate":1582799405000, "shippingInfo":{ "phone":"8888888888", "estimatedDeliveryDate":1583141496000, "estimatedShipDate":1583055096000, "methodCode":"Value", "postalAddress":{} }, "orderLines":{ "orderLine":[ { "lineNumber":"1", "item":{ "productName":"Yosoo 1/4\'\' Flexible Shaft Connecting Link/ Flex Hex Shank Extension Socket Bit Holder for Electric Drill / Screwdriver Bit", "sku":"004634169", "imageUrl":"https://i5.walmartimages.com/asr/50c071ed-18bd-4481-8dbf-e56c2cceabd6_1.00f9b1251b7cc941e2ff1bcee2fa4103.jpeg", "weight":{ "value":"10", "unit":"Pound" } }, "charges":{}, "orderLineQuantity":{ "unitOfMeasurement":"EACH", "amount":"1" }, "statusDate":1582898969000, "orderLineStatuses":{ "orderLineStatus":[ { "status":"Shipped", "statusQuantity":{ "unitOfMeasurement":"EACH", "amount":"1" }, "cancellationReason":null, "trackingInfo":{ "shipDateTime":1582867815000, "carrierName":{ "otherCarrier":null, "carrier":"FedEx" }, "methodCode":"Value", "trackingNumber":"12333634122", "trackingURL":"http://walmart.narvar.com/walmart/tracking/Fedex?&type=MP&seller_id=127287&promise_date=03/02/2020&dzip=92840&tracking_numbers=12333634122", "carrierMethodCode":null }, "returnCenterAddress":{ "name":"walmart", "address1":"walmart store 1 ", "address2":"walmart store 2 ", "city":"Huntsville", "state":"AL", "postalCode":"35805", "country":"USA", "dayPhone":"12344", "emailId":"walmart@walmart.com" } } ] }, "refund":null, "originalCarrierMethod":"24", "fulfillment":{ "fulfillmentOption":"S2H", "shipMethod":"VALUE", "storeId":null, "pickUpDateTime":1582799405000, "pickUpBy":"test user", "shippingProgramType":null }, "sellerOrderId":"23456" } ] }, "shipNode":{ "type":"3PLFulfilled" }, "buyerId":"52e3962d-90e4-46e1-ab1c-ba579fac5a39", "mart":"xyz", "isGuest":true } }';
        if($response && is_string($response)){
            $converted=json_decode($response);
            if(json_last_error() === JSON_ERROR_NONE){
                print_r(isset($converted->orders->purchaseOrderId)?"yea":'na');
            }
            else{ echo "erro";}
        }


        die();*/
        if(substr($ok, 0, 5) == "<?xml") {
            $array =simplexml_load_string($ok);
            if ($array!== false) {
                $schema = new \DOMDocument();
                $schema->loadXML($ok); //that is a String holding your XSD file contents
                $contains_order_tag = $schema->getElementsByTagName('order');
                $contains_order_id_tag = $schema->getElementsByTagName('purchaseorderid');
                if(isset($contains_order_tag->length) && $contains_order_tag->length && isset($contains_order_id_tag->length) && $contains_order_id_tag->length)
                    echo "valid";
                else
                    echo 'invalid';
                //print_r($fieldsList);
            };
        }
        die();
    }

    public function actionAmazonSalesPriceTest()
    {
        $channel=Channels::findone(['id'=>19]);
        $start_date= gmdate("c", strtotime(date('Y-m-d H:i:s'))) ;
        $end_date= gmdate("c", strtotime(date('Y-m-7 H:i:s'))) ;
        $feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
                    <AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <Header>
                            <DocumentVersion>1.01</DocumentVersion>
                            <MerchantIdentifier>A2OH62YAVP4150</MerchantIdentifier>
                        </Header>
                        <MessageType>Price</MessageType>
                        <PurgeAndReplace>false</PurgeAndReplace>
                       <Message>
                      <MessageID>123</MessageID>
                       <Price>
                      <SKU>ADIJRW01Wooden9ft/275cm</SKU>
                      <StandardPrice currency="DEFAULT">24.95</StandardPrice>
                      <Sale> 
                        <StartDate> $start_date</StartDate>
                        <EndDate>$end_date</EndDate>
                        <SalePrice currency="USD">23.95</SalePrice>
                      </Sale>
                       </Price>
                    </Message>
                    </AmazonEnvelope>
EOD;
       $feed_id=AmazonUtil::updateSalePrices($channel,$feed);
       print_r($feed_id); die();
    }

    public function actionActiveDealTest()
    {
        $response=DealsUtil::get_nearest_active_deal(19,26,'ADISBAC180N_F_4ft_BLK_WHT');
        print_r($response);
        die();
    }
    public function actionMagentoCat(){
        $channel=Channels::find()->one();
        //print_r($channel); die();
        \backend\util\MagentoUtil::ChannelCategories($channel);
    }

    ////magento test
    public function  actionRelationship()
    {
        $relation=['1669'=>['1670','1671','1672'],'1674'=>['1675','1676']];
        //$relation=['1669'=>['1670','1671']];
        $connection = Yii::$app->db;
        foreach($relation as $parent=>$child)
        {
            if(!$child || !is_array($child))
                continue;
            $ok=implode(',',$child);
         //   $sql1="SET @var := (SELECT `product_id` FROM `channels_products` WHERE `sku`='".$parent."')";
         //   $sql2="SET @image:=(SELECT `image` FROM `products` WHERE `id`=@var)";
            $sql="
                    SET @product_id := (SELECT `product_id` FROM `channels_products` WHERE `sku`='".$parent."');
                    SET @image:=(SELECT `image` FROM `products` WHERE `id`=@product_id );
                    UPDATE `products` p
                        JOIN 
                          `channels_products` cp
                          ON
                            `p`.`id`=`cp`.`product_id`
                        SET 
                          `p`.`parent_sku_id`=@product_id ,
                          `p`.`image`=IFNULL(`p`.`image`,  @image)
                         WHERE `cp`.`sku` IN($ok) ";
          /*  $sql="UPDATE `products` p
                  JOIN `channels_products` cp
                    ON `p`.`id`=`cp`.`product_id`
                  SET 
                    `p`.`parent_sku_id`=(SELECT `product_id` FROM `channels_products` WHERE `sku`='".$parent."')
                    
                  WHERE `cp`.`sku` IN($ok) ";*/
           // echo $sql; die();
            $command=$connection->createCommand($sql)->execute();
           // $command=$connection->createCommand($sql2)->execute();
          //  $command=$connection->createCommand($sql3)->execute();
        }

    }
    public function actionChildToParent()
    {
        $sql="UPDATE `products` child
              JOIN `products` parent
              ON `child`.`parent_ku_id`=`parent`.`id`
              SET
                `child`.`sub_Category`=`parent`.`sub_category`
              WHERE `child`.`sub_category` IS NULL
               AND `child`.`parent_sku_id` IS NOT NULL AND `id` IN (6131)";
        echo $sql;
    }
    public  function actionMagentoStoreId()
    {
        $channel=Channels::findone(['id'=>'18']);
        MagentoUtil::get_store_id($channel);
    }

    public function actionSqlServer()
    {
       // $findProduct = WarehouseStockList::findone(['sku'=>'ADHBG100-B/G12s','warehouse_id'=>'3']);
      //  echo "<pre>";
        //print_r($findProduct); die();
       // phpinfo();die();
        $connection= yii::$app->db2;
        echo "<pre>";
        //print_r($connection); die();
        $sql="SELECT * FROM Banks";
        //$sql="SELECT DB_NAME() AS my_db; ";
       /* $sql="SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_CATALOG='spl'";*/
        $command=$connection->createCommand($sql);
        $result=$command->queryAll();
        print_r($result);

    }
    public function actionMagentoStock()
    {
       /* echo "started_at:".date('Y-m-d H:i:s') ."<br/>";
        $channel=Channels::findone(['id'=>'22']);
        $sku=array('sku'=>'aa7411-501-10','stock'=>1);
      //  $products=ChannelsProducts::find()->where(['channel_id'=>$channel->id])->limit(100)->asArray()->all();
        //echo "<pre>";
        //print_r($products); die();
        $res=MagentoUtil::updateChannelStock($channel,$sku);
        print_r($res);*/
    }
    public function actionWarehouseAssign()
    {
        $channel=Channels::findone(['id'=>'22']);
        $customer=['shipping_address'=>['postal_codes'=>74350,'citys'=>'karachi']];
       /* $res=WarehouseUtil::GetDistributorWarehouseForSkuToFulfilByCity('AO0739-010','jhelum',$channel);
        $res=array_reverse($res);
        $final=WarehouseUtil::AssignWarehouse($res,$channel->default_warehouse);*/
       $res=WarehouseUtil::GetOrderItemWarehouse('AO0739-010',$customer,$channel);

        echo "<pre>";
       // print_r($final);
        print_r($res);
    }
    public function actionMagentoShipment()
    {
        $channel=Channels::findone(['id'=>'22']);
        //MagentoUtil::createShipment($channel);
        // MagentoUtil::createInvoice($channel);
         //MagentoUtil::refunOrderItem($channel);
         //MagentoUtil::getOrderInvoice($channel);
        //MagentoUtil::get_store_id($channel);
       // $ok=MagentoUtil::checkorderitemsinvoice($channel,'1679');
      //  echo "<pre>";
       // print_r($ok); die();
    }
    public function actionLcs()
    {
        //759518773
        //LCSUtil::getCities();
       // LCSUtil::shipping_test();
        //LCSUtil::generate_load_sheet();
        $courier=Couriers::findone(['id'=>6]);
        LCSUtil::generate_load_sheet($courier,array('759428568'));
       die();
       $file= LCSUtil::download_load_sheet($courier,'954493');
        if($file['status']=='success'){
            $filepath='order_load_sheets/'.$file['name'] ;
            $this->download_util($filepath);
        }
        die();
       $courier=Couriers::findone(['id'=>6]);
        $tracking_no="KI759351808";
        $tracking_no="756752814";
       $response= LCSUtil::trackShipping($courier,$tracking_no);
       echo "<pre>";
       print_r($response);
    }
    private function download_util($filepath,$delete_from_server=true)
    {
        // die($filepath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush(); // Flush system output buffer
        readfile($filepath);
        if($delete_from_server)
            unlink($filepath);
        die();
    }
    public function actionLabelTemplate()
    {
       // die('come');
        ////////////////////////////////
       // $content = file_get_contents('https://speedonline.pk/pedroshoes//api/images/products/323/2006');
      //  file_put_contents('shipping-labels/flower.jpg', $content);
      //  die();
        /// ///////////////////////////////////////////////////
        $order=Orders::findone(['id'=>6166]);
        $items = OrderItems::find()->where(['IN','order_id' , $order->id])->asArray()->all();
        //OrderItems::
        $params = ['shipper' => ['name'=>'','address'=>'Shop # 11, Ground Floor , Boulevard Mall, A-14, Auto Bhan Road, S.I.T.E. Hyderabadmore, Hyderabad','city'=>'kl','phone'=>'0900','state'=>'punjab','zip'=>'40000','country'=>'pk'],
            'customer' => ['name'=>'bilal khan','address'=>'Shop # 11, Ground Floor , Boulevard Mall, A-14, Auto Bhan Road, S.I.T.E. Hyderabadmore, Hyderabad','city'=>'kl','phone'=>'0900-123456789','state'=>'punjab','zip'=>'40000','country'=>'pk'],
            'package' => ['weight'=>'20'],
            'tracking_number' => 'khan',
            'shipping_charges' => 200,
            'order_number'=>'khan',
            'order'=>$order,
            'shipping_date'=>'2020-09-20',
            'order_items'=>$items  //for invoice generation
        ];
       // $response=CourierUtil::internal_label_template($params);
      // $response=CourierUtil::order_invoice_template_new($params);
       $response=CourierUtil::generate_order_invoice($params);
        echo $response;
    }

    public function actionPhpVersion()
    {

        phpinfo();
    }

    public function actionPrestaShip()
    {
        $channel=Channels::findone(['id'=>'16']);
       // PrestashopUtil::updateOrderCarrierTracking($channel, $prestaShopOrderId, $ShippingRatesExlTax,$ShippingRatesIncTax, $TrackingNumber, $Weight=null);
       $response= PrestashopUtil::updateOrderCarrierTracking($channel, 246, 2,2, '1Z786W500329774583', $Weight=null);
        echo "<pre>";
        print_r($response); die();
    }

    public function actionGetCityName()
    {

        $cities=Settings::findone(['name'=>'LCS_cities']); // check if cities are present in setttings table
        if($cities){
            $city=json_decode($cities->value);
            $city_ids=array_column($city,'id');
            echo "<pre>";
           // print_r($city);
            $index=array_search(6,$city_ids);
            echo $city[$index]->name;
        }
    }


    public function actionMagentoTest()
    {
        $item_stock=WarehouseStockList::find()->where(['sku'=>'ADIH100BLKGLD10oz'])->sum('available');
        echo $item_stock; die();
        $channel=Channels::findone(['id'=>22]);
        MagentoUtil:: sale_price_test();
       // MagentoUtil:: get_store_id($channel);
       /* $order=[6763=>[],6764=>[]];
        $api_response=[6763=>2030,6764=>2031];
        $channel=Channels::findone(['id'=>22]);
        MagentoUtil::notify_customer_shipping($channel,$order,$api_response);*/
    }

    public function actionGetMagentoStock()
    {
     /*   $channel=Channels::findone(['id'=>22]);
       $response= MagentoUtil::get_stock_detail($channel,'aq3211-606-s');
        echo "<pre>";
        echo isset($response->qty) ? $response->qty:"nooo";*/
    }
    public function actionKey()
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        echo $encryptionKey;
    }

    public function actionTracking()
    {
        $courier=Couriers::findone(['id'=>7]);
        //BlueExUtil::trackShipping($courier,'504897');
        //BlueExUtil::ShippingStatus($courier,'504897');
        $response=BlueExUtil::ShippingStatus($courier,'506087');
        print_r($response); die();
    }

    public function actionGeneratePdf()
    {
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, 'http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?5012978328');
        curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

        $html = curl_exec($curlSession);
        curl_close($curlSession);
        /*$url = "http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?5012978328";
        $json = file_get_contents($url);*/
      //  print_r($json);
        ////////////////
        /// error_reporting(E_ERROR | E_PARSE); // it will parse error
        $pdf_name="todays.pdf";
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $dompdf = new Dompdf($options);
       // $html=self::internal_label_template($params);
        $dompdf->loadHtml($html);
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');
        //$dompdf->setPaper('letter', 'portrait');
        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        //$dompdf->stream();
        $pdf = $dompdf->output();      // gets the PDF as a string

        $result = file_put_contents("shipping-labels/".$pdf_name, $pdf);

    }

    /*******************for magento spl dont remove*****//////////////////
    public function actionRemoveExpiredSpecialPrice()
    {
        die();
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        else
            die('offset required');

        $channel = Channels::findone(['id' => '22', 'name' => 'spl']);
        if ($channel)
        {
            $connection = Yii::$app->db;
            $sql="SELECT `channel_sku` FROM `channels_products` 
                  WHERE
                    `channel_id`='".$channel->id."' AND  `channel_sku`<>'' AND  `channel_sku` IS  NOT NULL
                  LIMIT 500 
                   OFFSET $offset";
            $command = $connection->createCommand($sql);
            $list = $command->queryAll();
            //$this->debug($list);
            //$list = ChannelsProducts::find()->select('channel_skuss')->where(['channel_id' => $channel->id])->andwhere(['nots', ['channel_sku' => null]])->where(['not', ['channel_sku' => '']])->limit(500)->offset($offset)->asArray()->all();
            $list = array_column($list, 'channel_sku');
            MagentoUtil::remove_expired_special_prices($channel, $list);
        } else
            die('channel not found');

    }

    public function actionGenerateImage()
    {
        $string="R0lGODlheAUgA\/cAAAAAAAEBAQICAgMDAwQEBAUFBQYGBgcHBwgICAkJCQoKCgsLCwwMDA0NDQ4ODg8PDxAQEBERERISEhMTExQUFBUVFRYWFhcXFxgYGBkZGRoaGhsbGxwcHB0dHR4eHh8fHyAgICEhISIiIiMjIyQkJCUlJSYmJicnJygoKCkpKSoqKisrKywsLC0tLS4uLi8vLzAwMDExMTIyMjMzMzQ0NDU1NTY2Njc3Nzg4ODk5OTo6Ojs7Ozw8PD09PT4+Pj8\/P0BAQEFBQUJCQkNDQ0REREVFRUZGRkdHR0hISElJSUpKSktLS0xMTE1NTU5OTk9PT1BQUFFRUVJSUlNTU1RUVFVVVVZWVldXV1hYWFlZWVpaWltbW1xcXF1dXV5eXl9fX2BgYGFhYWJiYmNjY2RkZGVlZWZmZmdnZ2hoaGlpaWpqamtra2xsbG1tbW5ubm9vb3BwcHFxcXJycnNzc3R0dHV1dXZ2dnd3d3h4eHl5eXp6ent7e3x8fH19fX5+fn9\/f4CAgIGBgYKCgoODg4SEhIWFhYaGhoeHh4iIiImJiYqKiouLi4yMjI2NjY6Ojo+Pj5CQkJGRkZKSkpOTk5SUlJWVlZaWlpeXl5iYmJmZmZqampubm5ycnJ2dnZ6enp+fn6CgoKGhoaKioqOjo6SkpKWlpaampqenp6ioqKmpqaqqqqurq6ysrK2tra6urq+vr7CwsLGxsbKysrOzs7S0tLW1tba2tre3t7i4uLm5ubq6uru7u7y8vL29vb6+vr+\/v8DAwMHBwcLCwsPDw8TExMXFxcbGxsfHx8jIyMnJycrKysvLy8zMzM3Nzc7Ozs\/Pz9DQ0NHR0dLS0tPT09TU1NXV1dbW1tfX19jY2NnZ2dra2tvb29zc3N3d3d7e3t\/f3+Dg4OHh4eLi4uPj4+Tk5OXl5ebm5ufn5+jo6Onp6erq6uvr6+zs7O3t7e7u7u\/v7\/Dw8PHx8fLy8vPz8\/T09PX19fb29vf39\/j4+Pn5+fr6+vv7+\/z8\/P39\/f7+\/v\/\/\/yH5BAAAAAAALAAAAAB4BSADAAj+AAEIHEiwoMGDCBMqXMiwocOHECNKnEixosWLGDNq3Mixo8ePIEOKHEmypMmTKFOqXMmypcuXMDX+m0mzps2bOHPq3Mmzp8+fQIMKHUo0Z8yjSJMqXcq0qdOnUKNKnUq1qtWrWLOCLMq1q9evYMOK3am1rNmzaNOqXcu2rdu3cOPKnTq2rt27ePMCFai3r9+\/gAMLHky4sF2ChhMrXtx3bkvGkCNLxvtwsuXLYwdi3sz5MIDOoEOLHk16MuLSqFNzdcxStevXYSvDnp1YM+3bpj\/j3s27t2+9p38L38x65fDjqmUjXz7UNvPnYPlCn069eujg1rPfLa5Su\/fCyr\/+U3cuvvxM6ebTq1+\/lzz790a5o4RPf7XD+sPd43+Ofr\/\/\/8xhByB78s034IE1hYfga\/ot2Ft\/DkYo4XUNTmhdgSdZ+J+CGopWYYeuQQjiiCQCJmCJ\/GFYEorvcciiZR++SKGMNNbY1Yk2+qbiijl+52KPtYkIJHG6DWmkkTgeOduOJClZ3Y9OmihklJBNSeWVFiaJZWlMjrTlclB+uZ2VYg5GZplo1qdlmpx1KRKbOt4HZ14xzglckXbmud6aekbmZkh90hZmoM2dSahYhh6qqHB8Lgrenx85mtqgkvJUZ6X2Yaopo5du6hekkXoKGqWiJphoqT6diuqqlzXKqmf+oHL0aqtyzhofnrYGpWquvJrZaa9exdoRsIyRuuqvxP6za7LMwoprs7EJuxG0jzYELbLELkvttvZpy61O0k77rZTWNostsN6Oq66l5657U7gyuXtnucy2y2u68uZ7nr36wpuRvpTVmiy\/tuILsLuuHmyqvxYpXJexqBI8q8EOf5twxQxfVDGiAmdLMasfb3ytxAhnXJHI0XWMbsgRP4syxiSva\/LJL9+ocq8xH+tyzQdf7PDMFPHcLUMj78wty0Ln6rPCQE+UNFEQl5pzy+9q\/LTUU1vcdERXCxW1qFmD\/SxGXYuN9NFbQ1R2e\/QOfLanIpK9tqZL95z211fjvWn+2HC7bPXcldYN8N16J104pnzv7XdBgLv9NrWE39z4wkSb+zjdOxs0Oc6JFx25QpuDK7nSlyNutOahT9y55Z+DnnrVbadZeaFGb0sm46+DvHq9rbueO02H5zi716UHXruyxf8epeD99p6Q8sCPviXztzbf0\/HQL0q9vM4\/n33wPaJuM\/aWZ886+Rt3j5D54A8pPu3Wm+84+jCrjzv07QP5PvH0eyz\/\/HOz3\/5ylz8k8Wt3kkre\/8q0vZIJEIHck96XBgi\/fClwgROE4MoeeEGZSfBKDbSJBhXVQQyCcIT34mAJx1XAFw1PVysMVAxNqKQQqkuFM7TdB6n0QrbFj4b+qsuh7lTIvh0ubyFFQSGhhAjE8CmxYDgsYuw2+MPoued+TYSTDVkYxe8Z0UmPe2Kf4iYgLGZRdmJ8FQ7717UWsiiMTJwTGa+4xTO+MY1D5KAUe8hANqYqjlocWxnraMcSEVKHRPTiFPvoO\/5VcV90xGMh\/XNIyHURf1+sIRKhBkg2zRFCZpzk9CTZskRicpFi4uMf\/ZhC2IGykqLMEimxZkrluRFFcGQl6VyJq1DGkoezNJseFanKDMLqkchr2C8N2Mm+1fJ3tzSkLsnSTDR9MmjLdF8wnTnMUxYTitNUHS+dlk39bVNxzyRgJs2GTLmVU3jnxFw6XxdNPcUzleH+fCcwqynPbtpync5Epj7HeE\/jzTN19ZyOcb5S0FEOVHsNddQa97jJCC00WPzE50NJGFGIHjR0CYXORccnUBFWdKPwzKhB\/QlNgO6pOwxVqUNF51KULgiW53sgRRt5U5hiNJ+6o2lIbZoenPLuo5sbaoCw9SsKVjCCt+IaUV3YUY4idXJKBRNTT+VUR1owcwM8yFSpKlOJXvKfqASQz5rqy6eWDCdtpRxQx9qiqh5qosQ8aU93xVYb2hWMi0tUXOlq0b\/K8KwtTeuGtoq+rsJwrlRz5U+MStjsUNZjV21cVpGzVq4O1odfhWtfIVtZ81x2gyxVp2IpydjJfta1pBX+m2g9W9bSMsiwBM0s4DZ7nM429rWrFGjCTmvbFNXWqqmlZ02L2tpV5gy3Bpztb49bXNQQN4W6DeByTdvc6wHXu7Htm3Sx993qqgm6ecKrN\/WKIN\/C9rnUBex4pyRW80rouqTL7tp4m5\/ussuv8dVkVNVm3\/ui107qRes3WctX2sI3vIoT6nYLXB78gjO5CJ2weNzr3JAd2InU1DCFffThQOq3bPzlVIOn++CSWpG9Iz6QhYOIYZCK2DscBm+LQ9vhEseYSAHObY2TemPt5Pi\/O4bqjwEbZHsiVrUL3s+RQ5zkty5Zk01O75OVu1op+5fKHs6yOa+MZQh7Tqd55Wn+e78s4bn6uEZiJjNvZqzGLWe4y\/iZcptTZmbTyZmZfT7qkLFaZMuyecBujjM8\/6xNRXvSzjbG83lX\/N4wBzqBl2Y0gd5sTUgTWdL00TOi+exiTUvT0Wg8cRsLfaFDz5fUPDY1nDnNyEFrltVPcvU4Ya1kWdOIznlE83rVLGNdmxTAmYaor2eN6k6rOm+4Ho+x5ZroZHN02b+mNT6f\/bQU\/0bUr46pte86bmwbutm1FraCYTwgcO9a3O38m7k3rO0Mcttw0VbotF9s6Xgrc970Rve2bb3bfIt035CssgfHKRGAV7jeo7y30LwdJ0r3mNdWPjY2HY5jiGMpwYmNcp7+EZ5Mhd8wsB7neGCArTOJ84ziD9LPiUbb71hrXOAqFwzLS0lw7YIaPjOXuYNr3ut353zS5b6wukPObrWSx3VCP41jQVt0Xh595DiPeM\/3a3Dj4gnq19TN1GFb6mReHetJp\/HSodz0xUpHqgmXenlDXHZlnR3tPt86iru+1Lfjzntx\/\/rcaVr3u+Od6y6vGcznbBvUAb7k\/Rl7cG1u+DVn\/eOeJvTP6+p3udc38NTG+MIr76Cd01Lvq978pjv\/dMeHUvLgLTzpOZ\/2Oif+ZYvfjedb\/\/fXD756lJ+922sfbAHu9PF7FXt9OQT7\/8pe+OoxvTDXzmWRI53aP2o+3YP+D\/3D7\/32KMs9brBDzlHDm\/vdD3XK94l6aKv+pZFv+J7PX\/X0Xx\/x7e8237Uaf7iH+6fPZ38kdnknBH4iI363EXRdpn2Eh34CCH\/E13L5h2\/vF31RF2UMCHz194CrF4E8R313Zn3qlxKid3Ic6H2pN4ETt3+c5VMk5YAn+HAEyH4gGGkiCHQu2C0BGIPSNoNHZIDpw4K9lYNJ5IMdYoQ8yDFIeCRCmIDrh2BNqGIGAoAwmIRG9oRM1nYQeHzrY2AkSH8ZZ4XMtYSARmw4SIbOdoO0x3BmCHpgOHpiOIYeeHpt2IFc+Hmlx3sEZn5UuIFxeIVoaE5q2HGBOHBa6GX+\/beH\/\/eCfviHrVaIKXWIcniHGbiF\/CY9lQg7O+iI\/QWJNhKFS4KFJjaIluiGqJSJx7aJnPhtosiEFfiIczg\/knh\/pqhKqLgwqriKMeeJzDaLMhiLmEWKFsh6ichet2hFuaiLuteKZYh8tLhudchgykd+rvc+xwhJyaiMTsiL2SaMsEiJv2eJ1WiNWHSNZleF2jiE3CgjoAgb0heMvniG09h4XeiG9siIYZiO+raOZBWPAwiMqOWPdgh581iOBols2aiPt8WPd+SNucaQZSaQ4qiIXWiOzGhgCvmQANlP0TiMG4ldDvmLgXdSFRmOmoiOGbmLH7lSHTmJaeaMxVb+jL0nSLtHdPmYki0Ikacmkee2kkrXks+YcCepYzYJhzjJfz5pViG5j0lpe0v5j5KVihf3hiZ4lH3XlMgFlCL5kvWYhxZHlNWWkFY5Gu+ITjz5jVyJh8n3WJUWlig5lqGokyTSjguJlRJ4ltz1lUhWlFUJl51ol0v0ihoJjmXpjiRnkXKpfn6pjoB5WE\/pdYR5keTSPzTnlo24mIIimU70mFcZmYnJZ5Q5dJZ5k5g5fpoZiVoZcI05fam5hlS3l6NplKW5jJ95hILZg6vJTXi5lWQ3lX1ImrMZl7mpZZyJlJ45nG1ymCYplZcZnMlxmp94m0x5nBPibjcXm33pnNv+iJxyJJ0HV5uo2ZoD2ZtgWYJcpJ20yZ2jKJ6AqJ6Y5p2EGJosxpfniZ7bmXfs2ZPU6YXy2ZbmqTX2eZ\/4l59oOWww2W7KiZBvGaCdUZimU5w56Z5KCaGD+Zpghp31yaB1iZ8H6poG2pVraaHz95uyqaHWBZ29SKAV+qFqaXn96Zv4WKImShoOypIdOp7QeKPSyJYwqoMLOqMwgqLduJvTmZbmGJ88Wp5UmaFAyiVCyo7w2ZlGupx5+aJKSqLZ2aRk+aT9qKK4uZ+FpZcX+p9oo6UnCp6yRKF\/CaZeaaWwSaY6ZKZOiqbVGaXGOaU1+pxiOqIxmqVy2qBc2pBE+p3+EupRalqk5PmmSwqgf+ohgYpLdhqhbBqiiTqmi1qmjUohdMqfXoqoOQqiLpqkioqlTJqpQFao6aajHjmpoSqifNinpWqqtLKpYTqokImnj5oZCWpysSqruUGrbdqphMqqMemmlkqqjOqrmJGnEyqst8qiR9qeonqssJqsyhqkwJp8ziql0EqlvDl5V1qtmHqt2IqqhqiqLtmtzHqmxsqnPtqc5Fot5mpvtsqtn9qixTqt7lqE8wqp8VquHAqqI9ivQlav+umqi\/iuwPmvhrGuWYmuVUqsCLqnr6qwMsqwDZurcxmpjCmxTveFyDquGLsYDktuh8qK2QqpHIubGXL+qXE6ssWisSNClyEis4J4sl7XsiH7sjCrGCUbmDirkh47fDorrjzbsxmbssW6rXeqrjZ7bmKJtONDsAVosJKKq0qrjlErtfxKtTQIsd\/KdEzrmFvLtbTjtT9otR2LtWj7sWVrto\/VthEJtqo5tEH0tnD7XgGLr\/Jot7aHt3mrY3sbrU17r4RLtj8auOImt64YtIyXtTNLs\/sEuIorVIPrrZ4qtnTLTolbuUNzuT8LsGxbNJTruVH5fWq7pqPLOqVrupQDuk\/LNoybao6baq3rukI5oJuLpKt7VLeLu6FbsLsrrb3rOL\/rusHrZLVrmpALIgiIZcdruslLnKkrhX7+O0TR67nTC4XLK6BO27wom72Vu73d2b2ZCb4a8ryNK76KS77rObwH+72zi3XsG7ju+2jmK5zX23L1m7f3S7tju7byS7qdi7txC7voW3L7W0r9C7f\/m4YBrLoDzLoFbMB6q7sC27fFi1kNbLYPnKoZ7KGGi7ke1cFc+8HnGsI4qrkqfLcVbMGCi8F8K8IsPMPgZMJSi8L0GsHWu8FU9MIwTGUIPL\/OssC0hMNIq8Nax8MoS8Qp3MJ\/C8RBHFVD\/LFOvMNMXL5I3LNKjHn5a5gJXKcrS5xbDLNdXLVZLLQ+zDlXvGZT\/DCxm6bV28RGzLnw+sYHLMOHK8AjfMYxWcb+I+vHaZvGj9vGXvzFXgzIGCvIWUjI6VnHASXFePy6ekzCz9rHcdyDisywjDy3UJyumBzGtLnJ\/9rJjTvHFWfIaOzIzkbK8WrKzfjJEbvG2OXK5LpziFyrrOy9oazKw2jL1zpjG\/e+shy2bLfL2wbMyopf\/4K\/qKzGE+y7kjzJucuGSzMsEAy\/BdrLBHzH1BzD1sxYsgLCNlyK3EzB3vzNQjxfzQcoT7zHEnzO0pzO6kzF7PxdXoLFxVy3tJxfyuyrSSJyPHLIyKy\/\/XzD00zNAX2IRTvI2ryi8my8CT3JC92RDd3ID\/2lB+3C9FzPp3udUyd\/2FfQ8kjSG7rRUdz+0R5NyeF8JpQiuR5p0jUryros05M70Xhc0Tr6c2M8sc9cyJCMTv8sqzrdwpuXyzGd0Zl7zEpdwjj9xkVdzoOI1HJo05PSKWu0FmDZ1FY11KYa1SEtkFRdpVbNrj2W1Vqx1ftsxwu70kiGaGENvz+NiHPNvOSD1mmh1uWc0m3t1pZ7z3zCk3WNdGU9p3eN12eh1\/BMxk89xWD9WYJd2Kop2VsqWIhtFoptySbr1Zn62PFytnudolxtr2122VaR2bBMv40dxJ792bK71io72Ofryw492kDL2Y3a2s0ct7C9k5TtqDQdrL8tiLj9p7rtTq8d2kM63KOSycvN3ClV3HL+etzy1paL7dPQnZyWbdpZgdrO\/Z3SbabUXd2wed3YbdtXW97cTRfqrdwMvNowPN7\/dtaazanozcelvd5S4d3Bfb7hraXyPczeTdD3Hc81bN4m9t9NGuAiTd\/1bd+9Hb8RzcHwbcEh5H8Ontrsmt2nGtTypOBAWrI97aIcvqxYrd9Xwd+0nZcgPqMavpmybdDtjeJOoeLd3Nd+LV1VPHwZTuNMYePojOM5rnE7vqMq7uMxAeTzLORD\/mJFTtc9juRJoeQSrdJ+vUXYDLQxDsaHLeVRQeUUbuVuTT0XDcAF3sMz7uVIAeY\/LOYrXTePQczuzc9pruZJXuffbVwtbqL+F3MUzlzis9rldt4UbM7Ge66hrrIU2Rzh23zgD55bh86gjVLj+jznUInS2FvhBhzYT1HpCA7UmM6\/mg68MbLfBM7oEO3oL36GkR6gWsLeq4zqGh3NVc7kTa7AgP3jsW7pvEvrYW7rTc7gLFN+tc3rxOvrbQ7sQ44jWaW+\/wjooovshj7qyNsgI97S\/a2rY23YHv6grW6fzL7l2z7ZZ07HRz7oRIjted53346e4V7uab7rn\/7I547uU5jfxv7h1C691i7TW96B0P6rgm7vU47n2Q7G7a6d7w7vcP3oJM7woG7wBA+y+D7vrbzv2tvvJD3uzw7x9C7xE1\/mIC3rSln+Ui7t8ESt8XUN02Tt8XYd5SFP8Q2f797Ofb7k8guu8mOM4acc8JKx6kvs86cmXApI8q9cIchd7xaPdkJfJese2\/9egEQfSTjv4khPFadO86nO1EbfR85n8xdY9Yj+IbD+tV1P2lyv9Y+GPBpImoNEjQdvXmvy5UF\/9oWr6k8flw1Yf2bU93mvaZxO6J6O8uYu7e95PH9vaDp+iUQe35ci+IMP9NGy3TF\/5xcq9pHY9jIK95\/E2pXpEmZu9+l9+ZXfGhKvM3an+X5KkC0q+aTnW3Ee+mq\/1Lle+vc+80sPYtu3gXzk+pUnOOlOzrkv46Rv+zJf+74\/hiYPk4Q\/3ST+8yZ\/wTC8jfknDfLGvxWnn0ez5WIZ3PzinTjEThjSn9yij9+4f\/0DXfHe3\/ORVHZttf4AbrPjb92zP6yhjrjYiIwYP77y7y\/TX\/4A8U\/gQIIFDR5EmFDhQoYDATwE0FDiRIoVLV7EmFHjRo4IIX4ECbHjSJIlTZ70GLHgQ4IiUb6EGVPmS5Yzbd7EmVPnTp49fSb8uDMkSKFDjR5FmlTpUqZNQ1Jk+lOqxaBTrV7FirJpVq5dJao0CPafS69lzZasSZXoWbZt3b6F27FqzqRFnd7Fm1dv1Il84\/qc+1fwYJNbCR+WmdahWLKIHXdVvPKpwKOPLV\/GnLli4Jt1NX\/+TqkUNM3Go03\/NXxa9UGjksWuhi339eKhY5HGxp1b90zONj3vfuwX+ObSw43TFX48c23Xyp2znk1Z73Pq1Yf35n3bOtzk211HD7tX\/Hjy5c2fR996YWrvp4u31x1Z+l749e0jxp5YO\/L0\/f13VwhA+PL77j8DD0QwQf8YYu8+xxiTz8HVInyKufkilDBDDaciEKbfcFIwxAX7WkrDDmkTMUUVV0yRQQE3hKwxCMGDUTMKZeztxBp35LGw9\/SrTCcWh8wLqhIz1NE2Ipdkssm7XDyyxxjnmlHK0W5UbK2WfrSySy+JwzCmD7\/sLEoHk3QyTTXX5BLFMckEDKyaqoT+0zIsZ5RPyzr35BNFu9TrU0wz70OTTUMPXRFK0QLl6TWW6GRUsDwD0\/NCGiPFdMMkfQwyU07frK9QREcl9T9FQfWUtC3H2vLSVLmaFMfiKn21Vvs2RWs\/EEtd0shFJRSVV2GHLXK9F20dKTJZkWUrVvGYhbY9XElCVVBiVfS1Wu+CvbZbbycL8NhoNVIW0nGzcpa+c9c9btpkdS3zWwWzhffWNtm9rEF8gdz3qnSn6zfgCe\/Vqt7s5E2Q3k7PJFjgwfR1WC0sI44zvPEoxhgzd2VbOF6ED1QYUIbDzBg1cUu2uNKGUaZ2tvJYhtlkkknr2LePEQzZwpFdtfRmn0f+PdXgmJujrdWhszs66dw2tvFnU0n8deeGnKYa0aBrVlq68IzOuuCuv75yZdOq7i9ncKW+mmy1WUxbZ7DBMxds2eSmO1+xaabV2rXJMzvvAe+u+09tj56U68A3mhnqxA9nHHHAOQKY4pOrY7pxIKOWW2QlLc9o8akr5zx06DyH\/GWHJ6cOdNE\/HZzws+dbnbh3H4+9diXhbr3V8wRG\/Tlu9wZexLZfj5320Ek31njbRccO8+TTC7h3534PvnqQn8\/9bcOXD3d25Lkv\/j3nQzNwX+mVo9569aHHXuisKd0efOi851l+25vPPUR8z2+34fX\/f1q4BvU1cMXNfqvi2Pf+Dmg5\/GFtdAlbF\/+Mkz4AVjByQJGg5OSkEgMu0Db086AHG6g58kHwXBkEDgUtuEKIFch9SXNURDq4wH8VK4T2G6HbSmjCaKFwN6q74Vd8yDsIsapoQdSaC22IxOXlkHgpaxBeHMfC8WGwik2rnwCzyMSLtFBp5ZrhAWsoRS42UXwv7BkJd+hAIVIxe2nUYVyeCDtyKa+MWnwjy8oVvxCO8Ul3rJ0T\/aY7Nq5xjm10YyGhqMizYM2Ot\/tcIiV5RTehsXGPZJwfnQLIQJ4RjZS0IiPxOMlpDTFONXukCkk5yeENknOYPJwCORlEQbrydmps5RYfuMq7mbJRsgLjFOv+x0ti6siLMOQZLAMny1n20ZMvzKMSVVfMUg7QLc\/kI5iGSU1i5lKZ+2vTN+nGzGbS8EeUBGUo41grX9qlhHTsnP+4yUtvklODnuSiPcspv\/xYE45MSyem2imkMBEllfKcJynrqcuhcUacmWPoPkV4TgFF85a4fNVA+TNKfQJRoseEaBhxGFGJipFLLcyjRnmkUo+1T5glraM\/Y5lNc8J0nx36YyXXSUhLZoqlLcXjS23axZ\/2MJn6zCRJh2pGkj2LPReEVlFtdtSOPhSQIG1oOJEaS6UutZMz4xW7pMobqAnVq4qzKMZkSNGuMnCLOz1rSKl6KHAGNHVWvSNWY7b+wVjl01V6jeteEWqouqb1OngtI2D1qDuiIbGgZAzs28TGpn6NVSpb9VNkF7pMi9HUpIaUqWb12Ms0RS+0bdETXBUn2vaJEmZw8+xIdwlZ1mZVgU0iol2ntBbXajGSCZXkZuu2zbZeMjqH7FltkUlOtp0koX3zaE8q09tQ\/ha4bhQuU\/36wNUq17bFveiI8FZM6CL2cpVkbEyJe13sthajXWvgdgvUXe++VpxlOxg1y4tZgsawr+q1LntXmN1xus28XzzuZA9cXz5F172qVetpyzKr\/8ZzcQJOJIEla64Fu26X1mWwfflrUt2iq8JHtPB6MWxBDRPQZSLlZ4IN\/N7+EFe2w6uzLM0621iMOLiZikUZbFFMS9wtscYRHvFIS4wVCr+4uD6eJZBLVrgh3xBD6jpyxqCsZMNyyHMHTfJVc2wrzd1YsBztaZbFaubjSXjCBMOkKlccvBa7mFZsHq031Sxi8D5onvvts3TbWLoLz3nAD0auWwM93EXvecp49spz0Upd9A3W0P+rM\/PC7GIQOjq3jX7YnydNY+tsWcxu5iqoJctRdXr605XmJqDthlhT53XMplU1p0Er40272kq1lqOoEZnmN3eZu1mWckjlu0ji2dLXZIb0bsk7agibZZMJDPClrZfpmVbZmbMtM7CfvdJoJ3XJTM4pgBeqbaf+cVuusY0xuEXm7HGnStz3QzWsSkMWej8v2+wGnrvh69Bev4\/Xi3RTvddccHyf2yr49LbEVAxwOiO634KFuGOdfCeRXFzhjLr3NYX970QXe3TpTTHJKU42gX\/3gzvW+LGZ7fGP9ynkqB15PUMtYx4TtdArr17L94rAnn87Zeosec0jdfNm5dzi5Sb0yeGtKJUDvd1Ph3qggjIxJnIczblWuomyzmiHe5nfBH+ypa2uNqF\/l+dd5\/Vfqx12myuTfXwmtr5RWdWfr31tbXfdvhmOzHeymu7IAp3wtJxvacdX3Vj3u7wAj2AYg+\/KxqT54b+0MSLd09gPz3y2qh55hE3+\/n1T595j0ap5aJOutJ\/OO6wnTvqfmX60XI856wfe99ebj\/GyP8z5+JdsmZXd82hftu6XO1fKFjb2\/hr80Qkj\/N+Hl9R+rj6Stzf204Fd+Z46UamcT2noMx2S0\/\/98G99SuN3f2sRr+n3l\/\/1NS3889Kd++Mllf7qEz\/Y2Zccj0A9M5K\/+du1+hOmaRs28gM9BkSk4OO\/c\/M\/7lg\/8HuxAcS3Agy8IhOWBOwmaku6KYGjgok+D4lAi5rAt0jB7nsd7iMiDXQ5ebu\/f3o+aVIoEAw9s+Op+3LBFIs99QNACgzCiNGhHrQxGMS4uKtBbTI2SVvA6xPBmesz8yPBaAL+wvYTuSGkvORDwsXjOCikHzCUQVbCQSrMFRpxwMzaOSvsvwr8JS1cLi7sQs8bwxK0QTtywtGzQ04JGrOiP9qTPMjzPvMpuomawyB7pvxjnRAEOTh8wwfUP6QDxJuxPQ+Dv886RC9UwhwkNDGsOyzEP4ZKO96bREoURByrvNQbxEzcvERUxDBkRK1zRHfij1XUm6sbGzdcODlkRRv7wliERU4kE13kmP6yRROsGtVYwTiEu16kQykURlkbF2JEvz10LpZzD2qcRmtERGckQlecwToExgabRdgww0X0GWXURqM6xjPzxlcTxxLjmxMqx4HhxjOkGnWsxwhaGCP0vXf+hEdoJCx6BEXcOEd8xMWw2UeC1Bl\/BCeAxDU07EB+LMjYkLNSJJZKjLDk4UWIZMdLCatOVEA9bEc7UTuMDMQ\/PEifAsl7XDyP9D2wApoEGkmdQ5KTRElv0Ui14shmhEn7W7cWCcYbfMJX7B+XtLx1jCqk1KCfBEqS1J+IXMIUcshXUkqBcrKOdEp2Iq1eYcFw7C9jVLNlxLXzu0TZ2sptlKVEQTKwrEU7vMicFL9TJLu1WhafTMul5DvxupyahLw1vLm4lMuZVMmq7JI54aBUJMC81EtVMzJj9MvCLEmEHEf6kszBtBq6HCeHWAwM7CTGbEzZi0xJNMoGHMFrZEr+g7zKvpxK9zMiyvDM8AFNZlnJVHPLXcGTaPwOrFxIvavIh3zNLElN15xNrhxOuWrNU5JBbFu61bzF5OSd94MnIivO1pvMeHy039wo0PLDT9TOSHNO49TK6vROnwNG2qJMeipDw4ykPoxEcuxNE4tP7cNL8mRJ17s20mSpPLRJmYHElGvO+Sy\/70TE63w0+7Q3nPQn0xlKMizKytRBqOjOA8RMUtlJgzPQl0TQ+2Q+9LS+x\/TBD3xQ3XzEWoTKCm2+y0zKDN3IDeVNubsYnXoWmhRRktw5sQxKFO09\/YTQboQ56nTRAG1J0+HLEFXPEa3NNTxRHd1RCmVPKeGrYKr+zyCFT4ncnQBSy9vMRlJk0rlU0eGCnbvMPSotz3hs0saETmvjL8HsUie50Oh8uTjdnCklUzjpp4xkyDQNRTCKLjZtUyZ50+iBzc48y3ir0yp1Uvz60DS0UwF9TsQszd3M0T8dkkCtrEFNoum0MhY9VH3cxHADURnt0UYlUI+5DY\/yU0qtVM1ETkLVVKPrVFL9VFdKNx590qVxVLxBLzXsMQVVVTWxVF8kujkd01htxV+kt94pVRgJT1+R1NiUPkH81VX9Uu2iU2OFUnDst3xbVk3JVRL80VfVpiWdVmxh1UySumLF1l\/TVgV7o24VO3hFxmctVKqrxnzcUnm1zrP+udWlXNdjnVX8\/MHW5M+\/3L+0aMG9hMBkzFctzVJ+PU7T+lcvqSV3pbQmdDrJvNdOAaIkFcnaa1g93Z+VSFcgndhsRdau9ESMjbX1jFjzrApGvZCFxddc\/Fau5MxwhdWTJbeUtdhXZFn9cllO\/dgdZE7ABFmb1VfE46CS3VSeRdmAFdiVJdiMtdV+Va9A81gPTMeQldkAfDt1hVpv9dmVSamgHc1EjVei5VpTVFqHBc5CjL+xrZE7zZ+8W1pCuVmTfFmYTVqFzNuoctqdpdu1ddKqK688\/Vo1xUeaTUjQIMt\/7EmxLVy0wU6LW0+KhFtTNdpOJNdyVbxqbdXQGM\/+ypWWk\/pWLCxYjUVaSB3VmZ1U0J2Xcx1ducVE073JpnJULVzdq+1bJtw6T5S5y5RdoRRd21TMDMTd3IVR4fXb113U4BpaJU0uXjXPzy1enKHdVINWTVte5n0wU3XeO5ReJMVa0kW5evW32M3e8tneuuzeNvteYIEzM3TE3lXbg9VZMJu99u2897UzwWPbF5xfvS00wRlf8s2w6dXfouNf1dxc32lWgC2y0i3g6Tnf7IzgeXVghX1bD2XXvbVA3R1gib3gUMlgTRTZ7eSjB\/6MuwvhwAW\/9rzWE76r36W8DeZgKJlQ7FPUHolcjLNgGz6s4RRhID7ilkmcOEth853+x56VYQ6F2BI+QiLeFtq52Fr1RR32kEHr4Y113x0JYvub4iG24vj4WVCaUalcXIv0Vf811+M1t\/i1yjPejlRlqzUevwT+oTeG49D1XRzeEwE2Yzt24yXWYxrU4odt4wa0Kjz+4x9OVCr+RJ01REO+4ebFsugFYTRt5D3V0639WONlXi5mWtwzWUwGvkA+kiLt1ZZ1YkqWuPtt4kl2ZQOO4hG2okJW5Xxl5ajA0lcW2lgGYzgU5azNT1HNZfyY4MPUKlles17G4MubSGQ+Uhs92PX6YhVMZuKF5qZL4ubk4RqWZgjmwJA00lWSRv\/0YgAtZuEdY3tc5kacN0Gmx3L+PspzjkpOfl38NdMbbWdhZuayi2ev5eMtJGd8lmcz9a9OVmT+Tdt\/bmAadmdvjuRbVuBaHkZ73kaFLmKpJeHJCec7HultTt\/rZd+LNo9gFeKE9milFUdsHj3FPWj2azIm5lKVBuRJXlFefulZA2knzlxGrmlQpmWOlunnjVSqLGl29OmfDo52ndremudSa+pRLmqzzOaqreqtXuHbe2qoHmipHeoypOmllk\/opdfi42pT5tuu1suwFut7rcOyRtKzVutD\/mYGFuavNmi0RmjKnWvIlWpdYkNG9WeB1OifOOa+\/lvCvmraRGp\/fUcSdeayfauBRWyrzV\/6nWxpHRb+T3XrmJTrI5uj0b5izDZr6LJmdeZry\/Xqrv3gTwZrl3a1MWlmvb7cVkpcogZsXP1si0ZnwEXtXbRtR+OLbh5hfa62s\/Xruo1sIdzrWL4W0X5uLyxt5bqejMpjUXJu2hbj6ObmnNbp2ZVjVJxuowq77U7Qx2qddy3u04Xr\/+vf8uYhnlbF9Kbsj7vvFw3kqQLv8J5v6cZe+47R89Y0\/ZZspatnNfptZn3mDRZv0Qxw6GvclDZwqGLonj5uZGs2B89rw\/1DAK\/pxM5oBceezjVpZc5wjN7BrG5pwb5tv9mpBxdx\/RTf3zbxFw9xyHSJr4XkFtdwxV5sig1uaGNwGj\/+JBsH3xTHzTTdcRYv8g+l8hUXrYLOzuxmrdNe8h6HbQxCYCa\/7Pj23OqF3YoOMSzH7g437SeqcS\/H5cjK7XG2XmIVaA+fcO5GcSSvuTh6c8tu8ria88n1Nhdu8zxvby3f8g8fJDiP88AadAHaXw8+9AFvPUW\/8h4Vc8\/ecxWucH7p4FHs9AAmc7XE9EzPwU3\/8qWK9F0OdSvnZyFfaQC2ylFPULrjRFVf9aFqdVen8zsHbVnfZPy2Vhl\/tr2c8rGWc0S3TPR9T4YW9kTe8Pw+9cEGZ1v\/RkvvooAG9th2W+K+bvo0dmsHagq\/5v7c9fGuWXD\/9Huq9noD9Kg1d9f+JuZA9\/aPse52z\/Z3X3S7hXEBx3bUHOak9uEwa2ylxve\/dvQ1H\/cZ75heB9x5J0qCv3fLPvhZfuwXhvhBPnKcXe9WVvNHnyDOlujWhfJkZzuFj\/fabvg9Y+\/llngHpXh1X8Ygj3asCnl3Z\/NKl2QOjfnynXmc6ztYv3ntRfA2C3isTPKX9+98jmgiT3p3JfqiD2NW5vCWx\/PUcqSFR+Ej\/7wof+iOt11LFr13ZtA6yfl93\/k0Z3Q35\/q\/8fpwJ9tSj6myenaaD+YYpvu4XnsG4\/JGf\/vU3vO9z2S5R0Ztvvss3GnoZvb75Hev+nsC0fWuH3zDL2V9P\/ybRqqLB97+OF5bwj\/lx2d1JQf8lRf5RkpOsI91s19czk\/nxZdvbddz0ed1ty991\/dloT9o1c8g5U7PrMZ9rIZ9CW78jc7Kvq8vP+9y07eXuJdZ3j9Bh6bRh7J5qn\/vjRe7ixp7Q+3ztkfd4Idp3Qds6Net3aFrDLd+Rkp7MstU4Ux6cV76xAt8wVf88Sd5qC9wzNdV9E9\/CFt\/nAUIAP8G\/gMgkKDBgwQXMmzo8CHEiBInUqxo8SLGjBohGtzo8SPIkCJHkixZMiHKjSgTmmzp8qXFlDBJrqwJsibOnDp38uzp8ydQhRR\/TgxqdOZLmRKNMm3q9CnUqFKnBi1KFCnWrFpHdhT+2hHh161ix5KdGbYs2rRq115c6REn27hjlcp9yLMuXrJXOVLNmdfqWb59BxMubPgwVcA9\/zJuzLGgwrMsHVOufFOo5cyaKQfOCHcz6Ih0Hd8NbXqoT9GIJ2sebXc17NiyZ0tVXPo07qx+F7LO7btx59\/ChxMvjtY149vGTe9tSHsz8uUnV0tnCLQ69rZurQfP7n369\/Dixy+Pnle5Rtrq+2pf7LD5a\/eZzZN\/S\/j79fr6Be\/vj7G7fwEKOCBM9NWFnmfrKQhVewgO5OBSEP5lIIEVJeZdfhXW15uGBALYIYghisgbh8DtdNmCKR4VU2rO6fTRifOVOOJ7heEHH43+0s2Y43gf8vgjkOFRGJeEQdLUInefwfhiSzum56SIsd2IpJHEQVlleZhhuSWXvw3JVpFdqkQlWEouaeaRWtrn44DrTSmfmBMy+SCbceZWp5156plWdCo2tWdIOEJmE1do2lbVmWoK6OebYQIqVox0KvroaXhSSqNsl0bYnZ9MafoknJISKtKcqP2ZqIedXilchp8eN6mkrvpmqawaZlprkoqqiiiuh+5G4qik\/mqqp6jutyutrAraK1YfrsrsebBCa6uU0\/aJLJnT5hppmdul6S2xkz5r4bjK7hpgq9oiJW256qqVrLvk3Qrttdg6uqm92DbIrahfshgsuTO2i2\/+dvpWmG68ZQ2c8FzSMnxstfTumO+9qlGM7L6l9rswvv5um+S3xhkcIsIPb8Wxyc06nLK8sGlb78UAJxizqhkPuzG8HaPcb5kh+0oz0EHXprPGLLuE2c5GH72y0kIa2rRgugqd9KBTK2izoTcH+vS+sRZap9Vhi81r1BVDnSCwZ4PJtNrYad02sFJbjeLYs2Ets4rCyrzmynX7\/TeFJcNtX9qDK8y24SK\/DTfMQtMNOGJ3g4uz3V9THfed8+opeOIodt7w5\/oVzfjlXkLu8r8I5j1dzvEhHq1hrnIeeo0Ct0472rj3yLXaHld3OuoBq55ik7e7aPxawO+teLa6H+\/+WunOqya9eMu37buOyh8mOfTEVzak9uHH\/DPv0rN0UGDRU1\/j+k6\/zjL27SeV7eqWs963+PmPXDa\/8oeFvleQJz\/2DbCAxYmfAb\/Vv\/olynoNCpf+Irgg8jnQeV6hU+ESuDUNchA3jatgBynYvQn+a1vlE17mxNeo\/rXvfxjsWQgJF8MZfm9iLKShCNUHKcm4BYSKSWH4Vji69Y0qgO\/DoQBxKCMdxumDkxNhAZdVQyNqCYHdAqL2hLg4IrKGh0ekYRKVqJcnUo6JW3Ki78xmOClyxovxoRsEJSjHylksVGI0o\/++KMbDaYyNtULjwNQ4vzkyKHU3nGIGQWYsohH+spGxY+QJqYccPLZQj3vcoXvIZi0bDpF\/ZGyYI08lvEMi0mvHg2MOQ6lKp6QyjE27GSW5eMkpPvJlnNyi6yKpslVqEpKftIxSSmRFUc2ST37UXW+S6UoDLrOYJqGOugCZLEGSzo7QkUlKfEgwZ46xebLkTiK56SJxwi5ytuQUKWuHS9ods43t\/CE5T\/bO0OkqnPF84T3XFiwW6pJS0nwdNa9HP+\/NLKDqfNgvrTTPz3XGjfkk0UP1OcK99fNR\/\/xPOnXDy17WcYEE5dswn+euTirUm1wEWCwlacmI6u2WTqroni7KPYlu1JqezNpHTdRMTJm0pDY96QhZik+hdtP+pQCC6eaMmlB1InVpNe3p8zxKQneulEfFKthCmbnTPBIVVA2Um3maaieZGlKbZnkqVE2oNQYWb6UhJVkhsZrWDqbUfFXNZ9KexjuxNlGpaczoLtEK2G5J9WoFPSry3tom9mAoq+ZTJEu3Wsy8UtR6fBUTWUdp1gIJVo04Ymscg0M18HW2tGmdnVbteU\/JznK0eLPsUjWV2dDWdZM\/tdxtmfpatz7LtL7NKGqjaESisvaSro2tJ6Pp1\/GlzLFlzW2u4oa0xPb2t9aNbXDpWly7dvWwKyJs6y6L2eVeDH5z9S50TylSGPKNttftbCvv2jvbyTeB273jUz42JuRe6p\/++TJvernHV5eacr\/4e+9148tBWN4XmfUlZ1zB8rgGwxWdc2vued27zk2VbVCo9CWCn6pg+zq0wA+lMBjzq10LTw3ABlXri9mrW+qCLcTWHXFqwSnjEz9YnLWlJ3kp5uLB5tK5B+0ob2tsY9\/iOIp2UW08UTzDH+fSxUUG2pBJiuQMo7CjH97ykkUMYsW+sorDFaqUY6jD5cU4qSx2HIYDLF0jAwae7R3Of6NE52pOjsrs7DE3S4eePY81yHk22UJFebQSLpI5NBtRdnGHU0Bztbvt2VomI91fQ9sryxsuI5Gvmeb7CVnPXNYqpStp6Ut\/dTAj9fOUT3vVLCk5zPD+HTOsEQplCKd61Te90Dl7bdyeKjp7tbY1WpssXMns2sfC3mO7uhg8iY16xRCatVwPjOxk47raf\/TWmSP7bPyaWU3S3ufoxNslMkd5oBx1W3W3zW0wq\/ts\/0NfswM9biUKU7T3fmK6NxvTXIfws5r2qYblTUhl+w+c4Y6otzXYb3P\/W6nJte2+kehuw1I14QqXI8NVLeGH89jXAVMvZNkMW\/4Cit28tiNo5RRxTNcyqqFu2akTl74SQzzjKS537cB75YtT2+exhnlOk0NwBUIT1CzfEKHnq2MT49Xoam6ovyNT2V\/WG0sudzbSp0qapeP20CA6OKqJa\/WCY53iWif+414Fnqev6zvsHJ9wGEn78Y2GXONr1\/nfF6xinNlcXE8f+MxRPTyx1xnGSdT73lfZ95+r3eQeL9Vlu+51skt8425i9JwPf+RuR16FpHcm50ln+fiiacByn3vqFX9zuXBoNJeDfOkdOfmjV371Gt2Z5jef+GW3+VXTdfuXf537CO4+x1RfbeAtTWO6A4n6k805kXaO\/EYrf\/n5a75w84366PvegrEn\/uyzf\/ygc\/\/K3mf+6TUu\/uuX3\/fWby329QnZ+dfx8u8\/HfgNEM+VXP2x38lZWwHOWPBh0tscl7b9nwQFYKU9X7slIMrtGAXm0fAJYP4lDz9Nn\/9B4N9IIJD+Dcv52Rv5pd3+YSDxWaDjiZ76rZUA4Z4Ifl\/8yR64nCDUbKAGXiALciAPVpLd1Zw8VRUN1mAQ3eCwpaDUuSAG0hfbBSFQFVbTgcb9ic7QZFvxmR8ToqATPmHWRWEXauDiRczJJB\/iYZuxyRmJjWGZfSHVQSECwqHBPYdWgOC3RR0tbSEKBhWaueFJ+WAGCiEgqtQQas5+WQwaKiASAg8JlmCfSaHRSKJdsZK1NaKNFZ4ZYk1ULaImYiLkPCJDBdAKVh0cVo0aoh8ovtcnVuFzndvtxdsqKo8oAt44\/SCvnWKEteEssuJZ5VwqFlQI9iKWKSEH3uIg6tspqh0xJlj+ESKV7QXfETbj2NTiGj0ZLjrbMv4hJXIhH94Nq+mXz5AeNTIXvb3e4GAd\/w3bNopbNzoYGzIdrcjhM8liOdaNNaajma0jtBViOyKUDhJi+tGcx9Dj\/QzjPXaaMR6jhPEjfv2jdoihLnZgqyGOQY7jOSZkMWbkFSYMg\/kjhkEkZPTfHH6hHqKXRVrinXmQkdmh+8QjJBpgz21jA3Ykw9gkv1EkSEWbShrYrDjX52nRApZg79Eh5qFjHAXbRMJkSxVkT4IKSGqWlrnftMGbTtKTQ+bkMt7FUBaeUholUxIkTxabMLKeRu4PVSLl9WBjMo7fUh6fWj7XV5rkVUIlTnr+4guepTl2311u0v4FpNK8o6RNnJ91pVUBpiF+4ytGJeaYpV7WzEL2oIdlpfyB5ejdYVye0UXu2ktFXGfq0Wcak+GxzUkuJmNeEUvGI1mmZV8aX1iuEcAJ5k2eJlEKYmAh5pt0om6Ko3TxUdblDNy1JmExlVQaZivi5mVaYaIFIyPCoHLW5XzJTVE64WbepmyuoVplZ+hp5zPWnsC1XmZiVCQiVx0GpVjmHXL6n4B952u2RmnuYHVWoGXa5rqEZ5VEYw91BUrtJ3nezvnYxH\/m5wPqp0DARWi+ka9wJ0e2nXrMTCu2FEI+JqNEphBOJmWm2Fsy53nO5XAK6G+63bH+2SUqjqiHPiCJZtMkpag9EuflHScRHmDovdU0Smh5UaghJlJ6AmQMVuJqlh1t0hryGShijWZS2oyHCWmB+lCAomiSjid+6h1\/FOdu0ZF4RteFNiY50uiELuh1cok6ZqMyiqZxykpcISKuGMiYDp2RbiebymWbEt6bdmfRNGgiqpYDRqiW3h1rCmev1BOYuqVrfhovmlPRsd92HChBZuBkHOpXBOe4LCo2NaqKfqiiulJ5VmWV2ikeZmmeMl5zOmeF\/qFrrhe5ueKZEiYApSql6g3P6CakvmqU2obXMCqRIqijouch2gimlaKF+qRjdqp57mmO5uF07tD09COm5iH+XHqnkzIReDZrMz3r10mroHkeY6UJr97prwKrS\/LlsJJpl74aHyliqRIqszwpszIpKhJorG7ZkQIogaorggYdkq7rgfEHtT4edIJSTSbZgHIrWn4qnwJYsWKmVYyiJZmpsu5mb5boiDJshzmsxE4qiDZpvFqsiXbircrXe8qpo+JljAIsZHLpj37Kt8ZLtbFJuB7Myp5rfL6rgNqrguIrvGIsu+brst5szRYpeDVpu+JdmnLWnM7gyeIp2rlo0QJt0ApUySrX4Rxs5ySty05fulbtNPEnumpf1uYsQGHt0nWs0hmh1HLq0eYlqIpae96oqI4R1AJe007t9smqq0r+KqyGC4u+6qribaq2auPRaqsiKn0KbMtKFPNc605OJZ6BbdQOrsT4ZtraVqsBFcT+bOH47cM2nqFGaq1aruXKrd7OKsWyaCtV5NnK3L+K7Mh6K+O23Or26d+9Wx+KUtyVrslabY\/hbD0eHu6uac\/O7O52G0ruq5gaLeqaq7C2rpsVbAKW6coN7I9sranQLLTeLnvGLDTubL3i7pLaI+I6HewCE\/cWb6fkY6iKW4Z+1+w672EyaMZ+zMY6FXAy6cWK6MRuL3E6bItuq2I+Y+0qrk49Lhcq74EgrOw279juThgm6HYCbu6uicwC38XqbMy+Ub1KKXZtT2ioL\/4Jb23+mu+7SA3CRq6alhnVau0BK7A8ziwKryesKOzYIS88AjA8CvDvKdOQwvABGU\/6nvBLyqSXTe4gObDd9iqvgm7dwqBzSlG30pJICi4O99Xb+qXj2u9xzmbCGvAT\/w6qwmjg5m78Pirdrqp+fS4Rh2PwfhKdLlEUT+FAlu9MdtOcCatH6jAWr3H12G4GV+9fTa\/v2qfSovF3ea\/clYu2NnHI+vGf2bHrPq2VXuC\/oSwdcx0is64Js+u8tpX0QmkF0yv2djIfQzBoNs+pjTKUtl8Tl61kejDoTB0BQVRbylYkm+rCum\/othWl9qf9vm\/9zq8ux08Si3L3Kp94rkohG7L+IGfxuiHzpjGyK78GMvJwqiQruIbh7\/podoYvzELv9kqwvHKy7r7epdKuvY5lQhWzMaOyGxPgKjezD\/MQytrZIRuu6\/pINTdlJXctH8dpPa8wz7LwJ8rjPEbwJUMl8YqvrpLs+CnyqU4xtwSTO7\/ah9ruQcOt54bx3tbp+5ZxlQFxRo\/xkPJt19jl0E6yoBqxH64kyRr0JjqxQsNeS4MrQ\/eRCb50CjVnPAMbRfet5oK0IVWqqjINA3PmR19ub2o0Fw8zFTorTNkws4Jsz6q0pyKtMmsmTdduHmvw2aEqukmz7ASS117tN4szA+IxTY20WD+1GW\/Mn24T2UL1RB\/+b1Unc1wv83w2ctVIpJFu871usrsGNeh1ce\/qtVHXcFJH61K\/mc+aslS7tSybLTS7NA2XH2ECdmIe1omuKP52DFEPp2UDscbKL8YGqllP62GPZlzOKGNTKVwndGSLmhWT6lp7o1fxdXIJdp3tM9GosDer69I6sSZ6sVOyy2k\/9mBycAivLdpyNkTbdWzHMN4h9T1DN9dKt267a5xiZq5S72atqGJDJDoHInKrcQTP9VW\/XQIL3nlrdvQ+LBnr9BF\/SXsXNRi\/N9mFs2FvN56EFFYjqwwXd2vjRXsTNxbdKl4XNeaqNxnPN93+bX7vtK3Ct0W\/8iCV4VkH8\/Aa81P+93Yf\/veBPJyAp6ZefThPRTepkbib9i7vSiV1l7UMlvQ5gu9Uw6ZxLy55W9Tb7hwrB5tft+B1+3DaZO\/OJqhgY3OKZ\/IJCkobg5oaY3iGT\/Jxq\/JYKweOs3N01niFlVuIfnYug7bd7nL7hrRmn\/UfU6xY9fcvnm5qqzZLs3Z4e+xRkiKVMzmkvaw+ezJvK+l4Z3O8CXFuB+SywO73Anj4prmaLzabQ\/kHt62Py\/mVP3ed5\/OJw2mHizlAq9wSvzCaEzpXNzmlP\/kbr42iIyOjj3jcqjdvKrd8D3VHe85AVy6ZqY8SF7r+OjVN+q+Mc7hGjfo8YzlQD\/Wim3TF+jT+boluqnPsqxPbSltwm+13uSa52iK6Men6qZo47X211dXzUHadH216W\/sro3\/3s3+6BwqLtM85tVMuLhI5inNYIgK59Qb5bMP7GaP24KF0fca4Ps642+K6ygxxjpc7y7Kvvxs4wXeYZ5\/6KF02L3M5EjM8As2TPHe3F3+7rec7v9fnz\/w7wC9WehOdpH88ur\/y9b47yd95WJu8L5NyvdtPYFG8vt96m2M3lcBinG\/8ojggWa+7zHs1pIP8yEPvvIPy\/iYn\/Ba0pj\/lml+flQ9cTM80yuG7zZvygXKug\/PXjs9tr9NX3kb4eq+6P3uJzBv90WvoMS99kIg4XctI5kb+PbUIPNYz+E9LuNdHqdX7unp1LoLbvXRreFHAeKaP\/YsautJf\/LhzMkT3KBn2cc\/LbJH\/fK45foNV\/FGH7d8D\/n3YKO\/F\/AtvtI6jL7t1+vpO9wLn+ZB\/tLt38ynp0tX3eOrLexHnb6D6vdhb\/jGBeyUSfqF4FdHv+lWFp5OHPkc7\/JZTMS0P\/\/xqeZYjPLFHLJcfPPAytOzrL+0H8moPvub3e+60OgnDor4yO86VOsicfoAyfmCTfp6P\/r9W9+ujP\/mDvLIrOOSfLL1PP05X\/wbjPrlvMF24cFeXsOjrPED8AzCQIIB\/Bw8WJIgwoUKDDB0+ZDiRYsSJFilm1Jj+ESPCjg0VbhQZ8aNGkiVNnpQokmVLlywLulQ5k2ZNmzdx5tS5k2fPnS1vvhQ6lGhRo0eRJlUKcmlTp0+hRpW6ceBUoCutZtW6levIkj6DdhU7FmnMilVTPgx5diFbtBfNwm07Ny7EukLXyn0LNa9dumrNtpVZ0yvOwirJHr2bFmxjx48hR5bM8yrhxJcxZx4qWHNnz5+pYhVreLBo0Kc7k+Q42SFq11kX9\/UYeK\/c0KaZpgWJlrNtxbX9wgY+G\/Df3bjdImacM7ny13qfR5c+fblz6texr0aenXv32ZhJE3\/b2nv5oijFsx5uvvvdr+RRo2f\/9L3s6qq1\/0xPE\/v+4vn\/ASQqrAAJvKy3AhFMbTvhbNKrKvkSbA+++yaLsL\/eLFoow\/UE4vBAozZE7sPcQORwqREdrA1Cv2aiMCidsvPPwhnnG5DGG5tCEccdnTJRuPN4i0tHHk9bcT\/WiHQttphCatI\/+zoc8iUnP4QSysp8\/E3KKIe7sjn8UlQvS7xgOnFLMdFMU801K4SpwSThxGvMOOn0CrwFVwvOrjqVnNBNL6f0k8\/EnnywNQ39rE9GAQ2VTdEtWYRUTkCJu23SFr9EsscRFw10TDZBDVXUUSnlEtNBUSUx1VVTuhNIPb9j9c5SU8yRVlnpG\/JRFRMVVKldsTLyy6iEnerNMA\/+pIwvTiVlrDRSoY1W2sawPBXXOJu9lsg5icWTrUr31HY0X\/\/MtjpxuSq0uC5pW7e444xVN0h3VT3sXVuN0\/JS647rNLxl1+v02WenLdjgg4W1Ed0dzV14Rm4\/w7RhhzdjUmGK2VsyX+3gjbLjiUuz1NTcbv0L4sI6ZhTSY480keUaz0RY5pkLrpZfjC0EGecAT\/bsUHJ3NlPZoM1zD+j7wnwNWJUFBvLWIwk+KVObn76uaVNpzlrrNKkGk+gEdf66aG+lsoxjFsXudui0Y8TwZxEbNZpKkc+u215eaUW06qgNijlvs1vummzurmab4osNz7jnxMtbXGj+GI9ccuj+HBw5Ocsrt1Je0\/6u8moqHS+3788LfxnrLL3eKmwsJxcb8dYvHBx2wmUfa\/XZx+Uad9a\/XRpFveUO3u3O6b579NorPp70wU1PPb\/C8QU319B3T\/L16p+7HXufkc9dzu1tVxN8Z41POnnh8Ua\/+6SKBRj68292fmqrBKuf+vz43lr\/\/akV\/ebx+7Q+AEZMgOliXgEHmD\/JJBBt38JcpOp1m3mlDIIeeyD7PDSvCXalZNxqHrTgJ6T7uUWB\/DPhCQHnoqMxMDPaY+G4prOXf7GwWfbTHQ2T1S6RdZBeFjzbWrSnuXztrSxz4qG1+gUqpv0Pg59C4ROhCDnjye+FmnH+YRVVh0CtyPB646vheMT3QvUt8WTtg5r7hjfGAC4PiadjUxPb6DQtYvE\/XaRj+OZ4xy3msWzpO4se3XisNVVRjX86nQ\/LF8Lj4ctiytPbIStTvCJ2ro3RamLLEPg+QBbIjpvcIx89uazoeI5XgPQJJs24SY05EXiOJFiJWslIV4LOldV6niw9VUkQkglWNvzVFUNJnU4Gs4+gJKaZZiVFEjbElD1BJRODqS6+eaxYIGvlCKn5tkP6rZCXzGX8dvlNERpTk8ccWwrN+cl0ggabcpRaJDvUzLUl0XSeLKf5zojHeyYyn+IM1grph05L7guaWjLmOuNTT4SqbaEKAhD+QL0IMR05M5TS1M0FKYgyfWEUlxYEohE3t6kDKpOegzRoQS\/V0JwpVKXRa6mBDgrTdiYuWyDVzx1XyTkdXtSW7ozg9D6mQbL9rp1HRCntHAnRw7wUQcNkqsqeSpaZci+qcJwhA7sJyelpsn0sjVT6gPk4bh61bRKJJRyr+lCvpjV5bDUgAffJVp3BSIxpfGSIJIm0SK6IonqFGlFjOizBEXGUYCRnWN3KwbUmFp6MjZeCqOjY\/ciSrLjL6choecueio6jJb1qem4pxKlKcKjo5ORo2RhF1a52sISVLCJfuykrxtGx7xSpbbFKym3Gza6snCazIMPT3vXKtVD1Zyr+WZXa1S4Xha2Na21HG9vo5nKZkiUpLE0bUR8t7auHpZR6rArW6YoVtztTLnPRqz\/nIval7IXurDTqVtoqMrvga5podYpYQM23u1wVKsl2ajfjlvC5dTpvehE8s\/V6KLbCbTAyYXqVcNlJnuUl42cHuLy8Sq+YU+yehUkL4CGG1KcEZhZ9nQpXbyWYxVpbsGiQy1T3Mna8bpJwrCiMReWY65QVtl6MkTXN7uIziRc2ccAOnGLusbLFTTbYi5EVWHk+OFeusvEzixu51K2sr\/b024Z\/GmbyFtVIvuOteBdJtSPn92j9s9qMqTxmr7VLynqEs3zrHJobe1apu7vSRLv+TMz7krhyl1NbgQVrr9A2kl1n5eyan+cvsJQ1z3EOL26HR+U7y7XSAvYNn7PMOF8BFsOC1hVxswpk0rqsoAnLYFLTzLFqetC0IMbydQtbY0v\/EnCZfvCm06rrpVIOx3rW8ZxfjWuECoy7\/dTqmFfNRFVHunbT9rB9bH1rZa9xzU72NpqgXGxxW1fYeJ5tZc3JUpPisNFB7WGhTQZKcs2zyJPM6A4RrcKcMtvNMRzrtwGuRP9hWqeaLjenIWvtikJz3bkFzkd7CFh3o5HNN\/3qJDfYU\/d+EKCOERC1EZ3kgI9cU9fuC3NofPBgd9p\/vwbnDR3+T0f1ud6HbnSPa+X+zjLSHOMvL1VkI+tg\/lL3xSQ3eqBB\/hGUJxbYVVU5ZdHNbjA1PIGp1qZuFH6uKEf75jbdLdxgHbKTWjjo9OT6+aIcNpEfne0457rSSy3jp0d17ryur5fhQ\/UM55CUvF20frHdVUFBNLP47Wyhde68srvx7JPKVIHX3nbJL\/3tPG9w0+nOcpcOfeGYJ6RusY7qR8aL7\/4NPL\/nZuYVWz6Qp\/85hBYPKw6LmegDn\/ztKa\/vfHNa85lPpjJZn8661\/V+zWbQwwm7q4ET+dlCvjBKFg\/0VPLdgRjvNu6x\/\/pe79rT3K\/Y7+MYfO93X7v5\/aZmJV7ii4\/d0Io2dLzFDH\/+2Jq89IMGWuxnP3+1\/zv7\/T+qjTwPp4avvXoPXPhL\/LwPAQ3nssTuhwhtX9qP+SZr\/eLLgSDu3ixH\/hZsfqao8TzF8dBq\/BKq10JNpQKQAMHP7k5QqgLN7UAt6o5rAFMlq6BtyOAn59JOsyrotnpL5mgw6VyvBPurrT4wBEWQnV4G\/5xOBk2wAHHQoLzD8FqPpFxwCjkvBoXQdXqwAcvnmmRH9B5v3xAPzLJp5sywkZpP95Av6zBpwIbN+o4QCbNLCQnQCVFQxXpGAQmFxFywCqsQxbJtexjw0Tgrs26QArUtAg8PAw1xDEGrEW1v8AIRxZimXB7n+vwP+8LNA1P+zg5bigmpbfka5wHFqedgbNOQDoAsShQNCa+WyBGtUHhg0Qb\/6tUsxRUhLQzlLA83R4siLxP9bxNZEc88sQlzreJWsKNmMdrGDhlRa9I+r\/h4ztrO0AfFyxH3S\/SeMflqDQZzMPG8EX+KDhhvTxgHphPjkFGU5uTYEK7IkAPpy8MuLfei0fwuiocm7hV3kBEHEQNBy6N2T\/4kRaG2TQ2bkQ7fkP7IMfvMkZeYDhQXCiKdjUswBiHNLgYB8e6I7xT77l407r8O8R9Fch8z6rtAMoh2yqhsrdTijsDcBxMXsu0aMg6TsaFAUfoW60ZUbSdxst98jBZrzp8k0P1GsrP+qnEZQ5HirJHJGO6NkGr1YjIqZzIhElAi12kAo0\/Jmir42NA6KKMdJ8fq9kb1NqskexEboacrz2yRZq0gnZLS0hEPfa4m7csqhc8Od0+t\/o8r9TApY3ELO48juw67Km4RL+goFfEw30cIC4\/RSqsg\/\/IP\/c0uj1BhJpH34vJ7ZEojeeZ1eDILQwzUmDEwyW\/+QFDW2kwWEdEse8sNTZEtry4XFTIVhYku4xIATSbObPMq8VIrozD3PjMvQe7W\/EqVdpOfdPDeQgQxi3IPq3FxcjIyfTPtrjAhM3MzrcXXLo8yj4k7seWmghPwCA43QVNyVtFp3pEkFVEbyU41Ze\/+kzbItaLzBTkTJvfPO6uSBGXO4IrRJvtzBiUxL2gJiQaUrKQtuEgTKYlSPR0sOZXzusCQQUkvHwnzMsMQMjMyHBvoOsGP4PLE5f5z2fByVbRPqICTNj3LPsHSPAGToJbSu66ROh30gVbU+eYRAesTPqWRKaMSGIWRXfiTQ\/eMBUO0hcaKPrcM6TTUJwUQzUIGr9rylVYzcJrjCXFRR7FpPkexBH+xRyXvR1vyE\/Fz4X6PT0rnOP1yF6cTdvoxxBrzSAlxAk0iTh\/U0XSUBy10SwOS\/7yUISNxQuhxCYv0Ls8NTQ3IpgYVCDG0v8LUz0Bv+bzQNBNN6yayFGcxUg\/+das0VDpWtEv79OjAdE1NLVG78z\/7MnYek1SpszovNE9nB\/LY08joNA1tpkqRU0IW9SmByVM\/leSmUgQN1dTksEaLhMvGFEkVLlDLrzTh7SQRFSrrj9ak0AL3VPO0dDKFUzxUtFed7FfHL1ijqUg3tTYRFWdy9FXrrwgv0DDzLzSDTB6f8B9REtiuNXtOVRz\/lFvZzlvzU0iv7OMANjF10v6O1V6J1VxbVCiBUlOdsS50qRdhFBXP1WCztbpmU18Djl+5D1zJ9EP\/6GNBtl+wS0+Bi87oStss0kgrFmGdlP+SJv0sdVVpa9RiEz0PtlUL9lYDikcx9kvzdWXr0F\/+c0xOKRIRHbacxtU5l64PUdSqNLUeL4eSCnNa1Qxnqej+HJMw2RVPUxYP9UlolZZVgxRsPdbsntPv3hMLc1Zm2XGeJFMF5+heiUaaojT0ztZF1XBRVghT8bbp6nVYlTWlyDZ3clU311YAZS9Ap1QgoJDYUDVXV8fz+lJugwb1stX4qlZRWe1pkOtmf\/ZwNddVyWdwD7Vwx5Z0i20NC85xbWVoH9dVI7fu9E4VpWRdxUrEMk5n8VV3FxQCJfV3wZFyCTdwaw91j09sQdR4U\/cUjW3CiihtoffNLC92C3B2lxWeslAgQXIY4RY6Q0cDJTR4y5NHgFZ5kbR5nZcYzTf+nsYtg0L2N9K3bOmEesMWvDZSVhn2RQVHzuRSf9W0fB+GY3fNM9MIulQ1XOPXfeECwpjpX4\/Ru4Smfksu5p60Zj9tuFJPqUSXUe2tQSsFSi04Q0G3WHm2Z\/f1c5HMj9DRfLlIkHyTi4aUYiGY1yS4TaTOliCxOTEra6sG3ZCXQQ3vTdFWfGVkR7Nu5+xuW004vbwVNw6YbQTYOBm1Ixp1gcftfddxV0dIcsPohrtwLZkPhOfm+diTGmN1yCKVb0XYiZF2YquvsRoXhZe4W+VYSKO4mfxtioUXjRiKhn9kYRMPam8VczG3QpkUf4Oy+QpZNqd4HNdnII14fXfWdJP+l4V7k2WqWGW3MYKPV2TJS5B9Qwq1990qyJoegwzBV16dNeJW2UXR4zLpbVuTWJKLiZK384m9DETOQ4\/HF3APMgmbVm2ztJdZVjA9klpHbGOQmXqgcTnQzxYzsJWjmZRnM+nkOMaCmXdoWSm3DB7V15Jd05BwhVqYNpafjz\/9F28BmXt\/2dXuVpHC+IxR+PEceSzNWXC3OX+R7UVH+HD6ua5i9m5sh1ydqZwtzqVuWX9ddp2\/OI\/wUYzRUM3SGIzVuPL27dTe1oM1Gp\/zGer2eetWWHm3q8xqFMkI+p47ejOjNrVm9JWIGYd5OGthOpaEuJY28F0tWsm+aJZT+n\/+j\/afovep7tjO2HhvXS2ENvSBD7qnNRk1iWej92qPG\/rraBWD0RCiR4+RL9Ki6Vl6cdnScPN9\/3lhhhpxhzCFnUSgu2ZrE+6r6ediMxp7LDdYPbc4FXka5TmRhVl1JW0lp0+XjZCpXxPZxNqtK3esc4skBzN+qbR4pNrmHqarednj7neZHZrMTnBdNTvALBt370U+BYqvso0OkSeSBftGAXWkDdu8ELvqVmJJdreB3xjQLPakwYavy3iBKruUMRuzzy0fN7sjPXu4gXeecfpCORGOrVOOThu121acH3K16aiFRcgvTdqKWwW7KY2PUjb2ejC1Yc61nVQpV+qdnY3+kCc3tOet1aavdmG1tYmRCqE5pI0Xt\/XWr7E4gaVbh8lYagOxgInNeutyvKF7qjnVFskSqxuFqtUSQuUDpUj7z7i0rBOaQH+awll2fZMtG6GPedFXtre7eyeRJfEGXwVcENO1LN0UgHvu7wazpneYpmXap+0bzUxyen+Quet5jpm4jmX2tTD889a6A9t3OzJZL6\/nZC9YUm3IjYm63TIXZerakF8WwbVJwWFzjD06QOOa8dTvXnmVxxNsJn9YRPdbyM9PSpF1soO8xd01fDnqp3kKpfEuk\/D6pV9zKAc5r6taxGm2y7u29Q5NicVctZq4zecWvmn3Vb4PZc9cabX+OalH94L7zmqnfAGp1+vYOlPjnLNDmQ+3F86duyOVPLnPedCbu53xvAkfHYd4L1VZV4J0EMBzeoMHPG6flfaINHc\/Xbh\/d5TTc9TRmnjb0GlJr9U70ZalS9EjKn1LCcTZ94pFTaJK\/DSDzIfpnHZl97GbEZH73K41uSmJvdh5+iVT3ZAD3YA1\/LWBesKeXdpNcX6pfT+tnW0nJtvl+sv05dJ\/naKtPIO13KqzGj1VHdCcaoOz2RLPnYgv3MnTjdnr0tnbPdqhfaaSFleJsF3T828tHdE7E3XuKYcneWpnHIJgPIhL3ncZXrULF+HxfbkXPlQ5flTXXeKx+92hfbD+WZyEM17Xl9zUt5zbyRqjTS+is+huKxrLy3CMKjrKXZnwXB6bX17WY36Nq83jxQXr987m3X3ic35kYx1bjRXWJZ3qZZjftWWud27V0\/SuzTuR0dueuVHZdX7l071or7nQe\/xiVT66a17c3h3nKX6joHoE3ZnstfUcSdjhtQz0bPfRYPaNe32xL5UUgTslQ97Tq\/lrwlzvmUtjsQbIIR7F2dywGJ+xtfvA9xLkby7SC\/XIW4cBUxnffL3ylfnnNb7fwe7yb58IVfLu5b2EPZ+O+V6tRR\/ZXbv0zergP1x+z77s9hm\/OAP4df70G5\/A9Zp7437nbQ77oU6r20Olq\/7+6bGW+4nv7\/fQVlF\/\/RcfjPMe7ES1+ukeXb3\/Sm1D6Re2wVH535l+vgVe+AECgECB\/woaPHhwoMKFCBs6fAjR4cCIBidSvKjwosaNHDt6\/AgypMiRJEMuPJlRYsqSLFu6fAlTo8WYNGvavInzH8GcDXdWRInQJ0ShPD+u\/En0IcqlGJeeLFrQqdSZUKtaJXk0alatExn2zMgw7FadY71iLYuWKlKCZhOCTZuUbNexKqcCaGo3bk26QdUa9Xs1sODBhN3m\/Qq4sOLFLBMzfgyZp16cQqlaniw3MuK7fTFvftpRKt68oLmSbqs5tWTAjueiBur6Lee6Qz1vBGo4K+7+ta5r391tWi1s27ydRjyNei9xrmeJI38OPbr06dSrW78+emru5aq7M3bsPbz4poErm59tenP45HxVUy9+eLz8mO1llg5OFPhi\/fzvy63\/WX7+6XdbfHVFlxN4nTWHnm\/XPQhhhBJOSKF\/B2q3FnwKzsfhSxt2CGJq+XX2Gom6oZdUihh6xx6AkL2Hn3EhzjhSWr4FdyFz\/zX4XUoWefUjXDzax5qPOwHJHWlKVUdZktwt6VyFUk5JZZVTZieahjLSyGVjT3YJJlTnbbfSZcCZ99WFyYnIJJvSaRlhmPNttSaSQ+poZ3dB7oljZlCK1NqRRnK2pppMGQrdTR\/qCOj+h1Y+CmmkkmZ5nJJwEihnphQtqmmnNI35k2FIuSWqhmlCyali1umZKpkUesqicANauCmtsb5m62D9FWrilq4iaBOnraqH5aTGHossdrUaCGuzXt7pbLQuoUlqtWT1ha2WpWq6qrLKZvgXldJGdqJYv+UqKqaINQptoOeauyNbX24bbrwNqtvrofm+qVyU85JIZLICD0ywXcXqO27CHA2rcMMqWatVttdCPPGSEnPL5IP2AgtuaFU6rKpf5srrqGz+\/lunkDuuPDK0lTLcco4BU\/prXDTTp6CLy7oMcs9WneZz0PQKTfTDodZ6dKUlqchhtwXL+qWVRf9c5LuyzUz+MtY8B3jvrPAOZ3Wr+KLa258FGrwvz77CRCu6Z289ddzPoh0xeXKHyPDdzYJqtp99N3pqw0\/rpnfQ9Y1tqEeIc51v4646vrDbjONMt7YvS14jcnMXzvm0ljJaW+dz\/it6pgK+TPGCiqPII+lC6+xg6a9X3Sdefbp7+2R7Ckqoyp\/hOeiNK\/u97vB5I2ohwg7CbtLnDMoO\/erMPhf9rdWDLKCK2bdetc2sBz4e88yj3tz1DtMZ\/MzAd997+97zabzu8rvffu3brf8+7\/ZnXnmMcCvfLwDyD27mkx3QLoW5AlKNgArskvbW5j8hySp1ritPqsYnPP41cFzlGt+uOgj+NRBu73hkG6EJu4arFGIQeS3CnABptMINTu2AoLOYDPdTwRuuZ0g3qxkPfXIUaqXuVsvhlfSWlkMdsuqE0hMhCp2YmQ+ScFRgs1fjYhPC3ixOawR64b4wlkQlCs55URIjYaZoRs3Mr2R80V7SNuZFHMIojXQ80O\/Clb8fso9lvmNQ2Pi4x5j9TpBI7N\/GuPiYHMawjgqjISN3yMBHssqCtKkYHLeoqzZJkpE2al675nejKv6NXVcTJW1MuS5U+hGCyPqUEQO2Sc45MpZqDCMtKXkVvczFU6u6ZRoX2aNXUg6KHhMm5DzErAh+zHOYjJ0v4zZLIj0zWLacZlGqWUz+svFSk9aU4QpxN0pSRpJ82cHfOPf3tnOmk5XHmlszndlNwznvbPFkGzbrqRzBJAaY7rkgGvHpMwymbI\/Paokx\/ZQnT7KLmoZspTgP+jaA9iyatpNoIS3aT326DDz8XGAkO4rRMUbNa8RMZALhk7B5XlJceIwjSEAa0vCptKIxfek9a3oWEPXQTR\/9J06j5SIsMvFxNKWcFXGnyqFlU6MNbWfkDuXTjv0UVhRd1lSPeNXC3LRJO82qV5FpG0Le7447U2c5xcobQObRplFtYg8dus7iMdOsX12iIYta13DmdTUXQ+cDe3on9G11r1c93FPAWcmy3rOKUsxaY8f5zrn+vtWpEU2sQdtK2ExOT3OZtWxnmzS0Mw11rPECn2lrOcfPBjRnJ70sRNf5xCyONkZuHaxiXdrPFNrTtqpNEBk529saBhdnFEufJf9Tw8N6jZw85ddwz0e7+H1yuumkp\/rIOtbd6VF\/ZnstWFfUIdFgFqbPFdNvq5pZzJb3jdmzVnvZyzsgCjF0dnXuejkosvQNdK3SvC5elToq6Qpvv\/VjKnjx1lVS3lc+6F1wBh3sJYhNsG7J\/V6A4Wu33E4HwtIqKW2JitLvqve2tY0tb596YBhCUMEcrm+CW6w6GGduWxP2W40teeOw0nU1qZWx6WRr4ncdlWRX0yvjEFuvyD7+lnhYZWuKHfhiWPqYXDOd8oWtvLpfBQ6AfMvx1k4cwB1jeXR\/zVHMztzHy\/0Ruyhes1vN\/M0YVhlMuN3ZmIN5V6s+d8TrnS3xduXDAiuztSbl850LrcdQKjdsjiUpG60IYtgyeamnJHRkB21oPIuZvId+6Ir1PNxMh5p73iNWX0v72E9DEsyd1uymHe1nJcMasoQupAdrvVJRm\/R5rXb1k0EdXF33ltWj5LRMhd3rXKIxoczWr860a04nC1qu9Rrem6XNTimdkdfJVnYy+ztqMcuY2NHzbrfFA1JoI9TZ8CvnuttdbQJLW7hP9XQXpUY1bp\/bvN\/+72eR\/W8LD5H+wm+snrn3bdfdehjSbfazrYFcYjmmGFLMZK6TEV6VBhs5veTu7HwJfmrSfbKvHZcsxsMUVFAuOdKK\/poLVT7riPvbtROP1AAFq0iAY9yR1NtzyQn78eNScKOkzi\/Jxa0obp6ciO42kbVJC\/XlqdXNaoYzQa9d1jBnm+LYLtPBAbZ0325W43nVOceHTt+Bg1x5p1O7xO0bdg2T+HFIjrGaGw2vYl0RlArV+zAnK6mu\/7qJcedq5Xoe7sITnGk2dDvf\/ty1U79I6YqfvGdWzu+F+z3EiB6xSo2Fxy\/SvPJaV7WVzQ50gRcc7WmHvOqP+\/Nibpj0qE20gJ+uQf9GfcD+7I69u6M6Z5oPXq9fhyftJRtlH6N+r+cxEHBxzL3iRt7FiTq+5Yte4IQ+3Lqelfq7uesmdHpy+H\/3bnQVbn0RJ3\/cvq9r8+n2\/KC\/n\/zp\/yXEWf7wkmOewbjm2qXz70+2h3715040U0Y+h3QtFntcxjrBR4BmtHBJdWXLE0ZspHls9WCeNmkNV2fl9yTn910PCIAIsyHtB4Em6FULSIIoKIKylF+BlGbe50e6l1bbx2bjR3Wyt34eCFjVx2ItiIMG+HzDxoKFpU7nRHSSJydrtHxAaGoAxhz7l4FB+D98t0pBVlD\/54Cl14OIN35OSIUrSHbuV4RTNVKAJSarhn3+xgaGhHdzDmd4TUiBWKh+R9hvYvdas\/eDbciBYphhwVaGP1VExtR\/jaeEnZc2gUiAnVRJzQZvTZZ1BbVxTvd9BAROX7eFrtRaeviFfKiDQmgf5SWHX3V5iNOBIdcWbDiAgZaAnohiN3h7jogyz2ZL8tZf2heJlHg8mciD6DaKrTaGMPaLWaVLpjg2KfNFxZd5vSJ+rshQdMh5HxaNWuNR98eBMjeNYViIb6iIqOKMsHWKHDaMRpiMgTUcczh9oLONGZdFufGN12SN0iiPDCdp7KhCYmNpMTeC6xiGvtiNUxaMCviPIfV+kocrfRNEr4dc\/FeQ7viOeAiLtkiJWGf+j\/xFkW0mM\/uojNpIafA4kOz3bV6nivE0jmbYgNH3hCC3ejMhf63okSf5RA8JkdRmdzUJheDmbVNYb33XffbGjyUWgB4lk3e3NmAzktZUkoIIk\/KVOCrplEHCemS2dUOZdB+pWrx4hfnoklJGlf6XauFohFu5YH\/FK11VjOcSlQw5lV2piWLZbZ8Heh3JY25JejwnX49HhHQpitOFLtmmTQh0lMgHF2zZlt8Il4EHiXNJmImYio+XlBv0mDWlY27Ddme5lDuoVZiykYuZkm14mFwHlHf4ipyJaZeBinuplz6HkTSIGZGpcHxJmiHojJ+Jby01hKEXm3Z5mghYefP+MlKxiVOB2Wm0yVKCJ5qVRZq6mZZ52ZupCZzE6JqA2FS1iYEVCZx2GZDQ6ZyA+JzAGJ15WXNXclHWmZvnhZXa2ZzdOZzfeZUh+SpgJZTlOXbnGZbpqZ53JpyHRp\/BFJ\/JuZ9jaZUSFaD3SUf5iZ\/HuUT9yZnZCWHsKaDbSaAE6aAeh6C5paCLyaAONqH4NKARKkYGOmb\/KU8dqqEi2mckSpIQ6qEPiqJlV6F3A6Inl6EAqqIBt6IAuaGpl2edE6M7Z6KoaZ83Kow5ynwvKjc9inAzel9E2k0tKqQFhKTKZ6TQxKQnOqXi6KTTlKVPanBVSopXWjRRem5KaqVByqVLJbql2kl\/MOqlCLijp5emtzQ4c0qndWqnd4qneaqne8qnfeqnfwqogSqog0qocVl4hYqoiaqoi8qojeqojwqpkSqpk0qplVohlRcQADs=";
        $img=UpsUtil::generate_label($string,'khan');
        echo "<img src='/shipping-labels/".$img."'>";

    }

    public function actionCronMalaysia(){

    }

    public function actionShopeeToken(){
        ShopeeUtil::generate_token();
    }

    public function actionPhp(){
        $a[0]=[
            'sku'=>'abc',
            'stock'=>'10'
        ];
        $a[1]=[
            'sku'=>'abc1',
            'stock'=>'11'
        ];
        $b[0]=[
            'sku'=>'abc1',
            'stock'=>'1'
        ];
     /*   $b[1]=[
            'sku'=>'abc11',
            'stock'=>'1'
        ];
        $b[2]=[
            'sku'=>'abc13',
            'stock'=>'1'
        ];*/
        $found=[];
        foreach($b as $index=>$value){
           $found= array_search($value['sku'],array_column($a, 'sku'));
           if($found!==false)
           {
               unset($b[$index]);
               $a[$found]['stock'] +=$value['stock'];
           }

        }
        echo "<pre>";
        print_r(array_merge($a,$b));
        /*echo "<pre>";
        $merged=array_merge($a,$b);
        print_r($merged);

        $input = array_map("unserialize", array_unique(array_map("serialize", $merged)));
        echo "<pre>";
        print_r($input);*/
    }

    public function actionLazadaToken(){
        $channel = Channels::find()->where(['marketplace'=>'lazada','name'=>'lazada','is_active'=>1])->one();
        LazadaUtil::create_fresh_token($channel);
    }
    public function actionLazadaFblStock()
    {
        $channel = Channels::find()->where(['marketplace'=>'lazada','prefix'=>'HRB-LZD','is_active'=>1])->one();
        LazadaUtil::GetFBLStock($channel,'473120647');
    }
    public function actionUpdatePrestaCat()
    {
        $channel = Channels::find()->where(['marketplace'=>'prestashop','name'=>'pedro','is_active'=>1])->one();
        PrestashopUtil::re_manage_sale_category($channel);
    }
    public function actionUpdatePrestaCat2()
    {
        $channel = Channels::find()->where(['marketplace'=>'prestashop','name'=>'pedro','is_active'=>1])->one();
        PrestashopUtil::auto_assign_category($channel,'add',[15,102,104],172);
    }
    public function actionDuplicateSku()
    {
        $product_exists = Products::findone(['sku'=>'BV0002-010_BLACK OR GREY_S']);
        self::debug($product_exists);
    }
    public function actionDelShopeeItem()
    {
        $channel = Channels::find()->where(['marketplace'=>'shopee','name'=>'shopee','is_active'=>1])->one();
            $data = [
                'discount_id'=>1219258403,
                'item_id' => 2015571051
            ];
         $response=ShopeeUtil::delete_discount_item($channel,$data);
         print_r($response);

    }
    public function actionPrestaCombination()
    {
      /*  header("Content-Type: image/jpeg");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        $url = 'https://speedonline.pk/pedroshoes/api/images/products/323/2006';


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, 'X8NLCGGCC5CZETGEQBCH21ZJW4L8PS1Q' . ':');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11');
        $res = curl_exec($ch);
        $rescode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch) ;
      //  self::debug($res);
        file_put_contents("shipping-labels/khang.png", $res);
        try{unlink('shipping-labels/khangs.png');} catch (\Exception $exception){}

        echo $res; die();*/
        //////////////////

       // $channel = Channels::find()->where(['marketplace'=>'prestashop','name'=>'pedro','is_active'=>1])->one();
        $channel = Channels::find()->where(['marketplace'=>'prestashop','name'=>'afsgear','is_active'=>1])->one();
        $res=PrestashopUtil::CombinationDetail( $channel->id, 3897 );
        $res=json_decode($res);
        self::debug($res);
        echo $res->combination->associations->images->image[0]->id;
       // self::debug(json_decode($res));
        //print_r($res); die();
    }
    public function actionPrestaCustomer()
    {
        $channel = Channels::find()->where(['marketplace'=>'prestashop','name'=>'pedro','is_active'=>1])->one();
        $res=PrestashopUtil::get_customer_detail( $channel, 28 );
        $res=json_decode($res);
      //  echo $res->combination->associations->images->image[0]->id;

        // self::debug(json_decode($res));
        //print_r($res); die();
    }
    public function actionEbayCat()
    {
        $cat_id=0;
        $response=  CatalogUtil::saveCategories(array('cat_name'=>'gloves','parent_cat_id'=>$cat_id,'is_active'=>'1','channel'=>array('id'=>17)));
        self::debug($response);
    }
    public function actionCheckImage()
    {
       /* if(@file_get_contents('http://54.145.139.107/product_image/190604048987.jpg', 0, NULL, 0, 1))
            echo "yo";
        else
            echo "NO";

        die();*/
        //unset($_COOKIE['item']);
      //  setcookie('item', null, -1, '/');
      //  setcookie("item", "", time()-3600);

        /*if(isset($_COOKIE['item']))
            self::debug($_COOKIE['item']);
        else
            die('not found');*/
        echo Date('H:i:s');
        echo "<br/>";
        $errors=[];
        ?>
        <script type="text/javascript">
            //Example error handling
            var errors=[];
            var handelImageNotFound = function (img_id) {
                errors.push(img_id);
                console.log(errors);
               // alert(img_id);
              //  document.cookie='item['+img_id+']='+img_id;
               // if(img_id)

                    }
        </script>

        <?php
        for($i=1;$i<300;$i++){ ?>


            <img width="1px" height="1px" src="http://54.145.139.107/product_image/190604048963.jpg" id="img_" onerror="handelImageNotFound('<?= $i;?>')" />
<?php
            $external_link = 'http://54.145.139.107/product_image/190604048963s.jpg';
           // echo $external_link;
            //echo "<br/>";
           // if (@getimagesize($external_link)) {
            /*if (@exif_imagetype($external_link)) {
            //if (@file_get_contents($external_link)) {
                echo  "yes";
                echo "<br/>";
            } else {
                echo  "NO";
                echo "<br/>";
            }*/
        }
      // self::debug($_COOKIE);
       // $errors= "<script>document.write(errors)</script>";
        echo "<pre>";
        print_r($errors);
        echo "<br>";
        echo date('H:i:s');
    }
    public function actionSetCookie()
    {
        $info=[];
        for($i=0;$i<1000;$i++){
            $info[$i]=[1=>'090000'.$i.'.jpg',2=>'0900002'.$i.'.jpg',3=>'0900003'.$i.'.jpg',4=>'0900004'.$i.'.jpg'];
        }
      // setcookie('bilal', json_encode($info), time()+3600);
        //unset($_COOKIE['bilal']);
        $data = unserialize($_COOKIE['bilal'], ["allowed_classes" => false]);
        self::debug($data);
    }

    public function actionReadImages()
    {
        echo Date('H:i:s');
        echo "<br/>";
        for($i=1;$i<8;$i++) {
        $ch = curl_init("http://54.145.139.107/product_image/190604048963s.jpg");

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $retcode >= 400 -> not found, $retcode = 200, found.
        curl_close($ch);
        echo($retcode);
        }
        echo "<br>";
        echo date('H:i:s');
        die();
        echo Date('H:i:s');
        echo "<br/>";
        for($i=1;$i<8;$i++) {
            $url = 'http://54.145.139.107/product_image/190604048963.jpg';
            $header_response = get_headers($url, 1);
           // self::debug($header_response);
            if (strpos($header_response[0], "404") !== false) {
                echo "yes";
                echo "<br/>";
            } else {
                echo "NO";
                echo "<br/>";
            }
        }
        echo "<br>";
        echo date('H:i:s');
    }

    public function actionBackmarketPrice()
    {
        $channel = Channels::find()->where(['marketplace'=>'backmarket','name'=>'back market','is_active'=>1])->one();
        $item=['price'=>19.00,'sku'=>'41654'];
      //  $item=['stock'=>13.00,'sku'=>'41654'];
        //$item=['price'=>11.00,'sku'=>'17469'];
        $res=BackmarketUtil::updateChannelPrice($channel,$item);
      //  $res=BackmarketUtil::updateChannelStock($channel,$item);
        self::debug($res);
    }
    public function actionBilal()
    {
        echo array_sum(unpack("C*", "Hello world"));
       // $string = "This is the Euro symbol '€'.";
       // echo iconv("UTF-8", "ASCII", $string), PHP_EOL;
    }

    public function actionPattern()
    {
        $sku="1351604-662_RED_MD";
       $res= ProductsUtil::get_sku_from_pattern_nike($sku);
      // $res= ProductsUtil::get_size_from_sku($sku);
       print_r($res);


    }
    public function actionGetCustomer()
    {
        OrderUtil::GetCustomerDetailByPk('7719');
    }

    public function actionUtcTime()
    {
        $utc=HelpUtil::get_utc_time("2020-12-01 23:59:59");
        echo $utc;
    }
    public function actionAmazonSp(){
        $channel="ok";
        AmazonspUtil::fetchOrdersApi2($channel);
    }

    public function actionAmazonCls()
    {
        //AmazonspUtil::sts_client();
        //AmazonspUtil::test_order();
        AmazontestUtil::test_order();
       // AmazontestUtil::fetchOrdersApi();
       // AmazonspUtil::order_detail();
       // AmazonspUtil::fetchOrdersApi();
    }
    public function actionCombination()
    {
       // die('come');
        $detail=PrestashopUtil::CombinationDetail('24','5000');
        echo "<pre>";
        print_r($detail);
    }
    public function actionEncrypt()
    {
       // die('come');
        //$encryptionKey = Settings::GetDataEncryptionKey();
        $name = new \yii\db\Expression('AES_ENCRYPT("bilal","1bb8d713f296847dca58a883f8820d67")');
        echo $name;
    }

    public function actionMoveOrders()
    {
        TestUtil::move_orders();
    }
    public function actionReplicateOrders()
    {
        TestUtil::replicate_orders();
    }

    public function actionTestLoop()
    {
        sleep(30);
        for ($i=0;$i<200000000;$i++){
            echo "";
        }
    }
    public function actionTestPattern()
    {
        $ok=GlobalMobileUtil::get_name_pattern('L/G- 36-38"','Black','IZOD Men\'s Sleep Pant Soft Touch Fleece Size L/G Black');
        print_r($ok);
    }

    public function actionCatMapping()
    {
        $cat=GlobalMobilesCatMapping::find()->asArray()->all(); /// mapp category and update;
        $product=['Parent Category'=>'Batteries','Sub-Category'=>''];
        $res=GlobalMobileUtil::map_global_mobile_cat($cat,$product);
        self::debug($res);
    }
    public function actionMagentoProduct(){
        $channel=Channels::findOne(['id'=>22]);
        //self::debug($channel);
        MagentoUtil::ChannelProducts($channel,'CU4384-902');
    }
    public function actionImageGlobal()
    {
        GlobalMobileUtil::refill_closest_variation_images(11);
    }

    public function actionStockDepletion()
    {
        $new=new Orders();
        $data=$new->getCustomersAddresses();
        self::debug($data);

        $data=[
            'warehouse_id'=>9,
            'channel_id' =>24,
            'sku' => 'ADIACC080-BW',
            'qty' => '2',
            'item_status' => 'pending',
            'order_item_id' => '100',
            'order_id_pk' => '153',
            'new' =>1
        ];
        $res=OrderUtil::DepleteWarehouseStock($data);
        self::debug($res);
    }
    public function actionOrderNotes()
    {
        $channel=Channels::findOne(['id'=>23]);
        $order=PrestashopUtil::order_notes($channel,373);
        self::debug($order);
    }

    public function actionUnitTest()
    {
        $x= "'Hello World!'";
        echo str_replace(["'",'!'],"",$x);
    }
    public function  actionQuick(){
       // $warehouse=Warehouses::findone(['id','6']);
        $warehouse=Warehouses::findone(['id','68']);
        //self::debug($warehouse);
        QuickbookUtil::get_receipt($warehouse);
    }

    public function actionTest()
    {
        $time_now = date('Y-m-d h:i:s');
        echo "Time Now : " . $time_now;
        flush();
        sleep(5);
        $time_then = date('Y-m-d h:i:s');
        echo "<br>Time Then : " . $time_then;
        die();
        for($i=0;$i<=15;$i++){
            sleep(2);
            echo date('Y-m-d H:i:s');
            echo "<br/>";
            $log=['type'=>'order-fetch-api','entity_type'=>'channel','entity_id'=>date('Y-m-d H:i:s'),'request'=>'amazon order items  fetch request','additional_info'=>'Order items api for amazonorderid => ','response'=>'khan','log_type'=>'error'];
            LogUtil::add_log($log);

        }
        die();
        $day_count=(isset($_GET['day_count'])  && is_numeric($_GET['day_count']) && $_GET['day_count'] <=30 && $_GET['day_count'] > 0) ? '-'.($_GET['day_count']):'-1';
        $created_after = gmdate('Y-m-d\TH:i:s\Z', strtotime($day_count." days"));
        echo $created_after;
    }

    public function actionTestUpload()
    {
        $string=file_get_contents('https://envio.tcscourier.com/BookingReportPDF/GenerateLabels?consingmentNumber=99210152937');
       // file_put_contents('images/feed.txt',"Hello World. Testing!");
       // self::debug($string);
        self::debug($string);
    }

    public function actionCourier()
    {
        $couriers=Couriers::findOne(['id'=>6]);
        LCSUtil::generate_load_sheet($couriers,'as');
    }

    public function actionAmazonProduct()
    {
        $channel=Channels::findOne(['id'=>25]);
        AmazonSellerPartnerUtil::getFbaInventory($channel);
    }

    public function actionMapProductCategories(){

        $inserted_count=0;
        $products = Products::find()->Where(['not', ['sub_category' => null]])->andWhere(['not', ['sub_category' => 0]])->asArray()->all();
        foreach ($products as $key => $product) {
          $exists = ProductCategories::findone(['product_id' => $product['id'], 'cat_id' =>$product['sub_category']]);

            if(!$exists) {
                $insert=new ProductCategories();
                $insert->product_id=$product['id'];
                $insert->cat_id=$product['sub_category'];
                if ($insert->save()){
                    $inserted_count++;
                }
            }
        }
        return $this->asJson(['status'=>'success',
                'msg'=>'inserted',
                'total_inserted'=>$inserted_count,
            ]
        );
    }

    public function actionPrestaCoupon()
    {
        $channel=Channels::findOne(['id'=>23]);
        $code=PrestashopUtil::get_order_cart_rule($channel,568);
        self::debug($code);
    }

    public function actionDarazDocument()
    {
        $channel=Channels::findOne(['id'=>32]);
        $code=DarazUtil::getOrderDocumentId($channel);
        self::debug($code);
    }

    public function actionTpW()
    {
        $sku=EzcomToWarehouseProductSync::findOne(['sku'=>'AQ0641-492','warehouse_id'=>'42']);
        $sku->status='failed';
        $sku->save();
        self::debug($sku);
    }

    public function actionFailedToWarehouse()
    {
        $products=EzcomToWarehouseSync::find()->where(['type'=>'product','comment'=>'product created to warehouse'])->orWhere(['comment'=>'product synced to warehouse'])->asArray()->all();
        if($products)
        {
            foreach ($products as $product)
            {
                $exists=EzcomToWarehouseProductSync::findone(['sku'=>$product['ezcom_entity_id'],'warehouse_id'=>$product['warehouse_id']]);
                if(!$exists)
                {
                    $new= new EzcomToWarehouseProductSync();
                    $new->warehouse_id=$product['warehouse_id'];
                    $new->sku=$product['ezcom_entity_id'];
                    $new->comment=$product['comment'];
                    $new->created_at=$product['created_at'];
                    $new->synced_at=$product['created_at'];
                    $new->status='synced';
                    $new->save();

                }
            }
        }

    }

    public function actionErrorWarehouseSync()
    {
        $sql="SELECT * FROM general_log where `log_type`='error' AND `type`='ezcom-to-warehouse-product-sync'
AND request NOT IN (SELECT sku FROM ezcom_to_warehouse_product_sync)
GROUP BY request";
       $products= Yii::$app->db->createCommand($sql)->queryAll();
      // self::debug($products);
        foreach ($products as $product)
        {

                $new= new EzcomToWarehouseProductSync();
                $new->warehouse_id=$product['entity_id'];
                $new->sku=$product['request'];
                $new->comment='failed sync';
                $new->response=$product['response'];
                $new->created_at=$product['created_at'];
                $new->synced_at=$product['created_at'];
                $new->status='failed';
                $new->save();


        }
    }

    public function actionUnsetSaleCat()
    {
        $channel=Channels::findOne(['id'=>22]);
        $response=MagentoUtil::unsetSaleCategory($channel);
        self::debug($response);
    }

    public function actionQuickbookAccounts()
    {
        $warehouse=Warehouses::findOne(['id'=>68]);
        QuickbookUtil::product_asscoiated_accounts($warehouse);
    }


}