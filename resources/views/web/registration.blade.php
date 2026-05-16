@extends('web.master')

<!-- navbar ends here  -->
@section('title')
    {{ __('registration') }}
@endsection
@section('content')
    <div class="main">
        <div class="breadcrumb">
            <div class="container">
                <div class="contentWrapper">

                    @if ($registration)
                        <span class="title"> {{ __('registration') }}</span>
                        <span>
                            <span class="home"><a href="{{ url('/') }}">{{ __('home') }}</a></span>
                            <span><i class="fa-solid fa-angles-right"></i></span>
                            <span class="page">{{ __('registration') }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @if ($registration)
            <section class="registration commonMT container">
                <div class="row">
                    <div class="col-12">
                        <div class="flex_column_center">
                            <span class="commonTag">
                                {{ isset($registration->tag) ? $registration->tag : 'Our Photos' }}
                            </span>
                            <span class="commonDesc">
                                {{ isset($registration->heading) ? $registration->heading : 'Please complete the form below to enroll in our programs at school. Provide accurate and detailed information to help us process your registration efficiently and ensure you receive the best.' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="getInTouchWrapper">
                                <div class="card">
                                    <div class="content">
                                        <span class="commonSpan">{{ isset($registration->tag) ? $registration->tag : 'Student Registration Form' }}</span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="formWrapper">
                                <form class="pt-3 student-registration-form1" id="student-registration-form1"
                                    enctype="multipart/form-data" action="{{ route('student-registration-store') }}"
                                    method="POST" novalidate="novalidate">

                                    @csrf
                                    <div class="row">
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('first_name', null, ['placeholder' => __('first_name'), 'class' => 'form-control', 'required']) !!}
                                        </div>
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('last_name', null, ['placeholder' => __('last_name'), 'class' => 'form-control', 'required']) !!}

                                        </div>
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('mobile') }}</label>
                                            {!! Form::number('mobile', null, [
                                                'placeholder' => __('mobile'),
                                                'class' => 'form-control mobile',
                                                'min' => 7,
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                            <div class="radio-group mt-2">
                                                <label class="radio-label">
                                                    {!! Form::radio('gender', 'male', null, [
                                                        'required',
                                                        'class' => 'radio-input'
                                                    ]) !!}
                                                    <span class="radio-custom"></span>
                                                    <span class="radio-text">{{ __('male') }}</span>
                                                </label>

                                                <label class="radio-label">
                                                    {!! Form::radio('gender', 'female', null, [
                                                        'required',
                                                        'class' => 'radio-input'
                                                    ]) !!}
                                                    <span class="radio-custom"></span>
                                                    <span class="radio-text">{{ __('female') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('image') }} <span class="text-danger">*</span></label>
                                            <input type="file" name="image" class="file-upload-default"
                                                accept="image/*" />
                                            <div class="input-group col-xs-12">
                                                <input type="text" class="form-control file-upload-info" disabled=""
                                                    placeholder="{{ __('image') }}" required />
                                                <span class="input-group-append">
                                                    <button class="file-upload-browse btn btn-theme"
                                                        type="button">{{ __('upload') }}</button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('dob', null, [
                                                'placeholder' => __('dob'),
                                                'class' => 'datepicker-popup-no-future form-control',
                                                'required',
                                            ]) !!}
                                            <span class="input-group-addon input-group-append">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                            <select name="class_id" id="class_id" class="form-control select2" required>
                                                <option value="">
                                                    {{ __('select') . ' ' . __('class') }}</option>
                                                @foreach ($classSchools as $classSchool)
                                                    <option value="{{ $classSchool->id }}">{{ $classSchool->name }} -
                                                        {{ $classSchool->medium->name }}
                                                        {{ $classSchool->streams->name ?? ' ' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label> {{ __('category') }} <span class="text-danger">*</span></label>
                                            <select name="category_id" class="form-control">
                                                <option value="">{{ __('select') . ' ' . __('category') }}</option>
                                                @foreach ($category as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('gr_number') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('admission_no', $admission_no, [
                                                'readonly' => true,
                                                'placeholder' => __('gr_number'),
                                                'class' => 'form-control',
                                                'required',
                                            ]) !!}
                                        </div>
                                        <div class="form-group col-sm-12 col-md-6">
                                            <label>{{ __('admission_date') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('admission_date', $admission_date, [
                                                'placeholder' => __('admission_date'),
                                                'class' => 'datepicker-popup-no-future form-control',
                                                'required',
                                                'readonly',
                                            ]) !!}
                                            <span class="input-group-addon input-group-append">
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('current_address', null, [
                                                'placeholder' => __('current_address'),
                                                'class' => 'form-control',
                                                'id' => 'current_address',
                                                'rows' => 2,
                                                'required',
                                            ]) !!}
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('permanent_address', null, [
                                                'placeholder' => __('permanent_address'),
                                                'class' => 'form-control',
                                                'id' => 'permanent_address',
                                                'rows' => 2,
                                                'required',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="row">
                                        @foreach ($studentFields as $row)
                                            @if ($row->type === 'text' || $row->type === 'number')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }}
                                                        {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                                    <input type="{{ $row->type }}" name="{{ $row->name }}"
                                                        placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}"
                                                        class="form-control"
                                                        {{ $row->is_required === 1 ? 'required' : '' }}>
                                                </div>
                                            @endif
                                            @if ($row->type === 'dropdown')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }}
                                                        {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                                    <select name="{{ $row->name }}" class="form-control"
                                                        {{ $row->is_required === 1 ? 'required' : '' }}>
                                                        <option value="">Please Select</option>
                                                        @foreach (json_decode($row->default_values) as $options)
                                                            @if ($options != null)
                                                                <option value="{{ $options }}">
                                                                    {{ ucfirst(str_replace('_', ' ', $options)) }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                            {{-- @if ($row->type === 'radio')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }}{!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                                    <br>
                                                    <div class="d-flex">
                                                        @foreach (json_decode($row->default_values) as $options)
                                                            @if ($options != null)
                                                                <div class="form-check form-check-inline">
                                                                    <label class="form-check-label">
                                                                        <input type="radio" name="{{ $row->name }}" value="{{ $options }}"{{ $row->is_required === 1 ? 'required' : '' }}>{{ ucfirst($options) }}
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif --}}
                                            @if ($row->type === 'radio')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>
                                                        {{ ucwords(str_replace('_', ' ', $row->name)) }}
                                                        {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}
                                                    </label>
                                                    <br>
                                                    <div class="radio-group mt-2">
                                                        @foreach (json_decode($row->default_values) as $options)
                                                            @if ($options != null)
                                                                <label class="radio-label">
                                                                    <input type="radio" name="{{ $row->name }}" value="{{ $options }}" class="radio-input" {{ $row->is_required === 1 ? 'required' : '' }}>
                                                                    <span class="radio-custom"></span>
                                                                    <span class="radio-text">{{ ucfirst($options) }}</span>
                                                                </label>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if($row->type==="checkbox")
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }} {!! ($row->is_required) ? ' <span class="text-danger">*</span></label>': '' !!}</label>
                                                    <br>
                                                    <div class="col-md-10" id="{{$row->name}}" >
                                                        @foreach(json_decode($row->default_values) as $options)
                                                            @if($options != null)
                                                                <div class="checkbox form-check form-check-inline">
                                                                    <label class="form-check-label">
                                                                        <input type="checkbox" class="form-check-input" name="{{ 'checkbox[' . $row->name . '][]' }}" value="{{$options}}" {{($row->is_required===1)?"required":''}}> {{ ucfirst(str_replace('_', ' ', $options)) }}
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($row->type === 'textarea')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }}
                                                        {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label></label>
                                                    <textarea name="{{ $row->name }}" cols="10" rows="3"
                                                        placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}" class="form-control"
                                                        {{ $row->is_required === 1 ? 'required' : '' }}></textarea>
                                                </div>
                                            @endif
                                            @if ($row->type === 'file')
                                                <div class="form-group col-sm-12 col-md-6">
                                                    <label>{{ ucwords(str_replace('_', ' ', $row->name)) }}
                                                        {!! $row->is_required ? ' <span class="text-danger">*</span></label>' : '' !!}</label>
                                                    <input type="file" name="{{ $row->name }}"
                                                        class="file-upload-default"
                                                        {{ $row->is_required === 1 ? 'required' : '' }} />
                                                    <div class="input-group col-xs-12">
                                                        <input type="text" class="form-control file-upload-info"
                                                            disabled=""
                                                            placeholder="{{ ucwords(str_replace('_', ' ', $row->name)) }}"
                                                            required />
                                                        <span class="input-group-append">
                                                            <button class="file-upload-browse btn btn-theme"
                                                                type="button">{{ __('upload') }}</button>
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-sm-12 col-md-12">
                                            <div class="radio-group mt-2">
                                                <label class="radio-label">
                                                    <input type="radio" name="parent_guardian_type" value="Parent"
                                                        class="radio-input parent-check"
                                                        id="show-parents-details" checked>
                                                    <span class="radio-custom"></span>
                                                    <span class="radio-text">{{ __('parents_details') }}</span>
                                                </label>
                                                <label class="radio-label">
                                                    <input type="radio" name="parent_guardian_type" value="Guardian"
                                                        class="radio-input parent-check"
                                                        id="show-guardian-details">
                                                    <span class="radio-custom"></span>
                                                    <span class="radio-text">{{ __('guardian_details') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="guardian_div" style="display:none;">
                                        <div class="row">
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('email') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('guardian_email', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('email'),
                                                    'class' => 'form-control',
                                                    'id' => 'email',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('mobile') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::number('guardian_mobile', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('mobile'),
                                                    'class' => 'form-control',
                                                    'id' => 'guardian_mobile',
                                                    'min' => 0,
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('first_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('guardian_first_name', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('first_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'guardian_first_name',
                                                    'required',
                                                ]) !!}
                                            </div>

                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('last_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('guardian_last_name', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('last_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'guardian_last_name',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('dob') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('guardian_dob', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('dob'),
                                                    'class' => 'form-control datepicker-popup-no-future form-control',
                                                    'id' => 'guardian_dob',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                                <div class="radio-group mt-2">
                                                    <label class="radio-label">
                                                        <input type="radio" name="guardian_gender" value="male" id="guardian_male" class="radio-input" required>
                                                        <span class="radio-custom"></span>
                                                        <span class="radio-text">{{ __('male') }}</span>
                                                    </label>
                                                    <label class="radio-label">
                                                        <input type="radio" name="guardian_gender" value="female" id="guardian_female" class="radio-input" required>
                                                        <span class="radio-custom"></span>
                                                        <span class="radio-text">{{ __('female') }}</span>
                                                    </label>
                                                </div>
                                                
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('occupation') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('guardian_occupation', null, [
                                                    'placeholder' => __('guardian') . ' ' . __('occupation'),
                                                    'class' => 'form-control',
                                                    'id' => 'guardian_occupation',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('guardian') . ' ' . __('image') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="guardian_image"
                                                    class="guardian_image file-upload-default" accept="image/*" required />
                                                <div class="input-group col-xs-12">
                                                    <input type="text" class="form-control file-upload-info"
                                                        id="guardian_image" disabled=""
                                                        placeholder="{{ __('guardian') . ' ' . __('image') }}" />
                                                    <span class="input-group-append">
                                                        <button class="file-upload-browse btn btn-theme"
                                                            type="button">{{ __('upload') }}</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="parents_div" style="display:none;">
                                        <div class="row">
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father_email') }} <span class="text-danger">*</span></label>
                                                {!! Form::email('father_email', null, [
                                                    'placeholder' => __('father') . ' ' . __('email'),
                                                    'class' => 'form-control',
                                                    'id' => 'father_email',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('mobile') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::number('father_mobile', null, [
                                                    'placeholder' => __('father') . ' ' . __('mobile'),
                                                    'class' => 'form-control',
                                                    'id' => 'father_mobile',
                                                    'min' => 0,
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('first_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('father_first_name', null, [
                                                    'placeholder' => __('father') . ' ' . __('first_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'father_first_name',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('last_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('father_last_name', null, [
                                                    'placeholder' => __('father') . ' ' . __('last_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'father_last_name',
                                                    'required',
                                                ]) !!}
                                            </div>

                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('dob') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('father_dob', null, [
                                                    'placeholder' => __('father') . ' ' . __('dob'),
                                                    'class' => 'form-control datepicker-popup-no-future form-control',
                                                    'id' => 'father_dob',
                                                    'required',
                                                ]) !!}
                                            </div>

                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('occupation') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('father_occupation', null, [
                                                    'placeholder' => __('father') . ' ' . __('occupation'),
                                                    'class' => 'form-control',
                                                    'id' => 'father_occupation',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('father') . ' ' . __('image') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="father_image"
                                                    class="father_image file-upload-default" accept="image/*" required />
                                                <div class="input-group col-xs-12">
                                                    <input type="text" class="form-control file-upload-info"
                                                        id="father_image" disabled=""
                                                        placeholder="{{ __('father') . ' ' . __('image') }}" />
                                                    <span class="input-group-append">
                                                        <button class="file-upload-browse btn btn-theme"
                                                            type="button">{{ __('upload') }}</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother_email') }} <span class="text-danger">*</span></label>
                                                {!! Form::email('mother_email', null, [
                                                    'placeholder' => __('mother') . ' ' . __('email'),
                                                    'class' => 'form-control',
                                                    'id' => 'mother_email',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('mobile') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::number('mother_mobile', null, [
                                                    'placeholder' => __('mother') . ' ' . __('mobile'),
                                                    'class' => 'form-control',
                                                    'id' => 'mother_mobile',
                                                    'min' => 0,
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('first_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('mother_first_name', null, [
                                                    'placeholder' => __('mother') . ' ' . __('first_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'mother_first_name',
                                                    'required',
                                                ]) !!}
                                            </div>

                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('last_name') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('mother_last_name', null, [
                                                    'placeholder' => __('mother') . ' ' . __('last_name'),
                                                    'class' => 'form-control',
                                                    'id' => 'mother_last_name',
                                                    'required',
                                                ]) !!}
                                            </div>

                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('dob') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('mother_dob', null, [
                                                    'placeholder' => __('mother') . ' ' . __('dob'),
                                                    'class' => 'form-control datepicker-popup-no-future form-control',
                                                    'id' => 'mother_dob',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('occupation') }} <span
                                                        class="text-danger">*</span></label>
                                                {!! Form::text('mother_occupation', null, [
                                                    'placeholder' => __('mother') . ' ' . __('occupation'),
                                                    'class' => 'form-control',
                                                    'id' => 'mother_occupation',
                                                    'required',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-sm-12 col-md-6">
                                                <label>{{ __('mother') . ' ' . __('image') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="mother_image"
                                                    class="mother_image file-upload-default" accept="image/*" required />
                                                <div class="input-group col-xs-12">
                                                    <input type="text" class="form-control file-upload-info"
                                                        id="mother_image" disabled=""
                                                        placeholder="{{ __('mother') . ' ' . __('image') }}" />
                                                    <span class="input-group-append">
                                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                    @php
                                        $site_key = DB::table('settings')->where('type', 'recaptcha_site_key')->pluck('message')->first();
                                        $status = DB::table('settings')->where('type', 'recaptcha_status')->pluck('message')->first();
                                    @endphp
                                  
                                    @if($status == 1)  
                                        <input type="hidden" name="recaptcha_status" value="1">
                                        <div class="g-recaptcha" name="recaptcha" data-sitekey="{{ $site_key }}"></div>
                                        </div>
                                    @else
                                        <input type="hidden" name="recaptcha_status" value="0">
                                    @endif 
                                    <div>
                                        <input type="submit" class="commonBtn" value={{ __('submit') }}>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
@section('script')
    <script>
        $('#student-registration-form1').on('submit', function(e) {

            e.preventDefault();
            let formElement = $(this);
            let submitButtonElement = $(this).find(':submit');
            let url = $(this).attr('action');
            let data = new FormData(this);

            function successCallback() {
                setTimeout(function() {
                    window.location.reload();
                }, 4000); 
            }


            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
        });

        
        $(".student-registration-form1").validate({
            rules: {
                'first_name': "required",
                'last_name': "required",
                'mobile': "number",
                'image': {
                    required: true,
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
                'dob': "required",
                'class_section_id': "required",
                'category_id': "required",
                'admission_no': "required",
                'roll_number': "required",
                // 'caste': "required",
                // 'religion': "required",
                'admission_date': "required",
                'blood_group': "required",
                // 'height': "required",
                // 'weight': "required",
                'current_address': "required",
                'permanent_address': "required",
                'father_first_name': "required",
                'father_last_name': "required",
                'father_email': {
                    "email": true,
                    "required": true,
                },
                'father_mobile': {
                    "number": true,
                    "required": true,
                },
                'father_occupation': "required",
                'father_dob': "required",
                'father_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },

                'mother_email': {
                    "required": true,
                    "email": true,
                },
                'mother_first_name': "required",
                'mother_last_name': "required",
                'mother_mobile': {
                    "number": true,
                    "required": true,
                },
                'mother_occupation': "required",
                'mother_dob': "required",
                'mother_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
                'guardian_email': {
                    "required": true,
                    "email": true,
                },
                'guardian_first_name': "required",
                'guardian_last_name': "required",
                'guardian_mobile': {
                    "number": true,
                    "required": true,
                },
                'guardian_occupation': "required",
                'guardian_dob': "required",
                'guardian_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
            },
            messages: {
                'image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'father_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'mother_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'guardian_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                }
            },
            success: function(label, element) {
                // add the success class to the input field
                $(element).parent().removeClass('has-danger')
                $(element).removeClass('form-control-danger')
            },
            errorPlacement: function(label, element) {
                errorPlacement(label, element);
            },
            highlight: function(element, errorClass) {
                highlight(element, errorClass);
            }
        });
    </script>
@endsection
