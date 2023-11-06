<div class="row mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Generate ACH file</h5>
                <form id="export-form">
                    <div class="form-row align-items-end">
                        <div class="col-3">
                            <label class="mb-0" for="frm-organization"><small>Organization:</small></label>
                            <select name="organization" id="frm-organization" class="form-control form-control-sm">
                                <?php
                                foreach ($organizations as $organization) {
                                    $html = '<option value="%d">%s</option>';

                                    if (!empty($parameters['organizationId']) and
                                        $organization['id'] == $parameters['organizationId']) {
                                        $html = '<option selected value="%d">%s</option>';
                                    }

                                    printf(
                                        $html,
                                        $organization['id'],
                                        htmlspecialchars($organization['name'], ENT_QUOTES)
                                    );
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-3">
                            <label class="mb-0" for="frm-since"><small>Since:</small></label>
                            <input
                                type="date"
                                name="since"
                                id="frm-since"
                                placeholder="YYYY-MM-DD"
                                class="form-control form-control-sm"
                                value="<?php echo isset($parameters['createdDateFrom']) ? $parameters['createdDateFrom'] : ''; ?>"
                            />
                        </div>

                        <div class="col-3">
                            <label class="mb-0" for="frm-until"><small>Until:</small></label>
                            <input
                                type="date"
                                name="until"
                                id="frm-until"
                                placeholder="YYYY-MM-DD"
                                class="form-control form-control-sm"
                                value="<?php echo isset($parameters['createdDateTo']) ? $parameters['createdDateTo'] : ''; ?>"
                            />
                        </div>

                        <div class="col-3">
                            <button type="submit" name="cmd" value="select_invoices" class="btn btn-primary btn-sm pl-4 pr-4">Select invoices</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
