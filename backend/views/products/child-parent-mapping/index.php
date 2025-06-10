<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 6/19/2020
 * Time: 2:52 PM
 */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Parent Child Products Mapping', 'url' => ['/products/child-parent-mapping']];
$this->params['breadcrumbs'][] = 'Parent Child Products Mapping';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3>Parent Child Products Mapping</h3>
            </div>
        </div>
    </div>
    <!--Negative margin skus-->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form class="form-horizontal form-bordered" method="post" action="/products/child-parent-mapping-save">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
                    <?php
                    if (isset(Yii::$app->session->getFlash)){
                        foreach(Yii::$app->session->getFlash as $key => $message) {
                            echo '<div class="flash-' . $key . '">' . $message . "</div>\n";
                        }

                    }

                    ?>
                    <div class="form-body">
                        <div class="form-group row">
                            <label class="control-label text-right col-md-3">Parent Sku</label>
                            <div class="col-md-9">
                                <select name="parent-skus" required class="select2" style="width: 100%">
                                    <?php
                                    foreach ($skus as $value){
                                        ?>
                                        <option value="<?=$value['id']?>"><?=$value['sku']?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-body">
                        <div class="form-group row">
                            <label class="control-label text-right col-md-3">Child Sku</label>
                            <div class="col-md-9">
                                <select name="child-skus[]" required class="select2 m-b-10 select2-multiple" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                    <?php
                                    foreach ($skus as $value){
                                        ?>
                                        <option value="<?=$value['id']?>"><?=$value['sku']?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="offset-sm-3 col-md-9">
                                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>