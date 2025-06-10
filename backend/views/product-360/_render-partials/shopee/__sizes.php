<?php
/**
 * Created by PhpStorm.
 * User: Abdullah khan, mrabd423@gmail.com
 * Date: 8/14/2019
 * Time: 11:37 AM
 */
$Sizes = array (
     'XS' => 'Xtra Small',
     'S' => 'Small',
     'M' => 'Medium',
     'L' => 'Large',
     'XL' => 'Xtra Large'
    );
?>
<select class="color-list select2 form-control" name="p360[variations][<?=$key?>][prestashop][size]" id="v-color-<?=$key?>">
    <option>Select Size</option>
    <?php foreach ( $Sizes as $Size_Code=>$Size_Name ) :  ?>
        <option value="<?=$Size_Name?>" <?=(isset($s_size) && ($Size_Name==$s_size)) ? 'selected' : ''?>><?=$Size_Name?></option>
    <?php endforeach; ?>
</select>
