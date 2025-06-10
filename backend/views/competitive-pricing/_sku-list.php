<div class="form-group">
    <label class="control-label" for="userroles-skus">SKUs</label>
    <select id="userroles-skus" class="form-control" name="UserRoles[skus][]" multiple="" size="4">
        <?php foreach ($response as $c):
            $selected = (in_array($c['id'],$skus)) ? 'selected' : '';
            ?>
            <option <?=$selected?> value="<?= $c['id'] ?>"><?= $c['sku'] ?></option>
        <?php endforeach; ?>

    </select>

    <div class="help-block"></div>
</div>