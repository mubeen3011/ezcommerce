<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/10/2020
 * Time: 4:24 PM
 */
?>
<style>
    th, td{
        padding: 0.25rem !important;
    }
    .show_items{
        cursor: pointer;
    }
</style>
<table id="zip-table" class="table table-bordered table-striped dataTable no-footer">
    <thead>
    <tr>
        <th></th>
        <th>Country & States</th>
    </tr>
    </thead>
    <tbody>

    <?php
    foreach ( $CS as $Detail ){
        ?>
        <tr class="state-<?=$Detail['state_id']?>">
            <td>
                <span data-state-name="<?=$Detail['state_id']?>" class="fa show_items <?=isset($pre_selected_zip[$Detail['state_id']]) ? 'fa-minus' : 'fa-plus'?>"></span>
            </td>
            <td><?=$Detail['country']?> - <?=$Detail['state_name']?></td>
        </tr>
    <?php
        if ( isset($pre_selected_zip[$Detail['state_id']]) ){
            $total_zip_of_state = count($pre_selected_zip[$Detail['state_id']]);
            $selected_zip_of_state = end($pre_selected_zip[$Detail['state_id']]);
            $selected_zip_of_state = $selected_zip_of_state['attached_zip'];
            ?>
            <tr id="cities-of-<?=$Detail['state_id']?>">

                <td colspan="9">
                    <div clas="table-responsive" style="height: 400px;overflow: scroll;">
                        <table class="table full-color-table full-muted-table hover-table" style="margin-left:15px">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" data-state-id="<?=$Detail['state_id']?>" class="select-all" <?=($total_zip_of_state==$selected_zip_of_state) ? 'checked' : ''?>/>
                                    </th>
                                    <th>City</th>
                                    <th>ZipCode</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ( $pre_selected_zip[$Detail['state_id']] as $ZipDetail ){
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" <?=($ZipDetail['selected']) ? 'checked' : ''?> class="state-zip-<?=$Detail['state_id']?>" name="zipcodes[]" value="<?=$ZipDetail['zipcode']?>"/>
                                    </td>
                                    <td><?=$ZipDetail['city_name']?></td>
                                    <td><?=$ZipDetail['zipcode']?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>

                </td>
            </tr>
    <?php
        }
    }
    ?>

    </tbody>
</table>