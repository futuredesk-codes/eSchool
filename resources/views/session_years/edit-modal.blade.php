<div>
    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ __('edit') . ' ' . __('session_years') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="editdata" class="editform" action="{{ url('session-years') }}" novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('name') }}</label>
                                <input type="text" name="name" placeholder="{{ __('name') }}"
                                    class = "form-control" id="name" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-6">
                                <label>{{ __('free_app_use_date') }} <span class="text-danger">*</span></label>
                                {!! Form::text('free_app_use_date', null, [
                                    'placeholder' => __('free_app_use_date'),
                                    'class' => 'datepicker-popup form-control',
                                    'id' => 'free_app_use_date',
                                ]) !!}
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_date" id="start_date"
                                    placeholder="{{ __('start_date') }}" class="datepicker-popup form-control" required>
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_date" placeholder="{{ __('end_date') }}"
                                    class="datepicker-popup form-control" id="end_date" required>
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('fees') }} {{ __('due_date') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="fees_due_date" class="datepicker-popup form-control"
                                    id="fees_due_date" placeholder="{{ __('fees') }} {{ __('due_date') }}"
                                    required>
                                </span>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('fees') }} {{ __('due_charges') }} <span class="text-danger">*</span>
                                    <span class="text-info small">( {{ __('in_percentage_%') }} )</span></label>
                                <input type="number" min="1" max="100" name="fees_due_charges"
                                    class="form-control" id="fees_due_charges"
                                    placeholder="{{ __('fees') }} {{ __('due_charges') }}" required>
                                </span>
                            </div>
                        </div>

                        <input type="hidden" name="edit_include_fee_installments" class="from-control"
                            id="edit_include_fee_installments">
                        <div class="row form-group installment-div" style="display:none">
                            <hr class="edit-installment-hr"
                                style='width:100%;margin-top: 1rem;margin-bottom: 1rem;border: 0;border-top: 1px solid rgba(0, 0, 0, 0.1);'>
                            <h5 class="card-title edit-installment-heading ml-3">
                                {{ __('edit') . ' ' . __('fees') . ' ' . __('installment') }}</h5>
                            <div class="edit-installment-container col-md-12 mt-4"></div>
                            <div class="form-group col-md-12 mt-4">
                                <button type="button" class="btn btn-inverse-success add-extra-fee-installment-data">
                                    <i class="fa fa-plus"></i> {{ __('add_new_data') }} </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="edit-installment-content-template" style="display: none">
        <input type="hidden" name="installment_data[0][id]" id="editInstallmentId_0" class="form-control">
        <div class="row">
            <div class="form-group col-md-4">
                <label>{{ __('installment') }} {{ __('name') }} <span class="text-danger">*</span></label>
                <input type="text" name="installment_data[0][name]" id="editInstallmentName_0"
                    class="form-control" placeholder="{{ __('installment') }} {{ __('name') }}" required>
            </div>
            <div class="form-group col-md-4">
                <label>{{ __('due_date') }} <span class="text-danger">*</span></label>
                <input type="text" name="installment_data[0][due_date]" id="editInstallmentDueDate_0"
                    class="datepicker-popup form-control" placeholder="{{ __('due_date') }}" required>
            </div>
            <div class="form-group col-md-3">
                <label>{{ __('due_charges') }} <span class="text-danger">*</span><span class="text-info small">(
                        {{ __('in_percentage_%') }} )</span></label>
                <input type="number" name="installment_data[0][due_charges]" id="editInstllmentDueCharges_0"
                    class="form-control" placeholder="{{ __('due_charges') }}" min="1" max="100"
                    required>
            </div>
            <div class="form-group col-md-1 pl-0 mt-4">
                <button type="button" class="btn btn-inverse-success btn-icon add-edit-fee-installment-content">
                    <i class="fa fa-plus"></i></button>
            </div>
        </div>
    </div>
</div>
