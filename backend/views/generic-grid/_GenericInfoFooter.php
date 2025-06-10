<?php
if ($page_no!='All') {
    ?>
    <div class="dataTables_info remove_when_pagination_used" id="example23_info" role="status" aria-live="polite">
    <?php
     $records_per_pages;
    $max_limit_showing = $page_no * $records_per_pages;
    $min_limit_showing = $max_limit_showing - $records_per_pages;
    $max_page = ceil($total_records / $records_per_pages);
    $page = $page_no;
    $reverse = $page - 1;
    if ($reverse < 1) {
        $reverse = 1;
    }
    $forward = $page + 1;
    if ($forward > $max_page) {
        $forward = $max_page;
    }
    ?>
    Showing <?= $min_limit_showing ?> to <?= $max_limit_showing ?>

        <label class="form-inline hidden-sm-down" style="float: right;display: block;  margin-left: 15px; margin-top:-3px;">

            <select id="record_per_page"  onchange="records_per_page()"  style="height: 25px;" class=" input-sm">
                <option <?php if($records_per_pages == '10') echo "selected" ?>  value="10">10</option>
                <option <?php if($records_per_pages == '50') echo "selected" ?>  value="50">50</option>
                <option <?php if($records_per_pages == '100') echo "selected" ?>  value="100">100</option>
                <option <?php if($records_per_pages == '200') echo "selected" ?> value="200">200</option>
                <option <?php if($records_per_pages == '500') echo "selected" ?>  value="500">500</option>
                <option <?php if($records_per_pages == '1000') echo "selected" ?> value="1000">1000</option>
            </select>

        </label>

        <br>
        <br>
        <p>Total Records : <?php echo $total_records; ?> </p>

    </div>
    <nav aria-label="..." class="remove_when_pagination_used">
        <ul class="pagination pull-right">
            <?php
            if ($page_no = 1) {
                ?>
                <li class="page-item paginate_button">
                    <a class="page-link" href="javascript:;" tabindex="-1" onclick="PageNo(<?= $page_no?>)">Start</a>
                </li>
                <?php
            }
            ?>
            <?php
/*            if ($page_no > 1) {
                */?><!--
                <li class="page-item paginate_button">
                    <a class="page-link" href="javascript:;" tabindex="-1" onclick="PageNo(<?/*= $page_no - 1 */?>)">Previous</a>
                </li>
                --><?php
/*            }
            */?>

            <?php

            $page_counter_no = $page;
            for ($i = $reverse; $i <= $forward; $i++) {

                ?>
                <li class="page-item <?php if ($page_counter_no == $i) {
                    echo 'active';
                } ?>">
                    <a class="page-link" href="javascript:;" onclick="PageNo(<?= $i ?>)"><?= $i ?></a>
                </li>
                <?php
            }

            ?>
            <?php
            /*if ($page_no != $max_page) {
                */?><!--

                <li class="page-item <?php /*if ($pagination_pages == $page_no) {
                    echo 'disabled';
                } */?>">
                    <a class="page-link" href="javascript:;" onclick="PageNo(<?/*= $page_no + 1 */?>)">Next</a>
                </li>
                --><?php
/*            }
            */?>
            <?php
            if ($page_no < $pagination_pages) {
                ?>

                <li class="page-item <?php if ($pagination_pages == $page_no) {
                    echo 'disabled';
                } ?>">
                    <a class="page-link" href="javascript:;" onclick="PageNo(<?= $pagination_pages ?>)">End</a>
                </li>
                <?php
            }
            ?>
        </ul>
    </nav>

    <?php
} ?>


