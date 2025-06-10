<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\SkusCrawlSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '';
$this->params['breadcrumbs'][] = 'Skus Details';
$this->params['breadcrumbs'][] = 'Add Product Ids in Sku : '.$_GET['sku'];
?>
<div class="row">
    <div class="col-12">
        <div class="card">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Add Product Ids in Sku : <?=$_GET['sku']?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <p>
                    <a href="javascript:;" onclick="AddDynamicPIdInputs('<?=str_replace('/','',$_GET['sku'])?>')">
                        Add More
                    </a>
                </p>
                <br>
                <style type="text/css">
                    .tg  {border-collapse:collapse;border-spacing:0;}
                    .tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
                    .tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
                    .tg .tg-us36{border-color:inherit;vertical-align:top}
                </style>
                <table class="tg table">
                    <form action="">

                        <tr>
                            <th class="tg-us36 sku_column">SKU</th>
                            <th class="tg-us36">Lazada</th>
                            <th class="tg-us36">11Street</th>
                            <th class="tg-us36">Shopee</th>
                        </tr>
                        <?php
                        foreach ( $sku_list as $key=>$value ){
                            ?>
                            <tr>
                                <td class="tg-us36 sku_column" id="<?=str_replace('/','',$key)?>">

                    <span class="dynamic_input">
                    </span>
                                </td>
                                <?php
                                if( isset($value[1]) ){
                                    $explode_sku=explode('?',$value[1]['product_ids']);
                                    ?>
                                    <td class="tg-us36">
                                        <?php
                                        foreach ($explode_sku as $sku_key=>$sku_value){
                                            echo '<p id="'.$sku_value.'">'.$sku_value.'  <a style="color: red;" href="javascript:;" onclick="DeleteProductId(\''.$sku_value.'\')">X</a>'.'<br /></p>';
                                        }
                                        ?>
                                    </td>
                                    <?php
                                }else{
                                    ?>
                                    <td class="tg-us36">

                                    </td>
                                    <?php
                                }
                                ?>

                                <?php
                                if( isset($value[3]) ){
                                    $explode_sku=explode('?',$value[3]['product_ids']);
                                    ?>
                                    <td class="tg-us36">
                                        <?php
                                        foreach ($explode_sku as $sku_key=>$sku_value){
                                            echo '<p id="'.$sku_value.'">'.$sku_value.'  <a style="color: red;" href="javascript:;" onclick="DeleteProductId(\''.$sku_value.'\')">X</a>'.'<br /></p>';
                                        }
                                        ?>
                                    </td>
                                    <?php
                                }else{
                                    ?>
                                    <td class="tg-us36">
                                    </td>
                                    <?php
                                }
                                ?>
                                <?php
                                if( isset($value[2]) ){
                                    $explode_sku=explode('?',$value[2]['product_ids']);
                                    ?>
                                    <td class="tg-us36">
                                        <?php
                                        foreach ($explode_sku as $sku_key=>$sku_value){
                                            echo '<p id="'.$sku_value.'">'.$sku_value.'  <a style="color: red;" href="javascript:;" onclick="DeleteProductId(\''.$sku_value.'\')">X</a>'.'<br /></p>';
                                        }
                                        ?>
                                    </td>
                                    <?php
                                }else{
                                    ?>
                                    <td class="tg-us36">

                                    </td>
                                    <?php
                                }
                                ?>
                            </tr>
                            <?php

                        }
                        ?>
                        <input type="hidden" value="<?=$_GET['sku']?>" id="sku_id" />
                </table>

            </div>
        </div>
    </div>
</div>
<script>
    function AddDynamicPIdInputs(value){
        //alert(value);
        $('.sku_column').show();
        $('#'+value+' .dynamic_input').text('');
        $('#'+value+' .dynamic_input').html('<br /><div class="form-group"><label>Lazada Id</label><input type=\"text\" id=\"lazada_id\" class=\"form-control\"/></div><div class="form-group"><label>11Street Id</label><input id=\"elevenstreet_id\" type=\"text\" class=\"form-control\"/></div><div class="form-group"><label>Shopee Id</label><input type=\"text\" id=\"shopee_id\" class=\"form-control\"/></div><br /> <button id=\"update_skus\" class="form-control btn-success">Update</button> ');
    }
    function DeleteProductId(value){

        $.ajax({
            async: false,
            type: "post",
            url: "/crawl/delete-product-id",
            data: {'pid':value},
            dataType: "json",
            beforeSend: function () {},
            success: function (data) {
                if( data.success=true ){
                    alert('successfully deleted');
                    $('#'+value).text('');
                }else{
                    alert('some thing went wrong');
                }
            },
        });
    }
</script>
<?php
$this->registerJs("
$(document).on('click', '#update_skus', function(){
        $.ajax({
            async: false,
            type: \"post\",
            url: \"/crawl/add-new-skus\",
            data: {'lazada_id':$('#lazada_id').val(),'elevenstreet_id':$('#elevenstreet_id').val(),'shopee_id':$('#shopee_id').val(),'sku_id':$('#sku_id').val()},
            dataType: \"json\",
            beforeSend: function () {},
            success: function (data) {
                if( data.lazada=='success' ){
                    alert('Lazada product id added successfully');
                }
                if( data.elevenstreet=='success' ){
                    alert('11Street product id added successfully');
                }
                if( data.shopee=='success' ){
                    alert('Shopee product id added successfully');
                }
            },
        });
        
    });
    
    
");