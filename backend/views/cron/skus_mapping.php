<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 9/12/2018
 * Time: 9:29 AM
 */
$BundleDeal=[];
$counter=0;
foreach ( $skus_list as $key=>$value ){
    if ($counter>0){
        continue;
    }
    $input = preg_quote($value, '~'); // don't forget to quote input string!

    if ( substr($input,-1)=='P'
        || substr($input,-1)=='B'
        || substr($input,-1)=='O'
        || substr($input,-1)=='R'
        || substr($input,-1)=='L'
        || substr($input,-1)=='Y'
        || substr($input,-1)=='G'
        || substr($input,-1)=='('
        || substr($input,-1)==')'
        || substr($input,-1)=='/'
        || substr($input,0,4)=='Bund'
        || substr($input,0,4) == 'BUND' || substr($input,0,3) == '100'
        || substr($input,0,3) == '915'
        || substr($input,0,3) == '929'
        || substr($input,0,3) == '871')
        continue;
    //$data = array('SCF760/00', 'SCF760/00B', 'SCF760/00R', 'SCF762/00 ', 'SCF782/30');
    $result = preg_grep('~' . substr($input ,0,9). '~', $skus_list);
    if (count($result)==1)
        continue;
    $BundleDeal[$value] = $result;
    $counter++;
}
/*echo '<pre>';
print_r($BundleDeal);*/
//die;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <h3>Skus Mapping Screen</h3>
                    <form id="skus_map_form">
                        <table class="table">
                            <thead>
                            <tr style="background-color: lightgreen">
                                <td><h4>Parent</h4></td>
                                <?php
                                $counter=0;
                                foreach ($BundleDeal as $key=>$value){
                                    /*echo '<pre>';
                                    print_r($value);
                                    die;*/
                                    foreach ( $value as $key1=>$value1 ){
                                        $parent_sku_id=$key;
                                        if ($counter>0){
                                            continue;
                                        }
                                     /*   echo '<pre>';
                                        print_r($value1);
                                        die;*/
                                        ?>
                                        <td colspan="3" style="font-size: 20px" align="center"><?=$value1?></td>
                                        <input type="hidden" name="parent_sku_id" value="<?=$key1?>"/>
                                        <?php
                                        $counter++;
                                    }
                                }
                                ?>

                            </tr>
                            </thead>
                            <tbody>
                                <tr style="background-color: lightgrey">
                                    <td><h4>Childs</h4></td>
                                    <?php
                                    $counter=0;
                                    foreach ($BundleDeal as $key=>$value){
                                        foreach ($value as $key1=>$value1){
                                            if ($counter==0){
                                                $counter++;
                                                continue;
                                            }
                                            ?>
                                            <td style="font-size: 20px" align="center"><input checked name="child_sku_ids[<?=$key1?>]" type="checkbox"><?=$value1?></td>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tr>
                            </tbody>

                        </table>
                        <button type="button" class="btn btn-info update_sku_mapp">Update</button>
                        <button type="button" class="btn btn-danger skip_sku_mapp">Skip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJs("
    $('.update_sku_mapp').click(function(){
        $.ajax({
            type: 'GET',
            url: '/cron/update-child-skus',
            data: $('#skus_map_form').serialize(),
            dataType: \"text\",
            beforeSend: function () {},
            success: function (data) {
            //alert('matched');
            location.reload();
            },
        });
    });
    
    
    
    
    
    $('.skip_sku_mapp').click(function(){
        $.ajax({
            type: 'GET',
            url: '/cron/update-child-skus',
            data: $('#skus_map_form').serialize()+'&skip=1',
            dataType: \"text\",
            beforeSend: function () {},
            success: function (data) {
            //alert('skip');
                location.reload();
            },
        });
    });
");