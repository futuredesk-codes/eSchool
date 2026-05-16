@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('leaving') . ' ' . __('certificate') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('generate') . ' ' . __('leaving') . ' ' . __('certificate') }}
                        </h4>
                        <form class="leaving-document" id="leaving-document" action="{{route('leaving.certificate.pdf')}}" method="POST">
                            @csrf
                            <div class="row mt-4">
                                <input type="hidden" name="id" id="id" value="{{$student->id}}"/>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('reason').' '.__('for').' '. __('leaving') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('reason', null, ['placeholder' => __('reason'), 'class' => 'form-control', 'rows'=>2]) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('promoted_to') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('promoted_to', null, ['placeholder' => __('promoted_to'), 'class' => 'form-control','rows'=>2]) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('general_conduct')}}</label>
                                    {!! Form::textarea('general_conduct', null, ['placeholder' => __('general_conduct'), 'class' => 'form-control','rows'=>2]) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('any_other').'/'.__('remark')}}</label>
                                    {!! Form::textarea('remark', null, ['placeholder' => __('remark'), 'class' => 'form-control', 'rows'=>2]) !!}
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
