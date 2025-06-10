<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/10/2020
 * Time: 4:17 PM
 */
?>
<div class="row">
    <div class="col-md-4"></div>
    <div class="col-md-4">
        <form class="warehouse-form" action="" method="get">
            <div class="form-group">
                <label for="example-email">Warehouses <span class="help"></span></label>
                <select name="warehouse" class="form-control warehouse-list">
                    <option></option>
                    <?php
                    foreach ( $warehouses as $value ){
                        ?>
                        <option value="<?=$value['id']?>" <?=(isset($_GET['warehouse']) && $_GET['warehouse']==$value['id']) ? 'selected' : ''?>>
                            <?=ucwords($value['name'])?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>

        </form>
    </div>
    <div class="col-md-4"></div>
</div>
