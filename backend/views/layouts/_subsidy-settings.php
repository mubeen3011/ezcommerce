<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 11/9/2017
 * Time: 3:42 PM
 */

use common\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$channelList = \backend\util\HelpUtil::getChannels();
$users = User::find()->select(['id', 'full_name'])->where(['role_id' => 2])->asArray()->all();
$usersList = ArrayHelper::map($users, 'id', 'full_name');
?>
<div class="form-group Columns required has-success">
    <label class="control-label" for="sku_id">Columns:</label>
    <br/>
    <div class="row">
        <?php foreach ($channelList as $cl): if ($cl['id'] == 1) continue; ?>
            <label for="default-<?= $cl['id'] ?>" style="margin: 2px"
                   class="btn btn-primary btn-small col-md-12"><?= $cl['name'] ?><input
                    type="checkbox" id="default-<?= $cl['id'] ?>"
                    name="<?= $cl['name'] ?>"
                    class="badgebox" data-class="chl-<?= $cl['name'] ?>" value="1"><span
                    class="badge">&check;</span></label><br/>
        <?php endforeach; ?>
    </div>

    <div class="help-block"></div>
</div>

