<?php
namespace backend\util;
use common\models\Channels;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrderShipment;
use common\models\OrderShipmentHistory;
use common\models\Products;
use Dompdf\Options;
use Yii;
use Dompdf\Dompdf;
class CourierUtil
{

    /*
    * map marketplace courier statuses with our own , pending, canceled, shipped,completed
    */
    public static function mapStatus($status)
    {
        //$shipped= ['processing'];
        $completed = ['delivered','completed','complete','deliver','Delivered, Front Door/Porch'];
        $canceled = ['cancelled', 'canceled','cancel'];
        $refunded=['refunded','returned','refund_paid'];
       // $canceled = ['cancelled', 'canceled','refunded','cancel','invalid','deceased', 'refused', 'refund_paid','returned','refunded', 'failed', 'reversed', 'delivery failed', 'canceled by customer'];

        if(in_array(strtolower($status),$completed))
            return "completed";
        if(strpos(strtolower($status), 'delivered') !== false)
            return "completed";
        if(in_array(strtolower($status),$canceled))
            return "canceled";
        if(in_array(strtolower($status),$canceled))
            return "refunded";
        else
            return "shipped";
    }

    public static function updateMarketplaceTrackingAndShippingStatus($channel,$order,$items,$courier,$api_response,$shipping_status=null)
    {
        if($channel->marketplace=='prestashop')
             PrestashopUtil::updateOrderTrackingAndShippingStatus($channel,$order,$courier,$api_response,$shipping_status);

        if($channel->marketplace=='ebay')
                EbayUtil::MarkOrderShipAndUpdateShipmentDetail($items,$channel->id,$api_response['tracking_number'],$courier->name);
    }

    /**
     * add courier record about order shipment
     */
    public static function addOrderShipmentDetail($shipment_detail)
    {
        foreach($shipment_detail as $response)
        {
            $order_shipment=OrderShipment::findone(['order_item_id'=>$response['order_item_id']]);
            if(!$order_shipment)
                $order_shipment=new OrderShipment();

            /*if(isset($response['courier_type']) && $response['courier_type']=='internal') // mean internal courier is used then complete all its statuses
            {
                $order_shipment->is_tracking_updated=1;  // because no tracking number in self courier
            }*/
            $order_shipment->order_item_id=$response['order_item_id'];
            $order_shipment->amount_inc_taxes=$response['amount_inc_taxes'];
            $order_shipment->amount_exc_taxes=$response['amount_exc_taxes'];
            $order_shipment->extra_charges=isset($response['extra_charges']) ? $response['extra_charges']:0.00 ;
            $order_shipment->system_shipping_status=$response['system_shipping_status'];
            $order_shipment->courier_shipping_status=$response['courier_shipping_status'];
            $order_shipment->shipping_date=isset($response['shipping_date']) ?  $response['shipping_date'] :NULL;
            $order_shipment->estimated_delivery_date=isset($response['estimated_delivery_date']) ? $response['estimated_delivery_date']:NULL;
            $order_shipment->dimensions=isset($response['dimensions']) ? json_encode($response['dimensions']):NULL; // ['length'=>,'width'=>'','height'=>'','weight'=>]
            $order_shipment->additional_info=isset($response['additional_info']) ? json_encode($response['additional_info']):NULL;
            $order_shipment->full_response=isset($response['full_response']) ? json_encode($response['full_response']):NULL;
            $order_shipment->packing_slip=isset($response['packing_slip']) ? $response['packing_slip']:NULL;
            $order_shipment->save();
        }
        return ;
    }

    public static function GetMarketPlaceCourier($OrderId){

        $order = Orders::find()->where(['id'=>$OrderId])->one();
        $channel = Channels::find()->where(['id'=>$order->channel_id])->one();

        if ($channel->marketplace=='lazada'){
            $OrderDetail = LazadaUtil::GetOrderDetail($order->order_number,$channel->id);

            if ( $OrderDetail->data->warehouse_code == 'dropshipping' ):
                if ( in_array('ready_to_ship',$OrderDetail->data->statuses) ){
                    $result = [
                        [
                            'name' => 'Already Ready To Ship',
                            'icon' => '/logos/lazada.png',
                            'type' => null,
                            'description' => '<span style="font-weight: 700;">Note:</span> This order is already marked as read to ship.<br />It is showing pending because Ezcommerce assume ready to ship as pending.
                                              Please check seller center lazada for more details'
                        ]
                    ];
                    return $result;
                }else{
                    $sql = "SELECT * FROM couriers WHERE type LIKE '".$channel->marketplace."%'";
                    return  yii::$app->db->createCommand($sql)->queryAll();
                }

            else:
                $result = [
                    [
                        'name' => 'Fulfilled By Lazada (FBL)',
                        'icon' => '/logos/lazada.png',
                        'type' => null,
                        'description' => '<span style="font-weight: 700;">Note:</span> This oder is not marked as dropshipping, means you cannot fulfil it but lazada will do it for you.<br />Please check seller center lazada for more details'
                    ]
                ];
            return $result;
            endif;

        }else{
            $sql = "SELECT * FROM couriers WHERE type LIKE '".$channel->marketplace."%'";
            return  yii::$app->db->createCommand($sql)->queryAll();
        }
    }

    /*
     * get all couriers attacjed to warehouse
     */

    public static function getWarehouseCouriers($warehouse_ids)
    {
        $sql="SELECT `c`.`id`,`c`.`name`,`c`.`type`,`c`.`icon`,`c`.`description`
                FROM
                    `couriers` c 
                 INNER JOIN
                    `warehouse_couriers` wc
                 ON 
                   `c`.`id`=`wc`.`courier_id`
                WHERE 
                    `wc`.`warehouse_id` IN($warehouse_ids) AND `wc`.`is_active`='1' AND c.type NOT IN ('shopee-fbs','lazada-fbl')
                GROUP BY
                      `c`.`id`";

        return  yii::$app->db->createCommand($sql)->queryAll();
    }

    /*
     * get record from db to track status against tracking number
     */

    public static function getTrackingList()
    {

       $sql="SELECT GROUP_CONCAT(`os`.`id`) as os_pk_ids,GROUP_CONCAT(`os`.`order_item_id`) as order_item_ids,GROUP_CONCAT(`oi`.`item_status`) as system_shipping_status,GROUP_CONCAT(`os`.`courier_shipping_status`) as courier_shipping_status,
              `oi`.`tracking_number`,`oi`.`courier_id`,`oi`.`shipping_label`,o.order_number, o.order_id as marketplace_db_order_id,o.id as order_pk_id,o.channel_id
             FROM 
                `order_shipment` os 
             INNER JOIN
                `order_items` oi
              ON 
                `os`.`order_item_id`=`oi`.`id`
             INNER JOIN
                  `orders` o 
              ON o.id=oi.order_id    
              WHERE
                `os`.`is_completed`=0 AND `os`.`is_tracking_updated`=1 AND `os`.`updated_at` > DATE_SUB(curdate(), INTERVAL 1 MONTH) 
              GROUP BY `oi`.`tracking_number` , `oi`.`courier_id` ";

        return yii::$app->db->createCommand($sql)->queryAll();
    }

    /*
     * check shipping status against tracking number
     * if status changed update online marketplace
     * update local db status
     */
  /*  public static function trackShipping($list)
    {
        //print_r($list); die();
        foreach($list as $item)
        {

            if(strtolower($item['courier_type'])=='ups' && $item['tracking_number'])  // for ups
                $response=UpsUtil::trackShipping((object)$item,'1Z12345E6605272234');
            if(strtolower($item['courier_type'])=='fedex' && $item['tracking_number'])  // for fedex
                //fedex tracking

            if(isset($response['status']) && $response['status']=='success' && ($response['courier_status']!=$item['item_status']))
            {
                $channel=Channels::findOne(['id',$item['channel_id']]);
                self::update_marketplace_shipping_status($channel,$item,$response['courier_status']); // update marketpalce status
                self::update_local_shipping_status($item,$response['courier_status']); // update local db item status
            }
        }
    }*/

    public static function update_marketplace_shipping_status($channel,$item,$courier_status)
    {

        if($channel->marketplace=='prestashop')
        {
            PrestashopUtil::updateMarketplaceShippingStatus($channel,$courier_status,$item['channel_order_id']);
        }
        if($channel->marketplace=='ebay')
        {
            //continue ebay integration
            return;
        }
    }

    /**
     * @param null $list
     * maintain shipping history of statuses of courier against tracking number
     * example
     * Array
            (
            [os_pk_ids] => 1,2
            [order_item_ids] => 1274,1275
            [system_shipping_status] => shipped,pending
            [courier_shipping_status] => shipped,delivered
            [tracking_number] => 9400111969000940000011
            [courier_id] => 21
            [shipping_label] => 9400111969000940000011.pdf
            [order_number] => RTANQAJKA
            [marketplace_db_order_id] => 248
            [channel_id] => 16
            )
     */
    private static function update_order_shipping_history($item=null)
    {
        if($item)
        {
            $order_item_ids=explode(',',$item['order_item_ids']);
            $system_shipping_statuses=explode(',',$item['system_shipping_status']);
            $courier_shipping_statuses=explode(',',$item['courier_shipping_status']);
            for($i=0;$i<count($order_item_ids);$i++)
            {
                $insert=new OrderShipmentHistory();
                $insert->order_item_id=$order_item_ids[$i];
                $insert->system_status=$system_shipping_statuses[$i];
                $insert->courier_status=$courier_shipping_statuses[$i];
                $insert->tracking_number=$item['tracking_number'];
                $insert->label=$item['shipping_label'];
                $insert->courier_id=$item['courier_id'];
                $insert->save(false);
            }

        }
        return;
    }

    /**
     * @param $list
     * @param null $courier_real_status
     * @return bool|string
     * update status of order shipment
     */
    public static function update_local_db_shipping_status($list,$courier_real_status=null)
    {

        if($list['os_pk_ids'] && $courier_real_status):

            $system_status=self::mapStatus($courier_real_status);
            $set="";
            if(in_array($system_status,['completed','canceled','refunded']))
                $set="`os`.`is_completed`=1,"; // so no need to check tracking against this record again

            $sql="UPDATE order_shipment os
                    INNER JOIN
                      order_items oi
                    ON 
                      os.order_item_id = oi.id
                    SET
                          os.courier_shipping_status = '".$courier_real_status."',
                          os.system_shipping_status = '".$system_status."',
                           $set
                          oi.item_status ='".$system_status."'
                    WHERE os.id IN(".$list['os_pk_ids'].")";
           // echo $sql;die();
            $updated=yii::$app->db->createCommand($sql)->execute();
            if($updated)
            {
                self::update_order_shipping_history($list); // update history
                return $system_status;
            }

            endif;
        return false;
    }

    public static function unsetFedexLabels($Packges, $FedExResponse){
        if ( count($Packges) == 1 ){
            if ( isset($FedExResponse->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image) )
                unset($FedExResponse->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);
        }else{
            if ( isset($FedExResponse->CompletedShipmentDetail->CompletedPackageDetails) ){
                foreach ( $FedExResponse->CompletedShipmentDetail->CompletedPackageDetails as $key=>$shipDetailFedex ){
                    if (isset($FedExResponse->CompletedShipmentDetail->CompletedPackageDetails[$key]->Label->Parts->Image)){
                        unset($FedExResponse->CompletedShipmentDetail->CompletedPackageDetails[$key]->Label->Parts->Image);
                    }
                }
            }
        }
        return $FedExResponse;
    }

    public static function make_shipping_cancellation_history($order_items,$order_shipment_items)
    {
        $items=[];
        if($order_items)
        {
            foreach ($order_items as $item)
                $items[$item['id']]=$item;
        }

        foreach($order_shipment_items as $ship_item)
        {
            $additional_info=['current_requested_status'=>'cancelled','amount_inc_taxes'=>$ship_item['amount_inc_taxes'],'amount_inc_taxes'=>$ship_item['amount_exc_taxes'],'extra_charges'=>$ship_item['extra_charges'],'other_info'=>$ship_item['additional_info']];
            $history=New OrderShipmentHistory();
            $history->order_item_id=$ship_item['order_item_id'];
            $history->system_status=isset($items[$ship_item['order_item_id']]['item_status']) ? $items[$ship_item['order_item_id']]['item_status']:NULL ;
            $history->courier_status= 'canceled';
            $history->tracking_number= isset($items[$ship_item['order_item_id']]['tracking_number']) ? $items[$ship_item['order_item_id']]['tracking_number']:NULL;
            $history->label= isset($items[$ship_item['order_item_id']]['shipping_label']) ? $items[$ship_item['order_item_id']]['shipping_label']:NULL;
            $history->courier_id= isset($items[$ship_item['order_item_id']]['courier_id']) ? $items[$ship_item['order_item_id']]['courier_id']:NULL;
            $history->additional_info= json_encode($additional_info);
            $history->save(false);

        }
        return;
    }

    /****
     * @param array $order_ids
     *
     */
    public static function get_tracking_numbers_of_order(array $order_ids)
    {
        $tracking=[];
        if($order_ids){
            $tracking=OrderItems::find()->select('tracking_number')->distinct()->where(['in', 'order_id', $order_ids])->asArray()->all();
        }
        return $tracking;
    }


    public static function arrange_data_for_invoice()
    {

    }
    /**
     * generate label for internal courier
     */
    public static function generate_order_label($params)
    {
        error_reporting(E_ERROR | E_PARSE); // it will parse error
        $pdf_name=$params['tracking_number'].".pdf";
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $dompdf = new Dompdf($options);
        $html=self::internal_label_template($params);
        $dompdf->loadHtml($html);
        // (Optional) Setup the paper size and orientation
        //$dompdf->setPaper('A4', 'landscape');
        $dompdf->setPaper('letter', 'portrait');
        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        //$dompdf->stream();
        $pdf = $dompdf->output();      // gets the PDF as a string

        $result = file_put_contents("shipping-labels/".$pdf_name, $pdf);
        if($result)
            return $pdf_name;
        else
            return false;
    }
    /**
     * generate invoice/packing slip pdf
     */
    public static function generate_order_invoice($params)
    {
        error_reporting(E_ERROR | E_PARSE); // it will parse error
        $pdf_name=$params['order']->order_number."_".date('dHI')."packing.pdf";
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $dompdf = new Dompdf($options);
        $html=self::order_invoice_template_new($params);
        //echo $html; die();
        $dompdf->loadHtml($html);
        // (Optional) Setup the paper size and orientation
        //$dompdf->setPaper('A4', 'landscape');
        $dompdf->setPaper('letter', 'portrait');
         // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        //$dompdf->stream();
        $pdf = $dompdf->output();      // gets the PDF as a string

        $result = file_put_contents("shipping-labels/".$pdf_name, $pdf);
        if($result)
            return $pdf_name;
        else
            return false;
    }


    /***
     * template
     */
    public static function order_invoice_template_new($params)
    {

        $shipping_date=isset($params['shipping_date']) ? $params['shipping_date']:"x";
        $shipping_fee=isset($params['extra_shipping_charges']) ? $params['extra_shipping_charges']:$params['order']->order_shipping_fee;
        $grand_total=isset($params['grand_total']) ?  $params['grand_total']:"";
        $shipper_full_address=isset($params['shipper']['full_address']) ? $params['shipper']['full_address']:$params['shipper']['address'];
        $html='<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    
    <style>
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        font-size: 13px;
        line-height: 24px;
        font-family: \'Helvetica Neue\', \'Helvetica\', Helvetica, Arial, sans-serif;
        color: black;
    }
    
    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
    }
    
    .invoice-box table td {
        padding: 5px;
        vertical-align: top;
        font-family: none;
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
        <tr class="top" style="">
                <td colspan="5">
                    <table>
                        <tr style="text-align:center;">
                            <td>
                            <h3>Date:<br/> '.date('Y-m-d',$params['order']->created_at).'</h3>
                            </td>
                            <td>
                                <b>Payment Advice</b>
                            </td>
                             <td>
                               <h3> Ship Date: <br/>'.$shipping_date.'</h3>
                             </td>
                           
                        </tr>
                    </table>
                </td>
            </tr>
        <tr style="text-align:center">
            <td colspan="5">
                
                <h1 style="font-size: 55px;font-family: Arial, Helvetica, sans-serif">Order #  
                                   <span > '.$params['order']->order_number.'</span>
                                </h1>
            </td>
        </tr>
           
            
            
            <!----------------------->
            
            <tr class="" >
            
                <td colspan="5">
                    <table>
                    <tr class="heading">
                    <td style="background:#f6f6f6">
                        From :
                    </td>
                    <td style="background:#f6f6f6">
                        To :
                    </td>
                </tr>
                    <tr>
                        <td style="border:2px solid gray !important;text-align:center;font-size:18px">
                            Shipper Name:'.$params['shipper']['name'].' <br>
                            Shipper Adress:'.$shipper_full_address.'<br>
                        </td>
                        
                        <td style="border:2px solid gray !important;text-align:center;font-size:18px">
                            Receiver Name: '.$params['customer']['name'].' <br>
                            Receiver Adress:'.$params['customer']['address'].'<br>
                            Receiver Phone:'.$params['customer']['phone'].'
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
            <!-------------------------->
             <tr class="heading" style="background:#f6f6f6">
                <td>
                    Image
                </td>
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
            </tr>';
        $sub_total=0;
        foreach($params['order_items'] as $item) {
            $product=Products::findOne(['sku'=>$item['item_sku']]);
            $item_name=$product ? $product->name:"";
           // $item_image=$item['item_image']  ? $item['item_image']:Yii::$app->request->baseUrl.'/backend/web/images/no_image-copy.jpg"';
            $item_image="X";
            if(isset($product->image) && $product->image)
                $item_image='<img src="'.$product->image.'" width="50px" style="object-fit: contain"/>';

            $sub_total += ($item['paid_price']  * $item['quantity']);
            $html .= '<tr class="item">
                <td>
                    '.$item_image.'
                </td>
                <td>
                    <b>'.$item['item_sku'].'</b>
                </td>
                <td>
                    '.$item_name.'
                </td>
                <td>
                    '.$item['paid_price'].'
                </td>
                <td>
                    '.$item['quantity'].'
                </td>
            </tr>';
        }
        $grand_total=$grand_total ? $grand_total:$sub_total;
        //$shipping_charges=isset($params['shipping_charges']) ?  $params['shipping_charges']:$params['order']->order_shipping_fee;
        $html .= '<tr class="total">
                <td ></td>
                <td colspan="3"> Sub Total:</td>
                
                <td>
                    '.$sub_total.'
                </td>
            </tr>
            <tr class="total">
                <td ></td>
                <td colspan="3">Tax:</td>
                
                <td>
                    '. array_sum(array_column($params['order_items'],'item_tax')).'
                </td>
            </tr>
            <tr class="total">
                <td></td>
                <td colspan="3">Shipping:</td>
                
                <td>
                    '.$shipping_fee.'
                </td>
            </tr>
            <tr class="total" style="text-align:center;">
              
                <td colspan="5">
                    <h1>Grand Total:'.$grand_total.'</h1>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';
        return $html;
    }
    /***
     * template
     */
    private static function order_invoice_template($params)
    {
        $shipping_date=isset($params['shipping_date']) ? $params['shipping_date']:"x";
        $html='<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    
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
                                '.$params['shipper']['address'].','.$params['shipper']['city'].' ,
                                   , '.$params['shipper']['state'].' <br> '.$params['shipper']['zip'].' , '.$params['shipper']['country'].'
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
                                Ship To:'.$params['customer']['name'].'<br>'.$params['customer']['address'].','.$params['customer']['city'].'<br> 
                                         , '.$params['customer']['state'].' , '.$params['customer']['zip'].' , '.$params['customer']['country'].'
       
                            </td>
                        </tr>
                    </table>
                </td>
                 <td colspan="2">
                    <table>
                        <tr>
                            
                             <td colspan="2">
                                Order #: '.$params['order']->order_number.'<br>
                                Date: '.date('Y-m-d',$params['order']->created_at).'<br>
                                Ship Date: '.$shipping_date.'
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
            </tr>';
        $sub_total=0;
        foreach($params['order_items'] as $item) {
            $item_name=Products::findOne(['sku'=>$item['item_sku']]);
            $item_name=$item_name ? $item_name->name:"";
            $sub_total += ($item['paid_price']  * $item['quantity']);
            $html .= '<tr class="item">
                <td>
                    '.$item['item_sku'].'
                </td>
                <td>
                    '.$item_name.'
                </td>
                <td>
                    '.$item['paid_price'].'
                </td>
                <td>
                    '.$item['quantity'].'
                </td>
            </tr>';
        }
        //$shipping_charges=isset($params['shipping_charges']) ?  $params['shipping_charges']:$params['order']->order_shipping_fee;
        $html .= '<tr class="total">
                <td ></td>
                <td colspan="2"> Sub Total:</td>
                
                <td>
                    '.$sub_total.'
                </td>
            </tr>
            <tr class="total">
                <td ></td>
                <td colspan="2">Tax:</td>
                
                <td>
                    '. array_sum(array_column($params['order_items'],'item_tax')).'
                </td>
            </tr>
            <tr class="total">
                <td></td>
                <td colspan="2">Shipping:</td>
                
                <td>
                    '.$params['order']->order_shipping_fee.'
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';
        return $html;
    }

    /**
     * generate template of label for internal type warehouse
     */
    public static function internal_label_template($params)
    {
        $template='<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    
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
    
    .invoice-box table tr td:nth-child(2) {
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
        border-top: 2px solid #eee;
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
                            <td class="title">
                            <div style="width:200px">
                                <img src="https://speedsports.pk/pub/media/logo/default/Speed-Pvt.-Limited-1.png" style="width:100%; max-width:250px;">
                            </div>
                            </td>
                            
                            <td>
                                Order #: '.$params['order_number'].'<br>
                                Created: '.Date('Y-m-d').'<br>
                                Tracking: '.$params['tracking_number'].'
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
         
            <tr class="information" >
                <td colspan="2">
                    <table>
                        <tr>
                            <td style="border:2px solid gray !important;text-align:center">
                              <b>FROM:</b><br/>
                                Shipper Name:545802 / SPEED SPORTS NIKE ASICS ADIDAS <br>
                                Shipper Adress:'.$params['shipper']['address'].'<br>
                            </td>
                            
                            <td style="border:2px solid gray !important;text-align:center">
                                <b>TO:</b><br/>
                                Receiver Name: '.$params['customer']['name'].' <br>
                                Receiver Adress:'.$params['customer']['address'].'<br>
                                Receiver Phone:'.$params['customer']['phone'].'
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
          <!--  <tr class="heading">
                <td>
                    Weight
                </td>
                
                <td>
                   2000 grams
                </td>
            </tr>-->
            
            <tr class="details">
                <td>
                    Weight
                </td>
                
                <td>
                    '.$params['package']['weight']. ' grams
                </td>
            </tr>
            
            <tr class="details">
                <td>
                    Cash Collection Amount
                </td>
                
                <td>
                   RS. ' .$params['shipping_charges'].'/-
                </td>
            </tr>
            
          
            
            <!--<tr class="total">
                <td></td>
                
                <td>
                   Total: $385.00
                </td>
            </tr>-->
        </table>
    </div>
</body>
</html>';
        return $template;
    }

}

