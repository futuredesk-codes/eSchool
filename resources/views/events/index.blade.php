@extends('layouts.master')

@section('title')
    {{ __('events') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('events') }}
            </h3>
        </div>
        <div class="row">
            @if (Auth::user()->can('event-create'))
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('create') . ' ' . __('events') }}
                            </h4>
                            <form class="event-form pt-3" id="event-form" action="{{ route('events.store') }}" method="POST"
                                novalidate="novalidate">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('title', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control']) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('description') }}</label>
                                        {!! Form::textarea('description', null, [
                                            'rows' => '3',
                                            'placeholder' => __('description'),
                                            'class' => 'form-control',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label for="session_year_id">{{ __('session_year') }} <span
                                                class="text-danger">*</span></label>
                                        <select required name="session_year_id" id="session_year_id"
                                            class="form-control session_year_id">
                                            <option value="">{{ __('select') . ' ' . __('session_years') }}</option>
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('image') }}</label>
                                        <input type="file" name="image" class="file-upload-default" accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" disabled=""
                                                placeholder="{{ __('image') }}" required="required" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="event_type" value="single" required
                                                        id="single" class="form-check-input event_type">
                                                    {{ __('single') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="event_type" value="multiple" required
                                                        id="multiple" class="form-check-input event_type">
                                                    {{ __('multiple') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Single Events Div --}}
                                <div class="row" id="single-div" style="display:none;">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('date', null, [
                                            'required',
                                            'placeholder' => __('date'),
                                            'class' => 'datepicker-popup form-control',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('timing') }}</label>
                                        {!! Form::text('time', null, [
                                            'placeholder' => __('start_time') . ' - ' . __('end_time'),
                                            'class' => 'timerange form-control',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                </div>
                                {{-- Select date range for multiple events --}}
                                <div class="row date-range-div" id="date-range-div" style="display:none;">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('date') . ' ' . __('range') }} <span
                                                class="text-danger">*</span></label>
                                        {!! Form::text('date_range', null, [
                                            'required',
                                            'id' => 'date_range',
                                            'placeholder' => __('date'),
                                            'class' => 'daterange form-control',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                </div>
                                {{-- Multiple Events Div --}}
                                <div class="row add-multiple-event-div" id="add-multiple-event-div" style="display:none;">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('events[0][title]', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control']) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('events[0][date]', null, [
                                            'required',
                                            'placeholder' => __('date'),
                                            'class' => 'datepicker-popup form-control',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('timing') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('events[0][timerange]', null, [
                                            'required',
                                            'placeholder' => __('start_time') . ' - ' . __('end_time'),
                                            'class' => 'timerange form-control',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-5">
                                        <label>{{ __('description') }}</label>
                                        {!! Form::textarea('events[0][description]', null, [
                                            'rows' => '3',
                                            'placeholder' => __('description'),
                                            'class' => 'form-control',
                                        ]) !!}
                                    </div>
                                    <div class="col-md-1 text-center pt-4">
                                        <button type="button" class="btn btn-icon btn-danger remove-multiple-event-div"
                                            disabled>
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row" id="add-more" style="display:none">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <button type="button" class="btn btn-success add-multi-div" id="add-multi-div">
                                            <i class="fa fa-plus"></i>&nbsp;&nbsp;{{ __('add_more') }}</button>
                                    </div>
                                </div>

                                <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @can('event-list')
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('events') }}
                            </h4>
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="filter_session_year" class="form-label text-nowrap">
                                        {{ __('session_year') }}
                                    </label>
                                    <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                        class="form-control filter_session_year">
                                        @foreach ($session_years as $session_year)
                                            <option value="{{ $session_year->id }}">
                                                {{ $session_year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <table aria-describedby="mydesc" id='table_list' class="table" data-toggle="table"
                                        data-url="{{ url('events/show') }}" data-click-to-select="true"
                                        data-side-pagination="server" data-pagination="true"
                                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                                        data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                        data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                        data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                        data-export-types='["txt","excel"]' data-query-params="queryParams"
                                        data-export-options='{ "fileName": "event-list-<?= date('d-m-y') ?>" ,"ignoreColumn":
                                        ["operate"]}' data-escape="true">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable="true" data-visible="false">
                                                    {{ __('id') }}
                                                </th>
                                                <th data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                                <th data-field="title" data-sortable="false">{{ __('title') }}</th>
                                                <th data-field="type" data-sortable="false">{{ __('type') }}</th>
                                                <th data-field="date" data-sortable="false">{{ __('date') }}</th>
                                                <th data-field="time" data-sortable="false">{{ __('timing') }}</th>
                                                <th data-field="description" data-sortable="false"
                                                    data-formatter="eventDescriptionFormatter">{{ __('description') }}</th>
                                                <th data-field="image" data-formatter="imageFormatter" data-sortable="false">
                                                    {{ __('image') }}</th>
                                                <th data-events="actionEvents" data-escape="false"
                                                    data-field="operate"data-sortable="false">{{ __('action') }}</th>

                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('events') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="edit-event" class="edit-event" action="{{ url('events/update') }}"
                            novalidate="novalidate">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="edit_id" id="edit_id">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('title', null, [
                                            'required',
                                            'placeholder' => __('title'),
                                            'class' => 'form-control',
                                            'id' => 'title',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('description') }}</label>
                                        {!! Form::textarea('description', null, [
                                            'rows' => '3',
                                            'placeholder' => __('description'),
                                            'class' => 'form-control',
                                            'id' => 'description',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('image') }}</label>
                                        <input type="file" name="image" class="file-upload-default"
                                            accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" disabled=""
                                                placeholder="{{ __('image') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <div style="width: 100px; margin-top: 10px">
                                            <img src="" id="edit-event-image" class="img-fluid w-100" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="edit_event_type" value="single" required
                                                        id="edit_single" class="form-check-input edit_event_type">
                                                    {{ __('single') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="edit_event_type" value="multiple"
                                                        required id="edit_multiple"
                                                        class="form-check-input edit_event_type">
                                                    {{ __('multiple') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="edit-single-div" style="display:none;">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('edit_date', null, [
                                            'required',
                                            'placeholder' => __('date'),
                                            'class' => 'datepicker-popup form-control',
                                            'id' => 'date',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('timing') }}</label>
                                        {!! Form::text('edit_time', null, [
                                            'placeholder' => __('start_time') . ' - ' . __('end_time'),
                                            'class' => 'timerange form-control',
                                            'id' => 'time',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                </div>
                                <div class="row edit-date-range-div" id="edit-date-range-div" style="display:none;">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('date') . ' ' . __('range') }} <span
                                                class="text-danger">*</span></label>
                                        {!! Form::text('date_range', null, [
                                            'required',
                                            'placeholder' => __('date'),
                                            'class' => 'daterange form-control',
                                            'id' => 'daterange',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
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
        // Session year date ranges for date validation
        var sessionYearDates = {};
        @foreach ($session_years as $years)
            sessionYearDates[{{ $years->id }}] = {
                start: '{{ \Carbon\Carbon::parse($years->start_date)->format("d-m-Y") }}',
                end: '{{ \Carbon\Carbon::parse($years->end_date)->format("d-m-Y") }}'
            };
        @endforeach

        function initConstrainedDatepicker(element, startDate, endDate) {
            var $el = $(element);
            $el.datepicker('destroy').removeClass('hasDatepicker');
            $el.datepicker({
                enableOnReadonly: false,
                todayHighlight: true,
                format: "dd-mm-yyyy",
                autoclose: true,
                startDate: startDate,
                endDate: endDate,
            });
            // Prevent custom.js from re-initializing without constraints
            $el.addClass('hasDatepicker');
        }

        function initConstrainedDaterange(element, startDate, endDate) {
            var $el = $(element);
            var parts = startDate.split('-');
            var minDateObj = new Date(parts[2], parts[1] - 1, parts[0]);
            parts = endDate.split('-');
            var maxDateObj = new Date(parts[2], parts[1] - 1, parts[0]);

            if ($el.data('daterangepicker')) {
                $el.data('daterangepicker').remove();
            }
            $el.val('');
            $el.daterangepicker({
                opens: 'right',
                autoUpdateInput: false,
                minDate: minDateObj,
                maxDate: maxDateObj,
            }).on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
            }).on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });
        }

        function updateCreateFormDateConstraints() {
            var sessionYearId = $('#session_year_id').val();
            if (!sessionYearId || !sessionYearDates[sessionYearId]) {
                return;
            }
            var startDate = sessionYearDates[sessionYearId].start;
            var endDate = sessionYearDates[sessionYearId].end;

            // Update single event datepicker
            $('#single-div .datepicker-popup').each(function () {
                initConstrainedDatepicker(this, startDate, endDate);
                $(this).val('');
            });

            // Update multiple event datepickers
            $('#event-form .add-multiple-event-div .datepicker-popup').each(function () {
                initConstrainedDatepicker(this, startDate, endDate);
                $(this).val('');
            });

            // Update daterange picker
            $('#date-range-div .daterange').each(function () {
                initConstrainedDaterange(this, startDate, endDate);
            });
        }

        function updateEditFormDateConstraints(sessionYearId) {
            if (!sessionYearId || !sessionYearDates[sessionYearId]) {
                return;
            }
            var startDate = sessionYearDates[sessionYearId].start;
            var endDate = sessionYearDates[sessionYearId].end;

            // Update edit single event datepicker
            $('#edit-single-div .datepicker-popup').each(function () {
                initConstrainedDatepicker(this, startDate, endDate);
            });

            // Update edit daterange picker
            $('#edit-date-range-div .daterange').each(function () {
                initConstrainedDaterange(this, startDate, endDate);
            });
        }

        $(document).ready(function () {
            $('#session_year_id').on('change', function () {
                updateCreateFormDateConstraints();
            });

            // Re-apply constraints when event type changes
            $('.event_type').on('change', function () {
                updateCreateFormDateConstraints();
            });

            // Handle dynamically added datepickers (cloned via "Add More")
            $(document).on('focus', '#event-form .add-multiple-event-div .datepicker-popup', function () {
                var sessionYearId = $('#session_year_id').val();
                if (sessionYearId && sessionYearDates[sessionYearId] && !$(this).data('datepicker')) {
                    initConstrainedDatepicker(this, sessionYearDates[sessionYearId].start, sessionYearDates[sessionYearId].end);
                }
            });
        });
    </script>

    <script type="text/javascript">
        window.actionEvents = {

            'click .edit-data': function(e, value, row, index) {
                $('#edit_id').val('');
                $('#title').val('');
                $('#description').val('');
                $('#edit-event-image').attr('src', '');
                $('#daterange').val('');
                $('#edit-extra-multiple-event').empty();

                $('#edit_id').val(row.id);
                $('#title').val(row.title);
                $('#description').val(row.description);
                $('#edit-event-image').attr('src', row.image);


                $('input[name="edit_event_type"][value="' + row.type + '"].edit_event_type').prop('checked', true);


                // Apply session year date constraints to edit form
                updateEditFormDateConstraints(row.session_year_id);

                if (row.type == 'multiple') {

                    $('#edit-single-div').hide();
                    $('.edit-add-more').show();
                    $('#edit-date-range-div').show();

                    var DateRange = convertDateRange(row.date);

                    $('#daterange').val(DateRange);

                } else {
                    $('#edit-multi-div').hide();
                    $('#edit-date-range-div').hide();
                    $('#edit-multiple-event-group-div').hide();
                    $('.edit-add-more').hide();
                    $('#edit-single-div').show();

                    if (row.time) {
                        var timerange = convertTimeRange(row.time);
                        $('#time').val(timerange);
                        initializeTimerangePicker();
                    }
                    $('#date').val(row.date);

                }
            }
        };
    </script>

    <script type="text/javascript">
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                session_year_id: $('#filter_session_year').val(),
            };
        }
    </script>
@endsection
