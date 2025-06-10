<?php

namespace backend\util;


use backend\controllers\ApiController;
use common\models\CustomersAddress;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrdersCustomersAddress;
use common\models\Settings;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Yii;

class RecordUtil
{

    public static function _recurisveCall($salesFunc, $v, $params, $limit, $depth = 0, $sales)
    {
        if ($v->marketplace == 'Lazada') {
            if ($limit == 100) {
                $params['offset'] = $limit * $depth;
                $response = ApiController::$salesFunc($v, $params);
                if (isset($response['data'])) {
                    $saleDepth = $response['data']['orders'];
                    $limit = $response['data']['count'];
                    $sales[] = $saleDepth;
                    return self::_recurisveCall($salesFunc, $v, $params, $limit, ++$depth, $sales);
                }
            } else {
                return $sales;
            }
        }
        if ($v->marketplace == 'Shopee') {
            $params['offset'] = $limit * $depth;
            $response = ApiController::$salesFunc($v, $params);
            if (isset($response['orders'])) {
                $saleDepth = $response['orders'];
                $more = $response['more'];
                $sales = array_merge($sales, $saleDepth);
                if ($more == 1) {
                    return self::_recurisveCall($salesFunc, $v, $params, $limit, ++$depth, $sales);
                } else {
                    return $sales;
                }
            }
        }
    }


    public static function phoneNumberChange($phone, $type = 'E164')
    {
        $phone = str_replace(['+60', '60'], '', $phone);
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            //var_dump( $malaysianNumber );
            if ($type == 'INTERNATIONAL') {
                $phone = '+6' . $phone;
                $malaysianNumber = $phoneUtil->parse($phone);
                return $phoneUtil->format($malaysianNumber, PhoneNumberFormat::INTERNATIONAL);
            } else {
                $malaysianNumber = $phoneUtil->parse($phone);
                return $phoneUtil->format($malaysianNumber, PhoneNumberFormat::E164);
            }
        } catch (\libphonenumber\NumberParseException $e) {
            // var_dump( $e );
        }

        return $phone;
    }

    public static function recordSales($sales, $v)
    {
        $shippedStatus = ['to_confirm_receive', 'requested', 'judging', 'processing', 'delivered', 'reversed', 'self collect', 'complete', 'completed'];
        $cancelStatus = ['unpaid', 'cancelled', 'invalid', 'to_return', 'in_cancel', 'accepted', 'refund_paid', 'closed', 'seller_dispute','returned', 'reversed', 'missing orders', 'canceled', 'refunded', 'expired', 'failed', 'returned', 'reversed', 'delivery failed', 'canceled by customer'];
        $pendingStatus = ['ready_to_ship', 'retry_ship', 'exchange', 'pending', 'processed', 'processing',  'ready_to_ship', 'in transit'];
        $encryptionKey = Settings::GetDataEncryptionKey();
        $ordersList = [];
        if ($v->marketplace == 'Lazada') {
            // insert into DB
            if ($sales) {
                foreach ($sales as $sale) {
                    foreach ($sale as $s) {
                        if (isset($s['order_number'])) {
                            $orders = Orders::find()->where(['order_number' => $s['order_number'],'channel_id'=>$v->id])->one();
                            // refine status
                            $order_status = implode(',', $s['statuses']);
                            if (in_array(strtolower($order_status), $shippedStatus))
                                $order_status = 'shipped';
                            if (in_array(strtolower($order_status), $cancelStatus))
                                $order_status = 'canceled';
                            if (in_array(strtolower($order_status), $pendingStatus))
                                $order_status = 'pending';

                            if (!$orders) {
                                $orders = new Orders();
                                $orders->channel_id = $v->id;
                                $orders->order_id = (string)$s['order_number'];
                                $orders->order_number = (string)$s['order_number'];
                                $orders->payment_method = $s['payment_method'];
                                $orders->order_total = $s['price'];
                                $orders->order_created_at = $s['created_at'];
                                $orders->order_updated_at = $s['updated_at'];
                                $orders->order_status = $order_status;
                                $orders->order_count = $s['items_count'];
                                $orders->order_shipping_fee = $s['shipping_fee'];
                                $orders->customer_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($s['customer_first_name'],0,49).'","'.$encryptionKey.'")');//substr($s['customer_first_name'],0,49);
                                $orders->customer_lname = substr($s['customer_last_name'],0,49);//new \yii\db\Expression('AES_ENCRYPT("'.substr($s['customer_last_name'],0,49).'","'.$encryptionKey.'")');
                                $orders->full_response = new \yii\db\Expression('AES_ENCRYPT("'.str_replace("\"", "'", json_encode($s)).'","'.$encryptionKey.'")');//json_encode($s);
                                if(!$orders->save(false))
                                    print_r($orders->getErrors());

                                $ordersList[] = $orders->id;
                            }
                            else {
                                $orders->order_created_at = $s['created_at'];
                                $orders->order_updated_at = $s['updated_at'];
                                $orders->order_status = $order_status;
                                if(!$orders->save(false))
                                    print_r($orders->getErrors());
                                $ordersList[] = $orders->id;

                            }
                            $lastOrderId = $orders->id;

                            $ca = CustomersAddress::find()->where(['billing_number' => self::phoneNumberChange($s['address_billing']['phone'])])->one();
                            if (!$ca) {
                                $ca = new CustomersAddress();
                                $ca->shipping_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($s['address_shipping']['first_name'],0,49).'","'.$encryptionKey.'")');
                                $ca->shipping_lname = substr($s['address_shipping']['last_name'],0,49);//new \yii\db\Expression('AES_ENCRYPT("'.substr($s['address_shipping']['last_name'],0,49).'","'.$encryptionKey.'")');
                                $ca->shipping_number = new \yii\db\Expression('AES_ENCRYPT("'.self::phoneNumberChange($s['address_shipping']['phone']).'","'.$encryptionKey.'")'); //self::phoneNumberChange($s['address_shipping']['phone']);
                                $ca->shipping_address = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_shipping']['address1'].'","'.$encryptionKey.'")');//$s['address_shipping']['address1'];
                                $ca->shipping_state = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_shipping']['address3'].'","'.$encryptionKey.'")');//$s['address_shipping']['address3'];
                                $ca->shipping_city = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_shipping']['city'].'","'.$encryptionKey.'")');//$s['address_shipping']['city'];
                                $ca->shipping_country = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_shipping']['country'].'","'.$encryptionKey.'")');//$s['address_shipping']['country'];
                                $ca->shipping_post_code = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_shipping']['post_code'].'","'.$encryptionKey.'")');//$s['address_shipping']['post_code'];
                                $ca->billing_fname = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['first_name'].'","'.$encryptionKey.'")');//$s['address_billing']['first_name'];
                                $ca->billing_lname = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['last_name'].'","'.$encryptionKey.'")');//$s['address_billing']['last_name'];
                                $ca->billing_number = new \yii\db\Expression('AES_ENCRYPT("'.self::phoneNumberChange($s['address_billing']['phone']).'","'.$encryptionKey.'")');//self::phoneNumberChange($s['address_billing']['phone']);
                                $ca->billing_address = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['address1'].'","'.$encryptionKey.'")');//$s['address_billing']['address1'];
                                $ca->billing_state = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['address3'].'","'.$encryptionKey.'")');//$s['address_billing']['address3'];
                                $ca->billing_city = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['city'].'","'.$encryptionKey.'")');//$s['address_billing']['city'];
                                $ca->billing_country = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['country'].'","'.$encryptionKey.'")');//$s['address_billing']['country'];
                                $ca->billing_postal_code = new \yii\db\Expression('AES_ENCRYPT("'.$s['address_billing']['post_code'].'","'.$encryptionKey.'")');//$s['address_billing']['post_code'];
                                $ca->save(false);
                            }
                            $lastCAId = $ca->id;
                            $oca = OrdersCustomersAddress::find()->where(['orders_id' => $lastOrderId, 'customer_address_id' => $lastCAId])->one();
                            if (!$oca) {
                                $oca = new OrdersCustomersAddress();
                                $oca->orders_id = $lastOrderId;
                                $oca->customer_address_id = $lastCAId;
                                $oca->save();
                            }
                        }
                    }
                }
            }
        }

        if ($v->marketplace == 'Street') {
            // insert into DB
            if ($sales) {
                foreach ($sales as $s) {
                    if (isset($s['ordNo'])) {
                        $orders = Orders::find()->where(['order_id' => $s['ordNo'],'channel_id'=>$v->id])
                            ->one();
                        $order_status = $s['status'];
                        if (in_array(strtolower($order_status), $shippedStatus))
                            $order_status = 'shipped';
                        if (in_array(strtolower($order_status), $cancelStatus))
                            $order_status = 'canceled';
                        if (in_array(strtolower($order_status), $pendingStatus))
                            $order_status = 'pending';
                        if (!$orders) {
                            $orders = new Orders();
                            $orders->channel_id = $v->id;
                            $orders->order_id = (string)$s['ordNo'];
                            $orders->order_number = (string)$s['ordNo'];
                            $orders->payment_method = "";
                            $orders->order_total = $s['ordAmt'];
                            $orders->order_created_at = date("Y-m-d h:i", strtotime($s['ordStlEndDt']));
                            $orders->order_status = $order_status;
                            $orders->order_count = $s['ordQty'];
                            $orders->order_shipping_fee = $s['dlvCst'];
                            $orders->customer_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($s['ordNm'],0,49).'","'.$encryptionKey.'")');//substr($s['ordNm'],0,49);
                            $orders->customer_lname = "";
                            $orders->full_response = new \yii\db\Expression('AES_ENCRYPT("'.str_replace("\"", "'", json_encode($s)).'","'.$encryptionKey.'")');//json_encode($s);
                            $orders->save(false);
                            $ordersList[] = $orders->id;
                        } else {
                            $orders->order_created_at = date("Y-m-d h:i", strtotime($s['ordStlEndDt']));
                            $orders->order_status = $order_status;
                            $orders->save(false);
                            $ordersList[] = $orders->id;
                        }
                    }
                }
            }
        }

        if ($v->marketplace == 'Shop') {
            // insert into DB
            if ($sales) {
                foreach ($sales as $s) {
                    $orders = Orders::find()->where(['order_id' => $s['order_id'],'channel_id'=>$v->id])->one();
                    $order_status = $s['order_status'];
                    if (in_array(strtolower($order_status), $shippedStatus))
                        $order_status = 'shipped';
                    if (in_array(strtolower($order_status), $cancelStatus))
                        $order_status = 'canceled';
                    if (in_array(strtolower($order_status), $pendingStatus))
                        $order_status = 'pending';
                    if (!$orders) {
                        $orders = new Orders();
                        $orders->channel_id = $v->id;
                        $orders->order_id = (string)$s['order_id'];
                        $orders->order_number = (string)$s['order_id'];
                        $orders->order_total = $s['total'];
                        $orders->order_created_at = $s['order_date'];
                        $orders->order_updated_at = $s['order_modified_date'];
                        $orders->order_status = $order_status;
                        $orders->order_shipping_fee = $s['shipping_cost'];
                        $orders->customer_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($s['fname'],0,49).'","'.$encryptionKey.'")');//substr($s['fname'],0,49);
                        $orders->customer_lname = substr($s['lname'],0,49);//new \yii\db\Expression('AES_ENCRYPT("'.substr($s['lname'],0,49).'","'.$encryptionKey.'")');
                        $orders->full_response = new \yii\db\Expression('AES_ENCRYPT("'.str_replace("\"", "'", json_encode($s)).'","'.$encryptionKey.'")');//json_encode($s);
                        $orders->save(false);
                        $ordersList[] = $orders->id;
                    } else {
                        $orders->order_created_at = $s['order_date'];
                        $orders->order_updated_at = $s['order_modified_date'];
                        $orders->order_status = $order_status;
                        $orders->save(false);
                        $ordersList[] = $orders->id;

                    }
                    $lastOrderId = $orders->id;

                    $ca = CustomersAddress::find()->where(['billing_number' => self::phoneNumberChange($s['customer_contact'])])->one();
                    if (!$ca) {
                        $ca = new CustomersAddress();
                        $ca->billing_fname = new \yii\db\Expression('AES_ENCRYPT("'.$s['fname'].'","'.$encryptionKey.'")');//$s['fname'];
                        $ca->billing_lname = $s['lname'];//new \yii\db\Expression('AES_ENCRYPT("'.$s['lname'].'","'.$encryptionKey.'")');//$s['lname'];
                        $ca->billing_number = new \yii\db\Expression('AES_ENCRYPT("'.self::phoneNumberChange($s['customer_contact']).'","'.$encryptionKey.'")');//self::phoneNumberChange($s['customer_contact']);
                        $ca->billing_address = new \yii\db\Expression('AES_ENCRYPT("'.$s['address'].'","'.$encryptionKey.'")');//$s['address'];
                        $ca->billing_state = new \yii\db\Expression('AES_ENCRYPT("'.$s['state'].'","'.$encryptionKey.'")');//$s['state'];
                        $ca->billing_city = new \yii\db\Expression('AES_ENCRYPT("'.$s['city'].'","'.$encryptionKey.'")');//$s['city'];
                        $ca->billing_country = new \yii\db\Expression('AES_ENCRYPT("Malaysia","'.$encryptionKey.'")');//'Malaysia';
                        $ca->billing_postal_code = new \yii\db\Expression('AES_ENCRYPT("'.$s['post_code'].'","'.$encryptionKey.'")');//$s['post_code'];
                        $ca->save(false);
                    }
                    $lastCAId = $ca->id;
                    $oca = OrdersCustomersAddress::find()->where(['orders_id' => $lastOrderId, 'customer_address_id' => $lastCAId])->one();
                    if (!$oca) {
                        $oca = new OrdersCustomersAddress();
                        $oca->orders_id = $lastOrderId;
                        $oca->customer_address_id = $lastCAId;
                        $oca->save();

                    }

                }
            }
        }

        if ($v->marketplace == 'Shopee') {
            if ($sales) {
                foreach ($sales as $s) {
                    if (isset($s['ordersn'])) {
                        $orders = Orders::find()->where(['order_id' => $s['ordersn'],'channel_id'=>$v->id])->one();
                        $o = ApiController::_fetchShopeeSalesDetails($s, $v);

                        if (isset($o['orders'][0]))
                            $o = $o['orders'][0];
                        else if (isset($o['orders']))
                            $o = $o['orders'];
                        else
                            print_r($o);
                        if (!isset($orders->id)) {
                            if ($o) {
                                $order_status = $o['order_status'];
                                if (in_array(strtolower($order_status), $shippedStatus))
                                    $order_status = 'shipped';
                                if (in_array(strtolower($order_status), $cancelStatus))
                                    $order_status = 'canceled';
                                if (in_array(strtolower($order_status), $pendingStatus))
                                    $order_status = 'pending';
                                $orders = new Orders();
                                $orders->channel_id = $v->id;
                                $orders->payment_method = $o['payment_method'];
                                $orders->order_id = $s['ordersn'];
                                $orders->order_number = $s['ordersn'];
                                $orders->order_total = $o['total_amount'];
                                $orders->order_created_at = date('Y-m-d H:i:s', $o['create_time']);
                                $orders->order_updated_at = date('Y-m-d H:i:s', $o['update_time']);
                                $orders->order_status = strtolower($order_status);
                                $orders->order_shipping_fee = $o['actual_shipping_cost'];
                                $orders->customer_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($o['recipient_address']['name'],0,49).'","'.$encryptionKey.'")');//substr($o['recipient_address']['name'],0,49);
                                $orders->customer_lname = '';
                                $orders->full_response = new \yii\db\Expression('AES_ENCRYPT("'.str_replace("\"", "'", json_encode($o)).'","'.$encryptionKey.'")');//json_encode($o);
                                if (!$orders->save(false)) {
                                    print_r($o);
                                    print_r($orders->getErrors());
                                }
                                $ordersList[] = $orders->id;
                                $lastOrderId = $orders->id;
                            }
                        } else {
                            if ($o) {
                                $order_status = $s['order_status'];
                                if (in_array(strtolower($order_status), $shippedStatus))
                                    $order_status = 'shipped';
                                if (in_array(strtolower($order_status), $cancelStatus))
                                    $order_status = 'canceled';
                                if (in_array(strtolower($order_status), $pendingStatus))
                                    $order_status = 'pending';
                                $orders->order_created_at = date('Y-m-d H:i:s', $s['update_time']);
                                $orders->order_updated_at = date('Y-m-d H:i:s', $s['update_time']);
                                $orders->order_status = strtolower($order_status);
                                $orders->save(false);
                                $lastOrderId = $orders->id;
                                $ordersList[] = $orders->id;
                            }
                        }

                        if ($o) {
                            $ca = CustomersAddress::find()->where(['billing_number' => self::phoneNumberChange($o['recipient_address']['phone'])])->one();
                            if (!$ca) {
                                $ca = new CustomersAddress();

                                $ca->billing_fname = new \yii\db\Expression('AES_ENCRYPT("'.substr($o['recipient_address']['name'],0,49).'","'.$encryptionKey.'")');//substr($o['recipient_address']['name'],0,49);
                                $ca->billing_lname = '';
                                $ca->billing_number = new \yii\db\Expression('AES_ENCRYPT("'.self::phoneNumberChange($o['recipient_address']['phone']).'","'.$encryptionKey.'")');//self::phoneNumberChange($o['recipient_address']['phone']);
                                $ca->billing_address = new \yii\db\Expression('AES_ENCRYPT("'.$o['recipient_address']['full_address'].'","'.$encryptionKey.'")');//$o['recipient_address']['full_address'];
                                $ca->billing_state = new \yii\db\Expression('AES_ENCRYPT("'.$o['recipient_address']['state'].'","'.$encryptionKey.'")');//$o['recipient_address']['state'];
                                $ca->billing_city = new \yii\db\Expression('AES_ENCRYPT("'.$o['recipient_address']['city'].'","'.$encryptionKey.'")');//$o['recipient_address']['city'];
                                $ca->billing_country = new \yii\db\Expression('AES_ENCRYPT("Malaysia","'.$encryptionKey.'")');//'Malaysia';
                                $ca->billing_postal_code = new \yii\db\Expression('AES_ENCRYPT("'.$o['recipient_address']['zipcode'].'","'.$encryptionKey.'")');//$o['recipient_address']['zipcode'];
                                $ca->save(false);
                            }
                            $lastCAId = $ca->id;
                            $oca = OrdersCustomersAddress::find()->where(['orders_id' => $lastOrderId, 'customer_address_id' => $lastCAId])->one();
                            if (!$oca) {
                                $oca = new OrdersCustomersAddress();
                                $oca->orders_id = $lastOrderId;
                                $oca->customer_address_id = $lastCAId;
                                $oca->save();

                            }
                        }
                    }
                }
            }
        }
        return $ordersList;
    }

    public static function recordSalesItems($apiResponse, $orderId)
    {
        $current = time();
        $Foc = HelpUtil::GetFocSkus();
        foreach ($apiResponse as $item) {
            $skus = HelpUtil::getSkuList('sku');
            $skuId = isset($skus[$item['sku']]) ? $skus[$item['sku']] : '';
            $Order_item_id = number_format($item['order_item_id'],0,'','');
            $items = OrderItems::find()
                ->where(['order_id' => $orderId, 'shop_sku' => $item['shop_sku'],'order_item_id'=>$Order_item_id])->one();
            if (!$items) {
                $item['item_price'] = str_replace(',', '', $item['item_price']);
                $item['paid_price'] = str_replace(',', '', $item['paid_price']);
                $items = new OrderItems();
                $items->order_id = $orderId;
                $items->sku_id = $skuId;
                $items->order_item_id = $Order_item_id;
                $items->item_status = $item['status'];
                $items->shop_sku = $item['shop_sku'];
                $items->price = (double)number_format($item['item_price'], 2, '.', '');
                if ( in_array($item['sku'],$Foc) )
                    $items->paid_price = 0.00;
                else{
                    $paid_price = (double)number_format($item['paid_price'], 2, '.', '');
                    $lazada_discount = (double)number_format($item['voucher_platform'], 2, '.', '');
                    $items->paid_price = $paid_price + $lazada_discount;
                }
                $items->shipping_amount = $item['shipping_amount'];
                $items->item_created_at = $item['created_at'];
                $items->item_updated_at = $item['updated_at'];
                $items->full_response = json_encode($item);
                $items->quantity = '1';
                $items->item_sku = $item['sku'];
                if (!$items->save())
                    print_r($items->getErrors());
            } else {
                $items->item_status = $item['status'];
                $items->item_created_at = $item['created_at'];
                $items->item_updated_at = $item['updated_at'];
                $items->full_response = json_encode($item);
                //$items->quantity = $items->quantity;
                if (!$items->save(false))
                    print_r($items->getErrors());
            }

        }
    }


    public static function recordStreetSalesItems($apiResponse, $orderId)
    {

        //11 street status list
        $streetStatus = ['101' => 'Order Complete', '102' => 'Awaiting Payment', '103' => 'Waiting Pre-Order',
            '201' => 'Pre-order Payment Complete', '202' => 'Payment Complete', '301' => 'Preparing for Shipment',
            '401' => 'Shipping in Progress', '501' => 'Shipping Complete', '601' => 'Claim Requested', '701' => 'Cancellation Requested',
            '801' => ' Awaiting for Re-approval', '901' => 'Purchase Confirmed','A01'=>'Return Complete','B01'=>'Order Cancelled','C01'=>'Cancel Order upon Purchase 
Confirmation'];

        $apiResponse = $apiResponse['order'];
        if (count($apiResponse) != 45) {
            foreach ($apiResponse as $v) {
                //adding shipping address
                $address = explode(', ', $v['rcvrBaseAddr']);
                $state = $address[1];
                $addr = explode(' ', $address[0]);
                $city = $addr[1];
                $ca = CustomersAddress::find()->where(['billing_number' => self::phoneNumberChange($v['rcvrTlphn'])])->one();
                if (!$ca) {
                    $ca = new CustomersAddress();
                    $ca->billing_fname = $v['rcvrNm'];
                    $ca->billing_lname = "";
                    $ca->billing_number = self::phoneNumberChange($v['rcvrTlphn']);
                    $ca->billing_address = $v['rcvrBaseAddr'] . " " . $v['rcvrDtlsAddr'];
                    $ca->billing_state = $state;
                    $ca->billing_city = $city;
                    $ca->billing_country = 'Malaysia';
                    $ca->billing_postal_code = $v['rcvrMailNo'];

                    $ca->save();
                }
                $lastCAId = $ca->id;
                $oca = OrdersCustomersAddress::find()->where(['orders_id' => $orderId, 'customer_address_id' => $lastCAId])->one();
                if (!$oca) {
                    $oca = new OrdersCustomersAddress();
                    $oca->orders_id = $orderId;
                    $oca->customer_address_id = $lastCAId;
                    $oca->save();
                }

                $skus = HelpUtil::getSkuList('sku');
                $skuId = isset($skus[$v['sellerPrdCd']]) ? $skus[$v['sellerPrdCd']] : '';
                $items = OrderItems::find()
                    ->where(['order_id' => $orderId, 'shop_sku' => $v['prdNo']])
                    // ->andWhere(['>','created_at',$current])
                    ->one();
                if (!$items) {
                    $v['ordAmt'] = str_replace(',', '', $v['ordAmt']);
                    $v['ordPayAmt'] = str_replace(',', '', $v['ordPayAmt']);
                    $items = new OrderItems();
                    $items->order_id = $orderId;
                    $items->sku_id = $skuId;
                    $items->order_item_id = '';
                    $items->item_status = $streetStatus[$v['ordPrdStat']];
                    $items->shop_sku = $v['prdNo'];
                    $items->price = (double)number_format($v['ordAmt'], 2, '.', '');
                    $items->paid_price = (double)number_format($v['ordPayAmt'], 2, '.', '');
                    $items->item_updated_at = date("Y-m-d h:i", strtotime($v['ordStlEndDt']));
                    $items->item_created_at = date("Y-m-d h:i", strtotime($v['ordDt']));
                    $items->shipping_amount = $v['dlvCst'];
                    $items->full_response = json_encode($v);
                    $items->quantity = $v['ordQty'];
                    $items->item_sku = $v['sellerPrdCd'];
                    if (!$items->save())
                        print_r($items->getErrors());
                } else {
                    $items->item_status = $streetStatus[$v['ordPrdStat']];
                    $items->item_updated_at = date("Y-m-d h:i", strtotime($v['ordStlEndDt']));
                    $items->item_created_at = date("Y-m-d h:i", strtotime($v['ordDt']));
                    if (!$items->save(false))
                        print_r($items->getErrors());
                }
            }

        } else {
            //adding shipping address
            $address = explode(', ', $apiResponse['rcvrBaseAddr']);
            $state = $address[1];
            $addr = explode(' ', $address[0]);
            $city = $addr[1];
            $ca = CustomersAddress::find()->where(['billing_number' => self::phoneNumberChange($apiResponse['rcvrTlphn'])])->one();
            if (!$ca) {
                $ca = new CustomersAddress();
                $ca->billing_fname = $apiResponse['rcvrNm'];
                $ca->billing_lname = "";
                $ca->billing_number = self::phoneNumberChange($apiResponse['rcvrTlphn']);
                $ca->billing_address = $apiResponse['rcvrBaseAddr'] . " " . $apiResponse['rcvrDtlsAddr'];
                $ca->billing_state = $state;
                $ca->billing_city = $city;
                $ca->billing_country = 'Malaysia';
                $ca->billing_postal_code = $apiResponse['rcvrMailNo'];

                $ca->save();
            }
            $lastCAId = $ca->id;
            $oca = OrdersCustomersAddress::find()->where(['orders_id' => $orderId, 'customer_address_id' => $lastCAId])->one();
            if (!$oca) {
                $oca = new OrdersCustomersAddress();
                $oca->orders_id = $orderId;
                $oca->customer_address_id = $lastCAId;
                $oca->save();
            }

            $skus = HelpUtil::getSkuList('sku');
            $skuId = isset($skus[$apiResponse['sellerPrdCd']]) ? $skus[$apiResponse['sellerPrdCd']] : '';
            $items = OrderItems::find()
                ->where(['order_id' => $orderId, 'shop_sku' => $apiResponse['prdNo']])
                // ->andWhere(['>','created_at',$current])
                ->one();
            if (!$items) {
                $apiResponse['ordAmt'] = str_replace(',', '', $apiResponse['ordAmt']);
                $apiResponse['ordPayAmt'] = str_replace(',', '', $apiResponse['ordPayAmt']);
                $items = new OrderItems();
                $items->order_id = $orderId;
                $items->sku_id = $skuId;
                $items->order_item_id = '';
                $items->item_status = $streetStatus[$apiResponse['ordPrdStat']];
                $items->shop_sku = $apiResponse['prdNo'];
                $items->price = (double)number_format($apiResponse['ordAmt'], 2, '.', '');
                $items->paid_price = (double)number_format($apiResponse['ordPayAmt'], 2, '.', '');
                $items->item_updated_at = date("Y-m-d h:i", strtotime($apiResponse['ordStlEndDt']));
                $items->item_created_at = date("Y-m-d h:i", strtotime($apiResponse['ordDt']));
                $items->shipping_amount = $apiResponse['dlvCst'];
                $items->full_response = json_encode($apiResponse);
                $items->quantity = $apiResponse['ordQty'];
                $items->item_sku = $apiResponse['sellerPrdCd'];
                if (!$items->save())
                    print_r($items->getErrors());
            } else {
                $items->item_status = $streetStatus[$apiResponse['ordPrdStat']];
                $items->item_updated_at = date("Y-m-d h:i", strtotime($apiResponse['ordStlEndDt']));
                $items->item_created_at = date("Y-m-d h:i", strtotime($apiResponse['ordDt']));
                if (!$items->save(false))
                    print_r($items->getErrors());
            }
        }


    }

    public static function recordShopSalesItems($apiResponse, $orderId)
    {
        foreach ($apiResponse as $v) {
            $skus = HelpUtil::getSkuList('sku');
            $skuId = isset($skus[$v['item_sku']]) ? $skus[$v['item_sku']] : '';
            $items = OrderItems::find()
                ->where(['order_id' => $orderId, 'shop_sku' => $v['shop_sku']])
                ->one();
            if (!$items) {
                $v['price'] = str_replace(',', '', $v['price']);
                $v['paid_price'] = str_replace(',', '', $v['paid_price']);
                $items = new OrderItems();
                $items->order_id = $orderId;
                $items->sku_id = $skuId;
                $items->order_item_id = $v['order_id'];
                $items->item_status = $v['order_status'];
                $items->shop_sku = $v['shop_sku'];
                $items->price = (double)number_format($v['price'], 2, '.', '');
                $items->paid_price = (double)number_format($v['paid_price'], 2, '.', '');
                $items->item_updated_at = $v['order_date'];
                $items->item_created_at = $v['order_date'];
                //$items->shipping_amount = $v['dlvCst'];
                $items->full_response = json_encode($v);
                $items->quantity = $v['quantity'];
                $items->item_sku = $v['item_sku'];
                if (!$items->save())
                    print_r($items->getErrors());
            } else {
                $items->item_status = $v['order_status'];
                $items->item_updated_at = $v['order_date'];
                $items->item_created_at = $v['order_date'];
                if (!$items->save(false))
                    print_r($items->getErrors());
            }
        }


    }


    public static function recordShopeeSalesItems($apiResponse, $orderId)
    {

        $skus = HelpUtil::getSkuList('sku');
        foreach ($apiResponse['items'] as $v) {

            $skuId = isset($skus[$v['item_sku']]) ? $skus[$v['item_sku']] : '';
            $v['variation_discounted_price'] = str_replace('RM ', '', $v['variation_discounted_price']);
            if ( $v['variation_id'] == 0 ){
                $items = OrderItems::find()
                    ->where(['order_id' => $orderId, 'item_sku' => $v['item_sku']])
                    ->one();
            }else{
                OrderItems::deleteAll(['item_sku'=>$v['item_sku']]);

                $items = OrderItems::find()
                    ->where(['order_id' => $orderId, 'item_sku' => $v['variation_sku']])
                    ->one();
            }
            if (!$items) {
                $v['variation_discounted_price'] = str_replace(',', '', $v['variation_discounted_price']);
                $items = new OrderItems();
                $items->order_id = $orderId;
                $items->sku_id = $skuId;
                $items->order_item_id = $v['item_id'];
                $items->item_status = strtolower($apiResponse['order_status']);
                $items->shop_sku = (string)$v['item_id'];
                $items->price = (double)number_format($v['variation_discounted_price'], 2, '.', '');
                $items->paid_price = (double)number_format($v['variation_discounted_price'], 2, '.', '');
                $items->item_updated_at = date('Y-m-d H:i:s', $apiResponse['update_time']);
                $items->item_created_at = date('Y-m-d H:i:s', $apiResponse['create_time']);
                //$items->shipping_amount = $v['dlvCst'];
                $items->full_response = json_encode($v);
                $items->quantity = $v['variation_quantity_purchased'];
                $items->item_sku = (($v['variation_id'] == 0)) ? $v['item_sku'] : $v['variation_sku'];
                if (!$items->save())
                    print_r($items->getErrors());
            } else {
                $items->item_status = strtolower($apiResponse['order_status']);
                $items->item_updated_at = date('Y-m-d H:i:s', $apiResponse['update_time']);
                $items->item_created_at = date('Y-m-d H:i:s', $apiResponse['create_time']);
                if (!$items->save(false))
                    print_r($items->getErrors());
            }
        }


    }

    public static function UpdateOrders($v = null)
    {
        if ($v->marketplace == 'Street') {


            $orders = Orders::find()->where(['is_update' => '0', 'channel_id' => $v->id])->all();

            foreach ($orders as $o) {
                $orderAmt = [];
                $orderShipAmt = [];
                $orderQty = [];
                $i = 1;
                $items = OrderItems::find()->where(['order_id' => $o->id])->all();
                foreach ($items as $item) {
                    $orderAmt[] = str_replace(',', '', $item->paid_price);
                    $orderShipAmt[] = $item->shipping_amount;
                    $orderQty[] = $item->quantity;
                    $i++;
                }

                $o = Orders::findOne(['id' => $o->id]);
                $o->order_total = array_sum($orderAmt);
                $o->order_shipping_fee = array_sum($orderShipAmt);
                $o->order_count = array_sum($orderQty);
                $o->is_update = 1;
                $o->save(false);
            }
        } else if ($v->marketplace == 'Shop' || $v->marketplace == 'Shopee') {
            $orders = Orders::find()->where(['is_update' => '0', 'channel_id' => $v->id])->all();
            foreach ($orders as $o) {
                $orderQty = [];
                $i = 1;
                $items = OrderItems::find()->where(['order_id' => $o->id])->all();
                foreach ($items as $item) {
                    $orderQty[] = $item->quantity;
                    $i++;
                }
                $o = Orders::findOne(['id' => $o->id]);
                $o->order_count = array_sum($orderQty);
                $o->is_update = 1;
                $o->save(false);
            }
        } else {
            $orders = Orders::find()->where(['is_update' => '0', 'channel_id' => $v->id])->all();
            foreach ($orders as $o) {
                $o->is_update = 1;
                $o->save(false);
            }
        }

    }


    public static function updateSalesStatus($fileData = [])
    {
        $sql = "SELECT orders.* FROM orders 
                INNER JOIN channels c ON c.id = orders.`channel_id`
                WHERE c.`marketplace` = 'lazada' ";
        $orders = Orders::findBySql($sql)->asArray()->all();
        foreach ($orders as $o) {
            if ($o) {
                $oi = OrderItems::find()->where(['order_id' => $o['id']])->all();
                if ($oi) {
                    foreach ($oi as $i) {
                        if (isset($fileData[$o['order_number']])) {
                            // echo $i->shop_sku."<br/>";
                            if (isset($fileData[$o['order_number']][$i->shop_sku])) {
                                $oix = OrderItems::findOne(['id' => $i->id]);
                                $oix->is_paid = $fileData[$o['order_number']][$i->shop_sku]['status'];
                                $oix->settle_date = $fileData[$o['order_number']][$i->shop_sku]['tdate'];
                                $oix->paid_batch_file = $fileData[$o['order_number']][$i->shop_sku]['file'];
                                $oix->save(false);
                            }

                        }

                    }
                }

            }
        }

    }


}