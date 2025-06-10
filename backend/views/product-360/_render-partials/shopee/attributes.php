<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 5/10/2019
 * Time: 4:15 PM
 */
$key = 0;
if(array_key_exists('attributes', $attributes)){
foreach ($attributes['attributes'] as $attr) {
    //if ($attr['attribute_name'] != 'Brand') {
    //if ($attr['attribute_name']=='Brand'){
     //   echo '<input type="hidden" name="p360[shopee_attributes][brand_attr_id]" value="'.$attr['attribute_id'].'"/>';
      //  continue;
    //}
        $required = '';
        $astaric = ' ';
        if($attr['is_mandatory']){
            $required='required';
            $astaric = ' * ';

        }
        echo '<div class="form-group">
                <label>' . $attr['attribute_name'] . '</label><small>' . $astaric .  'Shopee specific</small>
                <select name="p360[shopee_attributes][]" class="form-control select2 form-control-line ' . $required . '">
                    <option value="">Select ' . $attr['attribute_name'] . '</option>';
        $options = $attr['options'];
        $sel = "";
        foreach ($options as $opt) {
            $sel = in_array($attr['attribute_id'].'-'.$opt, $attrList) ? 'selected' : '';
            echo "<option " . $sel . " value='" . $attr['attribute_id'] . "-" . $opt . "'>" . $opt . "</option>";
        }
        echo '</select>
                </div>';

        $key++;

    //}
}
}else{
    echo '<div>' . $attributes['error'] . ' : ' . $attributes['msg'] .'</div><br/>';
}
?>