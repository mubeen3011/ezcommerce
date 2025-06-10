<div class="card">
    <div class="card-body">
        <div class="pull-right p-1">
            <a class="btn-sm btn btn-secondary comment-add-ez-com"><span class="fa fa-plus"> </span> Add new</a>
            <!--<button class="btn-sm btn btn-secondary"><span class="fa fa-trash"> </span> Delete Selected</button>-->
        </div>
        <div class="table-responsive">

            <!-------table------->
            <table id="myTable" class="display nowrap table table-hover table-striped table-bordered dataTable ez-com-comment-table" style=" width:100%;">
                <thead style="background-color: #ABB0B6;color:white">
                <tr>

                    <th >Name</th>
                    <th>Value</th>
                </tr>
                </thead>
                <tbody>
                <?php if(isset($fields['p360']['ez-com']['comments'])) {
                    foreach($fields['p360']['ez-com']['comments'] as $comment){ ?>
                        <tr>

                            <td>comment <br/>
                                <a class="fa fa-trash text-red comment-remove" data-toggle="tooltip" title="remove"></a>
                            </td>
                            <td><textarea name="p360[ez-com][comments][]" class="form-control form-control-sm"><?= $comment;?></textarea></td>

                        </tr>
                <?php }} else {  ?>

                <tr>

                    <td>comment<br/> <a class="fa fa-trash text-red comment-remove" data-toggle="tooltip" title="remove"></a></td>
                    <td><textarea name="p360[ez-com][comments][]" class="form-control form-control-sm"></textarea></td>

                </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>