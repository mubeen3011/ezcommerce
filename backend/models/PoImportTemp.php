<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "po_import_temp".
 *
 * @property int $id
 * @property string $po_date
 * @property string $po_number
 * @property string $ship_to
 * @property string $sku
 * @property string $in_bound_sku
 * @property int $quantity
 * @property string $er_no_1
 * @property int $er_qty
 * @property string $warehouse
 */
class PoImportTemp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'po_import_temp';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['po_date'], 'safe'],
            [['quantity', 'er_qty'], 'integer'],
            [['warehouse'], 'string'],
            [['po_number', 'ship_to', 'sku', 'in_bound_sku'], 'string', 'max' => 255],
            [['er_no_1'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'po_date' => 'Po Date',
            'po_number' => 'Po Number',
            'ship_to' => 'Ship To',
            'sku' => 'Sku',
            'in_bound_sku' => 'In Bound Sku',
            'quantity' => 'Quantity',
            'er_no_1' => 'Er No 1',
            'er_qty' => 'Er Qty',
            'warehouse' => 'Warehouse',
        ];
    }
}
