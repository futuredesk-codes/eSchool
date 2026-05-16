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
            <a class="btn btn-sm btn-theme" href="{{ route('events.index') }}">{{ __('back') }}</a>
        </div>
        <div class="row">
            @can('event-list')
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                {{ __('list') . ' ' . __('schedule') }}
                            </h4>

                            <div class="row add-multiple-event-div" id="add-multiple-event-div"
                                style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 10px; display:none;">
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
                                    <button type="button" class="btn btn-icon btn-danger remove-multiple-event-div-edit">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <form id="edit-schedule" class="edit-event" action="{{ url('events-update') }}"
                                novalidate="novalidate" method="POST">
                                @csrf

                                <input type="hidden" name="event_id" value="{{ $event->id }}" class="form-control">
                                <input type="hidden" name="start_date" value="{{ $event->start_date }}" class="form-control">
                                <input type="hidden" name="end_date" value="{{ $event->end_date }}" class="form-control">

                                @foreach ($multievents as $key => $event)
                                    @php
                                        $date = date('d-m-Y', strtotime($event['date']));
                                        $timerange = $event['start_time'] . ' - ' . $event['end_time'];
                                    @endphp
                                    <div class="row mt-6"
                                        style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 10px;">
                                        <input type="hidden" name="edit_events[{{ $key }}][id]"
                                            value="{{ $event['id'] }}" class="form-control">

                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                            {!! Form::text("edit_events[$key][title]", $event['title'], [
                                                'required',
                                                'placeholder' => __('title'),
                                                'class' => 'form-control',
                                            ]) !!}
                                        </div>

                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                            {!! Form::text("edit_events[$key][date]", $date, [
                                                'required',
                                                'placeholder' => __('date'),
                                                'class' => 'form-control datepicker-popup',
                                            ]) !!}
                                            <span class="input-group-addon input-group-append"></span>
                                        </div>

                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('timing') }}</label>
                                            {!! Form::text("edit_events[$key][timerange]", $timerange, [
                                                'required',
                                                'placeholder' => __('start_time') . ' - ' . __('end_time'),
                                                'class' => 'timerange form-control',
                                            ]) !!}
                                            <span class="input-group-addon input-group-append"></span>
                                        </div>

                                        <div class="form-group col-sm-12 col-md-5">
                                            <label>{{ __('description') }}</label>
                                            {!! Form::textarea("edit_events[$key][description]", $event['description'], [
                                                'rows' => '3',
                                                'placeholder' => __('description'),
                                                'class' => 'form-control',
                                            ]) !!}
                                        </div>

                                        <div class="col-md-1 text-center pt-4">
                                            <button type="button" data-id="{{ $event['id'] }}"
                                                class="btn btn-icon btn-danger edit-remove-multiple-event-group">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                <div id="edit-extra-multiple-event"></div>

                                <div class="row edit-add-more" id="edit-add-more">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <button type="button" class="btn btn-success add-multiple-event-group-div"
                                            id="add-multiple-event-group-div">
                                            <i class="fa fa-plus"></i>&nbsp;&nbsp;{{ __('add_more') }}</button>
                                    </div>
                                </div>

                                <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection
@section('script')
    <script>
        document.getElementById('edit-event').addEventListener('submit', function(event) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            let isValid = true;
            document.querySelectorAll('input[name^="edit_events"][name$="[date]"]').forEach(function(dateField) {
                const eventDate = new Date(dateField.value);
                if (eventDate < startDate || eventDate > endDate) {
                    isValid = false;
                    alert(
                        `Date ${dateField.value} is not between ${startDate.toDateString()} and ${endDate.toDateString()}`
                    );
                }
            });

            if (!isValid) {
                event.preventDefault(); // Prevent form submission if any date is invalid
            }
        });
    </script>
@endsection
