<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/11/2019
 * Time: 11:06 AM
 */
?>
<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/5/2019
 * Time: 4:12 PM
 */
$ServiceTypes=[];
$ServiceTypes['FEDEX_GROUND']='FedEx Ground®';
$ServiceTypes['GROUND_HOME_DELIVERY']='FedEx Home Delivery®';
$ServiceTypes['FEDEX_2_DAY']='FedEx 2Day®';
$ServiceTypes['FEDEX_2_DAY_AM']='FedEx 2Day® A.M.';
$ServiceTypes['FEDEX_EXPRESS_SAVER']='FedEx Express Saver®';
$ServiceTypes['STANDARD_OVERNIGHT']='FedEx Standard Overnight®';
$ServiceTypes['PRIORITY_OVERNIGHT']='FedEx Priority Overnight®';
$ServiceTypes['FIRST_OVERNIGHT']='FedEx First Overnight®';
$ServiceTypes['FEDEX_2_DAY_FREIGHT']='FedEx 2Day® Freight';
$ServiceTypes['FEDEX_3_DAY_FREIGHT']='FedEx 3Day® Freight';
$ServiceTypes['FEDEX_NEXT_DAY_FREIGHT']='FedEx First Overnight® Freight';

$PackageType=[];
$PackageType['YOUR_PACKAGING'] = 'Package';
$PackageType['FEDEX_ENVELOPE']='FedEx One Rate® Envelope';
$PackageType['FEDEX_EXTRA_LARGE_BOX']='FedEx One Rate® Extra Large Box';
$PackageType['FEDEX_LARGE_BOX']='FedEx One Rate® Large Box';
$PackageType['FEDEX_MEDIUM_BOX']='FedEx One Rate® Medium Box';
$PackageType['FEDEX_PAK']='FedEx One Rate® Pak';
$PackageType['FEDEX_SMALL_BOX']='FedEx One Rate® Small Box';
$PackageType['FEDEX_TUBE']='FedEx One Rate® Tube';
$PackageType['FEDEX_10KG_BOX']='FedEx® 10kg Box';
$PackageType['FEDEX_25KG_BOX']='FedEx® 25kg Box';
$PackageType['FEDEX_BOX']='FedEx® Box';
$PackageType['FEDEX_ENVELOPE']='FedEx® Envelope';
$PackageType['FEDEX_PAK']='FedEx® Pak';
$PackageType['FEDEX_TUBE']='FedEx® Tube';
?>
<div style="padding: 10px 0px 0px 10px;border-radius: 15px;background-color: whitesmoke;" class="package-details-fedex col-12">
        <h3 style="text-align: center;">Package Detail</h3>
        <br/>
        <form class="floating-labels m-t-40 fedex-shipping-rates-form">
            <div class="row">
            <?php
            if ( $AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='NORMALIZED' )
            {
                ?>
                <div class="form-group m-b-40 col-md-4 fedex-service-type-form-group">
                    <select style="width: 100% !important;" class="form-control p-0 fedex-service-type" id="input111" required="">
                        <option></option>
                        <?php
                        foreach ( $ServiceTypes as $Service=>$ServiceName ){
                            ?>
                            <option value="<?=$Service?>"><?=str_replace('_',' ',$ServiceName)?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <span class="bar"></span>
                    <label for="input111">Service Type *</label>
                </div>
                <div class="form-group m-b-40 col-md-4 fedex-package-option-form-group">
                    <select style="width: 97% !important;" class="form-control p-0 fedex-package-option" id="input111" required="">
                        <option></option>
                    </select>
                    <span class="bar"></span>
                    <label for="input111">Package Type *</label>
                </div>
                <div class="form-group m-b-40 col-md-4 fedex-ship-date-form-group">
                    <input type="text" name="ship_date" autofill="off" class="form-control fedex-ship-date" id="fedex-datepicker-autoclose" placeholder="mm/dd/yyyy" style="width: 95%;">
                    <span class="bar"></span>
                    <label for="input111">Ship date *</label>
                </div>
                <?php
            }
            ?>
            </div>
            <div class="row">
            <?php
            // $ItemCount condition is used because if we have more than one item then dropdown is useful otherwise not.
            if (isset($AddressValidation->AddressResults->State) && $ItemsCount > 1){
                if ( $AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='NORMALIZED' ){
                    ?>
                    <div class="form-group m-b-40 col-md-4 fedex-package-type-form-group">
                        <select style="width: 100% !important;" class="form-control p-0 fedex-package-type" id="input111" required="">
                            <option></option>
                            <option value="single_package">Single Package</option>
                            <option value="seperate_package">Seperate Package</option>
                        </select>
                        <span class="bar"></span>
                        <label for="input111">Package Type *</label>
                    </div>
                    <?php
                }
            }
            ?>

            <?php
            // by default package weight field for only one single item
            if (  isset($AddressValidation->AddressResults->State) && $ItemsCount==1 ){
                if ( $AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='NORMALIZED' ){
                    ?>
                    <div class="form-group m-b-40 col-md-3">
                        <input min="1" type="number" value="1" style="width: 100% !important;" class="form-control fedex-single-item-package-weight" id="input100" required="">
                        <span class="bar"></span>
                        <label for="input3">Package Weight (LB)</label>
                    </div>

                    <div class="form-group m-b-40 col-md-3">
                        <input style="width: 100% !important;" type="number" class="form-control fedex-single-item-package-weight oz-weight-single-item"
                               min="0" value="12" id="input101" required="">
                        <span class="bar"></span>
                        <label for="input3">Package Weight - OZ</label>
                    </div>
                    <div class=" hide form-group m-b-40 col-md-4 fedex-package-type-form-group">
                        <select style="width: 100% !important;" class="form-control p-0 fedex-package-type" required="">
                            <option value="single_package" selected="">Single Package</option>
                        </select>
                        <label for="input111">Package Type *</label>
                    </div>
                    <?php
                }
            }else if ( isset($AddressValidation->AddressResults->State) && $ItemsCount>1 ){
                if ( $AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='NORMALIZED' ) {
                    ?>
                    <div class="form-group m-b-40 col-md-4 fedex-multiple-item-package-group" style="display: none;">
                        <input style="width: 100% !important;" type="number" class="form-control fedex-single-item-package-weight lbs-weight-single-item" min="0" value="0" id="input101" required="">
                        <span class="bar"></span>
                        <label for="input3">Package Weight - LB</label>
                    </div>
                    <div class="form-group m-b-40 col-md-4 fedex-multiple-item-package-group" style="display: none;">
                        <input style="width: 100% !important;" type="number" class="form-control fedex-single-item-package-weight oz-weight-single-item"
                               min="0" value="12" id="input101" required="">
                        <span class="bar"></span>
                        <label for="input3">Package Weight - OZ</label>
                    </div>
                    <?php
                }
            }

            if ( isset($AddressValidation->AddressResults->State) ){
                if ( $AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='NORMALIZED' ){
                    ?>
                    <div class="form-group m-b-40 col-md-4">
                        <button type="button" class="btn btn-warning get-shipping-estimate-cost-fedex">Click to Get Est Shipping Cost</button>
                    </div>
                    <?php
                }
            }
            ?>
            </div>
            <?php
            $customerValidation = 1;
            if ($customerInfo[0]['customer_number']==''){
                $customerValidation=0;
                ?>
                <div class="row">
                    <div class="col-md-12" style="text-align: center;">
                        <p style="color: red;">
                            Note: It looks like phone no is not available for this customer. Please update phone no in order to ship with FedEx.
                        </p>
                    </div>
                </div>
                <?php
            }
            ?>
            <input type="hidden" class="customer-validation" value="<?=$customerValidation?>">

        </form>
    </div>
<div class="api-responses" style="width: 100%;"></div>