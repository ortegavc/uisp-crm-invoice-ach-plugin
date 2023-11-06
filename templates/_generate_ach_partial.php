<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-4">
                    <h3>Invoices found: <?php echo $invoicesCount; ?></h3>
                </div>
                <div class="d-flex justify-content-between">
                    <div style="flex: 2;" class="mr-3">
                        <fieldset class="border p-2">
                            <legend  class="w-auto">ACH Generation</legend>

                            <form>
                                <input
                                    type="hidden"
                                    name="organization"
                                    value="<?php echo $parameters['organizationId']; ?>"
                                />
                                <input
                                    type="hidden"
                                    name="since"
                                    value="<?php echo $parameters['createdDateFrom']; ?>"
                                />
                                <input
                                    type="hidden"
                                    name="until"
                                    value="<?php echo $parameters['createdDateTo']; ?>"
                                />

                                <div class="form-group">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="originatingDFiId"
                                        aria-describedby="originatingDFiIdHelp"
                                        placeholder="Immediate Destination"
                                        minlength="8"
                                        maxlength="10"
                                        required
                                    />
                                    <small id="originatingDFiIdHelp" class="form-text text-muted">
                                        Enter Your Organization Bank Routing Number
                                    </small>
                                </div>
                                <div class="form-group">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="immediateDestinationName"
                                        aria-describedby="immediateDestinationNameHelp"
                                        placeholder="Immediate Destination Name"
                                        required
                                    />
                                    <small id="immediateDestinationNameHelp" class="form-text text-muted">
                                        Enter Your Organization Bank Name
                                    </small>
                                </div>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    name="cmd"
                                    value="export_to_ach"
                                >Generate ACH</button>
                            </form>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>