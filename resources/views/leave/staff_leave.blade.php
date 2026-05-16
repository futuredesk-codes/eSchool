@extends('layouts.master')

@section('title')
    {{ __('leave') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('leave') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('staff') . ' ' . __('leaves') }}</h4>
                        {!! Form::hidden('holiday_days', $holiday_days ?? '', ['class' => 'form-control holiday_days']) !!}
                        {!! Form::hidden('public_holiday', $public_holiday ?? '', ['class' => 'form-control public_holiday']) !!}
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="" class="filter-menu">{{ __('session_year') }}</label>
                                <select name="session_year_id" class="form-control" id="filter_session_year_id">
                                    @foreach ($sessionYears as $sessionYear)
                                        <option value="{{ $sessionYear->id }}" {{ $sessionYear->id == $currentSessionYearId ? 'selected' : '' }}>{{ $sessionYear->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-sm-12 col-md-4">
                                <label for="" class="filter-menu">{{ __('filter') }}</label>
                                {!! Form::select('filter', ['All' => __('all'), 'Today' => __('today'), 'Tomorrow' => __('tomorrow'), 'Upcoming' => __('upcoming')], 'All', ['class' => 'form-control', 'id' => 'filter_upcoming']) !!}
                            </div>

                            <div class="form-group col-sm-12 col-md-4">
                                <label for="month" class="filter-menu">{{ __('month') }}</label>
                                {!! Form::select('month', $months, null, ['class' => 'form-control', ' id' => 'filter_month_id', 'placeholder' => __('all')]) !!}
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table" data-url="{{ route('staff-leave.show') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-show-export="true" data-export-options='{"fileName": "student-leave-request-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}' data-query-params="studentLeaveRequestQueryParams" data-escape="true">

                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="name">{{ __('name') }}</th>
                                    <th scope="col" data-field="from_date">{{ __('from_date') }}</th>
                                    <th scope="col" data-field="to_date">{{ __('to_date') }}</th>
                                    <th scope="col" data-visible="false" data-field="created_at">{{ __('created_at') }}</th>
                                    <th scope="col" data-visible="false" data-field="updated_at">{{ __('updated_at') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"> {{ __('view') . ' ' . __('leave') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fa fa-close"></i></span>
                        </button>
                    </div>
                    <form id="status-update" class="status-update" action="{{ url('student-leave-request-update') }}" novalidate="novalidate" method="Post">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="id" id="id">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="radio" class="form-check-input leave-status pending" name="status" id="optionsRadios2" value="0" checked=""> {{ __('pending') }} <i class="input-helper"></i></label>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="radio" class="form-check-input leave-status approved" name="status" id="optionsRadios2" value="1"> {{ __('approved') }} <i class="input-helper"></i></label>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="radio" class="form-check-input leave-status rejected" name="status" id="optionsRadios2" value="2"> {{ __('rejected') }} <i class="input-helper"></i></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                    <textarea name="reason" disabled id="edit_reason" class="form-control"></textarea>
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('from_date', null, ['required', 'class' => 'form-control datepicker-popup datepicker-popup-no-past', 'placeholder' => __('from_date'), 'id' => 'edit_from_date', 'readonly']) !!}
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('to_date', null, ['required', 'class' => 'form-control datepicker-popup datepicker-popup-no-past', 'placeholder' => __('to_date'), 'id' => 'edit_to_date', 'readonly']) !!}
                                </div>
                            </div>

                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('attachments') }} </label>
                                <div id="attachment"></div>
                            </div>

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12 edit_leave_dates mt-3">

                                </div>
                            </div>
                            <div class="row" style="display: none" id="rejected_reason_div">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('reason') . ' ' . __('to') . ' ' . __('reject') }}</label>
                                    <textarea name="reason" id="reject_reason" class="form-control" disabled></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript">
        function studentLeaveRequestQueryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                class_id: $('#filter_class_id').val(),
                session_year_id: $('#filter_session_year_id').val(),
                filter_upcoming: $('#filter_upcoming').val(),
                month_id: $('#filter_month_id').val(),


            };
        }
    </script>
@endsection
