<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/21/2020
 * Time: 4:58 PM
 */
?>

<tr id="cities-of-<?=$state_id?>">

    <td colspan="9">
        <div clas="table-responsive" style="height: 400px;overflow: scroll;">
            <table class="table full-color-table full-muted-table hover-table" style="margin-left:15px">
                <thead>
                <tr>
                    <th><input type="checkbox" data-state-id="<?=$state_id?>" class="select-all"/></th>
                    <th>City</th>
                    <th>ZipCode</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $zipcodes as $Detail ){
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="state-zip-<?=$state_id?>" name="zipcodes[]" value="<?=$Detail['zipcode']?>"/>
                        </td>
                        <td><?=$Detail['city_name']?></td>
                        <td><?=$Detail['zipcode']?></td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
        </div>

    </td>
</tr>
