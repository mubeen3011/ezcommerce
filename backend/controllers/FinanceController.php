<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/7/2020
 * Time: 12:53 PM
 */
namespace backend\controllers;

use backend\util\FinanceUtil;
use common\models\Channels;

class FinanceController extends MainController{

    public function actionAudit(){

        //$itemCharges = $this->GetCharges(['209682805739694']);

        $report = FinanceUtil::GetReport($_GET);
        $channels = Channels::find()->where(['marketplace'=>'lazada'])->asArray()->all();
        return $this->render('audit', ['report'=>$report['report'],'channels'=>$channels, 'total_records'=>$report['total_records']]);

    }
    private function GetCharges($orderItems){
        $sql = "SELECT b.`Order No.`, b.`Order Item No.`, b.`Transaction Type`, b.`Transaction Date`,b.`Fee Name`,b.`Order Item Status`,
                b.Amount,b.`VAT in Amount`,b.`WHT Amount`, b.`WHT included in Amount` FROM blip_statments_lazada b GROUP BY b.`Fee Name`";
        $result = \Yii::$app->db->createCommand($sql)->queryAll();
        $data = [];
        $feeNames=[];
        foreach ( $result as $value ){
            if ($value['Fee Name']=='Shipping Fee Paid by Seller' || $value['Fee Name']=='Adjustments Others' || $value['Fee Name']=='Adjustments Shipping'
                || $value['Fee Name']=='Adjustments Shipping Fee' || $value['Fee Name']=='Auto. Shipping fee subsidy (by Lazada)' || $value['Fee Name']=='Other Credit - Non Taxable'){
                $feeNames[] = $value['Fee Name'];
            }

        }
        $feeNames = array_unique($feeNames);
        $sql = "SELECT b.`Order No.`, b.`Order Item No.`, b.`Transaction Type`, b.`Transaction Date`,b.`Fee Name`,b.`Order Item Status`,
                b.Amount,b.`VAT in Amount`,b.`WHT Amount`, b.`WHT included in Amount` FROM avent_statments_lazada b 
                WHERE b.`Order Item No.` IN (".implode(',',$orderItems).");
                ";
        $result = \Yii::$app->db->createCommand($sql)->queryAll();
        foreach ( $result as $value ){

            $data[$value['Order Item No.']]['Trx']['item_status'] = $value['Order Item Status'];
            $data[$value['Order Item No.']]['Trx']['order_id'] = $value['Order No.'];
            $data[$value['Order Item No.']]['Trx']['item_id'] = $value['Order Item No.'];
            unset($value['Order Item Status']);
            unset($value['WHT included in Amount']);
            $data[$value['Order Item No.']]['Trx']['charges'][$value['Fee Name']] = $value;

        }
        foreach ( $data as $orderItemId=>$detail ){
            foreach ( $detail as $ItemDetail ){
                foreach ( $feeNames as $feeNameVal ){
                    if ( !isset($ItemDetail['charges'][$feeNameVal]) ){
                        $newFee=[
                            'Order No.'=>$ItemDetail['order_id'],
                            'Order Item No.'=>$ItemDetail['item_id'],
                            'Transaction Type'=>'-',
                            'Fee Name' => $feeNameVal,
                            'Amount' => '-',
                            'VAT in Amount' => '-',
                            'WHT Amount' => '-'
                        ];
                        $data[$ItemDetail['item_id']]['Trx']['charges'][$feeNameVal]=$newFee;
                    }
                }

            }
        }
        foreach ( $data as $key=>$value ){
            if (isset($value['Trx']['charges']['Shipping Fee Paid by Seller']['Transaction Date'])){
                $value['Trx']['Date'] = $value['Trx']['charges']['Shipping Fee Paid by Seller']['Transaction Date'];
                $data[$key]=$value;
            }
        }
        foreach ( $data as $key=>$value ){
            if (!isset($value['Trx']['Date'])){
                if ( isset($value['Trx']['charges']['FBL Handling Fee']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['FBL Handling Fee']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Item Price Credit']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Item Price Credit']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Lost Claim']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Lost Claim']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Adjustments Others']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Adjustments Others']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Commission Rebate']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Commission Rebate']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Seller Funded Marketing Voucher']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Seller Funded Marketing Voucher']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Adjustments Claim']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Adjustments Claim']['Transaction Date'];
                    $data[$key]=$value;
                }
                elseif ( isset($value['Trx']['charges']['Adjustments Others']['Transaction Date']) ){
                    $value['Trx']['Date'] = $value['Trx']['charges']['Adjustments Others']['Transaction Date'];
                    $data[$key]=$value;
                }
                else{
                    echo 'Debug';
                    $this->debug($value);
                }
            }
        }
        return $data;
    }
    public function actionFinanceExport(){
        $Data = FinanceUtil::GetReport($_GET,1);
        //$this->debug($Data);
        $file = fopen('finance_reports/Finance-Report'.$_GET['Date_range'].'.csv', 'w');

// save the column headers
        //$this->debug($header);
        $head=[];
        foreach ($Data['report'][0] as $headName=>$val){
            if ($headName=='commission_amount'):
                $head[] = str_replace('_','', ucwords($headName) ).' Expected Commission (5%)';
            elseif ($headName=='transaction_fee'):
                $head[] = str_replace('_','', ucwords($headName) ).' Expected Transaction Fee ( 2% )';
            elseif ($headName=='fbl_fee'):
                $head[] = str_replace('_','', ucwords($headName) ). ' Expected Fbl Fee ( 2 MYR )';
            elseif ($headName=='expected_receive_amount'):
                $head[] = str_replace('_','', ucwords($headName) ).' Paid Price - ( Commission + TransactionFee + FBL Fee )';
            elseif ($headName=='total_feeses'):
                $head[] = str_replace('_','', ucwords($headName) ).' Sum of all feeses';
            elseif ($headName=='receiving_difference'):
                $head[] = str_replace('_','', ucwords($headName) ).' Expected Recieving - Total feeses';
            else:
                $head[] = str_replace('_',' ',ucwords($headName));
            endif;

        }
        //$this->debug($head);
        $content=[];
        foreach ($Data['report'] as $key=>$dataDetail){
            $contentDetail=[];

            foreach ( $dataDetail as $value ){
                $contentDetail[] = $value;
            }
            $content[]=$contentDetail;
        }
        fputcsv($file, $head);

        $data = $content;

        foreach ($data as $row)
        {
            fputcsv($file, $row);
        }
        $this->redirect('/finance_reports/Finance-Report'.$_GET['Date_range'].'.csv');
        fclose($file);
    }
}