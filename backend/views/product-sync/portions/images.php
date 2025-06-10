<h3> * Minimum Height/Width should be greater than 330X330</small></h3>
<br/>
<form id="pimages" action="/product-360/upload" class="floating-labels m-t-40 dropzone"  enctype="multipart/form-data" method="post">
    <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>"/>
    <input type="hidden" name="uqid" value="<?= $this->params['uniqId'];?>">
    <input type="hidden" name="sid" id="sid"  value="<?= isset($_GET['shop']) ? $_GET['shop'] : '' ?>">
    <!-- preload images -->
    <?php
    if ($images) {

        echo "<h4>Current Images</h4>";
        echo '<div class="row">';
        foreach ($images as $img) {
            if($img){
                echo '<div class="col-md-2">
                    <img  class="img-thumbnail img-responsive" src="/product_images/' . $img . '" width="60%" style="height: 70%;" alt="">
                    <small><a href="javascript:;" style="cursor: pointer" class="delete" data-img-id="' . $img . '">Delete</a></small>
                </div>';
            }
        }
        echo "</div>";
    }

    ?>

</form>