<?php if(isset($services) && !empty($services)) { ?>
<div class="row">
        <div class="col-sm-12">
            <div class="input-group">
                <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">
                            <small class="mdi mdi-truck-delivery"> Select Service *</small>
                        </span>
                </div>
                <select class="form-control" id="usps_service_type" name="service">
                 <?php  foreach ($services as $service){  ?>
                     <option data-usps-service-name="<?= $service['name']; ?>" data-usps-service-amount="<?= $service['amount']; ?>"  value="<?= $service['code'];?>">
                            <?= $service['name']."&emsp; | Delivery days : " . $service['delivery_days'] . "&emsp; | Amount : " . $service['amount'] ;?>
                     </option>
                 <?php } ?>
                </select>
                <input type="hidden" id="usps-service-name-input" name="service_name" value="<?= $services[array_key_first($services)]['name'];?>">
                <input type="hidden" id="usps-service-amount-input" name="service_amount" value="<?= $services[array_key_first($services)]['amount'];?>">
            </div>
            <br>
        </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h5>Optional additional addons <small class="text-muted"> ( * addons selection may have additional charges)</small></h5>
    </div>
</div>
    <div class="row additional-addons-span-usps">
    <?php foreach($services[array_key_first($services)]['addons'] as $addon) { ?> <!---- selected only first service addon --->
        <div class="col-sm-3">
            <div class="checkbox checkbox-success">
                <input id="checkbox<?= $addon['code'];?>" type="checkbox" class="usps-addon-checkbox" data-usps-addon-code="<?= $addon['code'];?>" data-usps-addon-amount="<?= $addon['amount'];?>">
                <label for="checkbox<?= $addon['code'];?>"> <?= $addon['code'] ."( $ ". $addon['amount'] ." )" ;?> </label>
            </div>
        </div>
      <?php } ?>
    </div>
    <div class="row additional-addons-span-usps-inputs"> <!--- for form to convert checkboxes into input saving amount and code--->
    </div>
<?php } ?>
<?php if(isset($error) && !empty($error)) { ?>
<div class="col-sm-12">
    <?= $error; ?>
</div>
<?php } ?>
<input type="hidden" value="<?= $courier->id ?>" name="courier">