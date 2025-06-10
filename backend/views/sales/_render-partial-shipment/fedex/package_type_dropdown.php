<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/7/2020
 * Time: 2:09 PM
 */
?>
<?php
$package_type=[];
if ( $service_type=='FEDEX_GROUND' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
}
else if ( $service_type=='GROUND_HOME_DELIVERY' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
}
else if ( $service_type=='FEDEX_2_DAY' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
else if ( $service_type=='FEDEX_2_DAY_AM' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
else if ( $service_type=='FEDEX_EXPRESS_SAVER' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
}
else if ( $service_type=='STANDARD_OVERNIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
else if ( $service_type=='PRIORITY_OVERNIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
else if ( $service_type=='FIRST_OVERNIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
else if ( $service_type=='FEDEX_2_DAY_FREIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
}
else if ( $service_type=='FEDEX_3_DAY_FREIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
}
else if ( $service_type=='FEDEX_NEXT_DAY_FREIGHT' ){
    $package_type['YOUR_PACKAGING'] = 'Package';
    $package_type['FEDEX_ENVELOPE'] = 'FedEx® Envelope';
    $package_type['FEDEX_EXTRA_LARGE_BOX'] = 'FedEx One Rate® Extra Large Box';
    $package_type['FEDEX_LARGE_BOX'] = 'FedEx One Rate® Large Box';
    $package_type['FEDEX_MEDIUM_BOX'] = 'FedEx One Rate® Medium Box';
    $package_type['FEDEX_PAK'] = 'FedEx® Pak';
    $package_type['FEDEX_SMALL_BOX'] = 'FedEx One Rate® Small Box';
    $package_type['FEDEX_TUBE'] = 'FedEx® Tube';
    /*$package_type['FEDEX_10KG_BOX'] = 'FedEx® 10kg Box';
    $package_type['FEDEX_25KG_BOX'] = 'FedEx® 25kg Box';*/
    $package_type['FEDEX_BOX'] = 'FedEx® Box';
}
?>
<select style="width: 97% !important;" class="form-control p-0 fedex-package-option" id="input111" required="">
    <option></option>
<?php
foreach ( $package_type as $package_Type_key=>$package_type_name ){
    ?>
    <option value="<?=$package_Type_key?>"><?=$package_type_name?></option>
    <?php
}
?>
</select>