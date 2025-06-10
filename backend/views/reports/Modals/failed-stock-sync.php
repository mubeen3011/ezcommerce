<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/23/2018
 * Time: 5:09 PM
 */
?>
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title failed-stock-modal-header" id="myLargeModalLabel" ></h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
        <div class="modal-body">
            <h4>System is unable to update the stocks of these skus.</h4>
            <table class="nowrap table table-hover table-striped table-bordered failed-sku-stock-sync-list table">
                <thead>
                    <tr>
                        <th>Sku</th>
                        <th>Message</th>
                        <th>Detail Message</th>
                    </tr>
                </thead>
                <tbody id="failed-sku-sync-tbody">
                </tbody>
            </table>
        </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-danger waves-effect text-left" data-dismiss="modal">Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
