@extends('layouts.master')

@section('title')
    {{ __('leave') . ' ' . __('settings') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('leave') . ' ' . __('settings') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form id="frmData" class="general-setting" action="{{ route('leave-master.store') }}"
                            novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4 col-sm-12">
                                    <label>{{ __('total_leave_per_month') }}<span class="text-danger">*</span></label>
                                    <input name="total_leave" type="number" required
                                        placeholder="{{ __('total_leave_per_month') }}" class="form-control" />
                                </div>
                                <div class="form-group col-md-4 col-sm-12">
                                    <label>{{ __('holiday_days') }}<span class="text-danger">*</span></label>
                                    <select name="holiday_days[]"
                                        class="form-control js-example-basic-single select2-hidden-accessible" multiple>
                                        <option value="Sunday">{{ __('Sunday') }}</option>
                                        <option value="Monday">{{ __('Monday') }}</option>
                                        <option value="Tuesday">{{ __('Tuesday') }}</option>
                                        <option value="Wednesday">{{ __('Wednesday') }}</option>
                                        <option value="Thursday">{{ __('Thursday') }}</option>
                                        <option value="Friday">{{ __('Friday') }}</option>
                                        <option value="Saturday">{{ __('Saturday') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4 col-sm-12">
                                    <label>{{ __('session_year') }}<span class="text-danger">*</span></label>
                                    <select name="session_year_id" class="form-control">
                                        <option value="">{{ __('Please Select') }}</option>
                                        @foreach ($session_year as $data)
                                            <option value="{{ $data->id }}">{{ $data->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <input class="btn btn-theme" type="submit" value="{{ __('Submit') }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('leave') . ' ' . __('settings') }}
                        </h4>
                        <div class="row align-items-end">
                            <div class="col-md-2">
                                <label for="filter_session_year" class="form-label text-nowrap">
                                    {{ __('session_year') }}
                                </label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_year as $year)
                                        <option value="{{ $year->id }}">
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('leave-master.show', 1) }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                            data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                            data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                            data-query-params="leaveSettingsQueryParams" data-show-export="true"
                            data-export-options='{"fileName": "stream-list-<?= date('d-m-y') ?>","ignoreColumn":
                            ["operate"]}' data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="total_leave" data-sortable="false">
                                        {{ __('total_leave') }}</th>
                                    <th scope="col" data-field="holiday_days" data-sortable="false"
                                        data-formatter="holidayDaysFormatter">{{ __('holiday_days') }}</th>
                                    <th scope="col" data-field="session_year_id" data-visible="false"
                                        data-sortable="false">{{ __('session_year_id') }}</th>
                                    <th scope="col" data-field="session_year" data-sortable="false">
                                        {{ __('session_year') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true"
                                        data-visible="false">{{ __('updated_at') }}</th>
                                    <th scope="col" data-escape="false" data-field="operate" data-sortable="false"
                                        data-events="leavesSettingEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('leave') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 leave-setting" id="leave-setting"
                            action="{{ route('leave-master.update', 1) }}" novalidate="novalidate">
                            @csrf
                            <input type="hidden" name="edit_id" id="edit_id" value="" />
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('total_leave_per_month') }}<span class="text-danger">*</span></label>
                                        <input name="total_leave" id="total_leave" type="number" required
                                            placeholder="{{ __('total_leave_per_month') }}" class="form-control" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('holiday_days') }}<span class="text-danger">*</span></label>
                                        <select name="holiday_days[]" id="holiday_days"
                                            class="form-control js-example-basic-single select2-hidden-accessible"
                                            multiple>
                                            <option value="Sunday">{{ __('Sunday') }}</option>
                                            <option value="Monday">{{ __('Monday') }}</option>
                                            <option value="Tuesday">{{ __('Tuesday') }}</option>
                                            <option value="Wednesday">{{ __('Wednesday') }}</option>
                                            <option value="Thursday">{{ __('Thursday') }}</option>
                                            <option value="Friday">{{ __('Friday') }}</option>
                                            <option value="Saturday">{{ __('Saturday') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('session_year') }}<span class="text-danger">*</span></label>
                                        <select name="session_year_id" id="session_year" class="form-control">
                                            <option value="">{{ __('Please Select') }}</option>
                                            @foreach ($session_year as $data)
                                                <option value="{{ $data->id }}">{{ $data->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('edit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript">
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
@endsection
