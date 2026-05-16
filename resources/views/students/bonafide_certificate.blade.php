@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('bonafide') . ' ' . __('certificate') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('generate') . ' ' . __('bonafide') . ' ' . __('certificate') }}
                        </h4>
                        <form class="generate-document" id="generate-document" action="{{route('bonafide.certificate.pdf')}}" method="POST">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="id" id="id" value="{{$student->id}}"/>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('reason', null, ['placeholder' => __('reason'), 'class' => 'form-control', 'rows'=>2]) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('valid_upto') }}</label>
                                    {!! Form::text('valid_upto', null, ['placeholder' => __('valid_upto'), 'class' => 'datepicker-popup form-control']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>

                            </div>
                            <input type="submit" class="btn btn-theme mt-4" value="{{ __('Generate') }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
