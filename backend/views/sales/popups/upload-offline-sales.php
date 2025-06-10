<!-- Modal -->
<div id="offlineSalesImportModal" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Import Offline Sales</h4>
            </div>
            <div class="modal-body">
                <form action="offline-sales-import" method="post" enctype="multipart/form-data">
                    Shop : <select id="offline_shop_import_dropdown" required name="channel_id" class="form-control">
                        <option></option>
                        <option value="17">Celcom planet</option>
                        <option value="19">Lazada OutRight</option>
                    </select>

                    <div class="hide" id="po-number-div">
                        PO Number : <input type="text" name="po_number" class="form-control" placeholder="Enter the PO Number"/>
                        <span>
                            Enter the PO Number, You can find it from lazada supplier portal.
                        </span>
                    </div>
                    <br />
                    <br />
                    Upload CSV : <input required type="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" name="offline_sales" />
                    <br />
                    <br />
                    <p style="color: red;">
                        <b>Note :
                            <ul style="color: red;">


                                <li>
                                    All number column should not contain any comma, It should be like 23090.00
                                </li>
                                <li>
                                    Lazada ORB -> If you are importing lazada orb sales, Please put the PO Number in the invoice column of CSV.
                                </li>
                            </ul>
                            </b>
                    </p>
                    <input type="submit" class="form-control btn btn-success"/>
                </form>
            </div>
            <div class="modal-footer">
                <a href="/sample-files/offline_products.csv">Download Sample CSV</a>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>