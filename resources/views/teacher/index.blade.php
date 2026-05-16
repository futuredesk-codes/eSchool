@extends('layouts.master')

@section('title')
    {{ __('teacher') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_teacher') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create_teacher') }}
                        </h4>
                        <form class="create-form pt-3" id="formdata" action="{{ url('teachers') }}" enctype="multipart/form-data" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('first_name', null, ['required' => true, 'placeholder' => __('first_name'), 'class' => 'form-control']) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('last_name', null, ['required' => true, 'placeholder' => __('last_name'), 'class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'male', false, ['required' => true]) !!}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'female', false, ['required' => true]) !!}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('email') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('email', null, ['required' => true, 'placeholder' => __('email'), 'class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('mobile') }} <span class="text-danger">*</span></label>
                                    {!! Form::number('mobile', null, ['required' => true, 'placeholder' => __('mobile'), 'min' => 1, 'class' => 'form-control']) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-6">

                                    <label>{{ __('image') }}</label>
                                    <input type="file" name="image" class="file-upload-default" accept="image/png,image/jpeg,image/jpg" />
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" />
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('dob', null, ['required' => true, 'placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('qualification') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('qualification', null, ['required' => true, 'placeholder' => __('qualification'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('current_address', null, ['required' => true, 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('permanent_address', null, ['required' => true, 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>
                            <div class="row">
                                @foreach ($teacherFields as $row)
                                    @if ($row->type === 'text' || $row->type === 'number')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                            <input type="{{ $row->type }}" name="{{ $row->name }}" placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}" class="form-control" {{ $row->is_required === 1 ? 'required' : '' }}>
                                        </div>
                                    @endif
                                    @if ($row->type === 'dropdown')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                            <select name="{{ $row->name }}" class="form-control" {{ $row->is_required === 1 ? 'required' : '' }}>
                                                <option value="">Please Select</option>
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <option value="{{ $options }}">{{ ucfirst($options) }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    @if ($row->type === 'radio')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                            <br>
                                            <div class="d-flex">
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <div class="form-check form-check-inline">
                                                            <label class="form-check-label">
                                                                <input type="radio" name="{{ $row->name }}" value="{{ $options }}" {{ $row->is_required === 1 ? 'required' : '' }}>
                                                                {{ ucfirst($options) }}
                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($row->type === 'checkbox')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label>
                                            <br>
                                            <div class="col-md-10" id="{{ $row->name }}">
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <div class="checkbox form-check form-check-inline" {{ $row->is_required === 1 ? 'required' : '' }}>
                                                            <label class="form-check-label">
                                                                <input type="checkbox" name="{{ 'checkbox[' . $row->name . '][' . $options . ']' }}" value="{{ $options }}"> {{ ucfirst(str_replace('_', ' ', $options)) }}
                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                        </div>
                                    @endif
                                    @if ($row->type === 'textarea')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                            <textarea name="{{ $row->name }}" cols="10" rows="3" placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}" class="form-control" {{ $row->is_required === 1 ? 'required' : '' }}></textarea>
                                        </div>
                                    @endif
                                    @if ($row->type === 'file')
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label>
                                            <input type="file" name="{{ $row->name }}" class="file-upload-default" {{ $row->is_required === 1 ? 'required' : '' }} />
                                            <div class="input-group col-xs-12">
                                                <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}" required />
                                                <span class="input-group-append">
                                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="grant_permission" aria-label="Checkbox for following text input" id="gridCheck">
                                    </div>
                                </div>
                                <label class="form-control" for="gridCheck">
                                    {{ __('grant_permission_to_manage_students_parents') }}
                                </label>
                            </div>
                            <div class="form-group text-info" style="font-size: 0.8rem;margin-top: -0.3rem">{{ __('note_for_permission_of_student_manage') }}</div>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        @endsection
