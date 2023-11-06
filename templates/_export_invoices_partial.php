<div class="row mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Update invoices</h5>

                <div class="form-row">
                    <div class="col-6">
                        <form onsubmit="return confirm('Do you really want to update invoices?');">
                    <?php
                    if (count($fileStorage) == 0) {
                        echo 'No saved invoices';
                    } else {
                        ?>
                        <button
                            id="submit-update-invoices-btn"
                            type="submit"
                            name="cmd"
                            disabled
                            value="update_invoices"
                            class="btn btn-primary btn-sm pl-4 pr-4 mb-3">Update invoices</button>
                        <?php
                    }

                    foreach ($fileStorage as $index => $invoiceRow) {
                        ?>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   onchange="document.getElementById('submit-update-invoices-btn').disabled = false;"
                                   name="invoicesRowId"
                                   id="radio_<?php echo $index;?>"
                                   value="<?php echo $invoiceRow['id'];?>">
                            <label
                                class="form-check-label"
                                style="text-decoration: underline;font-size: 16px;color: black;"
                                for="radio_<?php echo $index;?>">
                                <?php
                                echo $invoiceRow['date'] . '&nbsp;&nbsp;&nbsp;invoices count: ' . count($invoiceRow['invoices']);
                                ?>
                            </label>
                        </div>
                        <?php
                    }
                    ?>
                </form>
                    </div>
                    <div class="col-2" style="padding-top: 46px;">
                        <form>
                            <button
                                type="submit"
                                name="cmd"
                                value="export_json_file"
                                class="btn btn-success btn-sm pl-4 pr-4 mb-3">Export</button>
                        </form>
                    </div>
                    <div class="col-4">
                        <form enctype="multipart/form-data" method="POST">
                            <div>
                                <input
                                    name="userfile"
                                    type="file"
                                    accept=".json"
                                    class="mb-3"
                                />

                                <button
                                        type="submit"
                                        name="cmd"
                                        value="import_json_file"
                                        class="btn btn-success btn-sm pl-4 pr-4 mb-3">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
