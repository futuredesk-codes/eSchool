@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('student') . ' ' . __('id') . ' ' . __('card'). ' ' . __('settings')}}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <form class="pt-3 id-card-setting" id="id-card-setting" action="{{ route('id_card_settings.update') }}" method="POST" novalidate="novalidate">
                            <div class="row">
                                {{-- Colour --}}
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="header_color">{{ __('header_color') }} <span class="text-danger">*</span></label>
                                    <input name="header_color" id="header_color" value="{{ $settings['header_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="footer_color">{{ __('footer_color') }} <span class="text-danger">*</span></label>
                                    <input name="footer_color" id="footer_color" value="{{ $settings['footer_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="header_footer_color">{{ __('header_footer_text_color') }} <span class="text-danger">*</span></label>
                                    <input name="header_footer_text_color" id="header_footer_text_color" value="{{ $settings['header_footer_text_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
                                </div>
                                {{-- End Colour --}}

                                {{-- Background Image --}}
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="image">{{ __('background_image') }} </label>
                                    <input type="file" name="background_image" accept="image/jpg,image/png,image/jpeg,image/svg" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" id="image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    @if ($settings['background_image'] ?? '')
                                    <div id="background">
                                        <img src="{{ Storage::url($settings['background_image']) }}" class="img-fluid w-25 mt-2" alt="">
                                        <a href="" data-type="background_image" class="btn btn-inverse-danger btn-sm student-id-card-settings">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                {{-- End Background Image --}}

                                {{-- Signature --}}
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="image">{{ __('signature') }} </label>
                                    <input type="file" name="signature" accept="image/jpg,image/png,image/jpeg,image/svg" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" id="image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    @if ($settings['signature'] ?? '')
                                    <div id="signature">
                                        <img src="{{ Storage::url($settings['signature']) }}" class="img-fluid w-25 mt-2" alt="">
                                        <a href="" data-type="signature" class="btn btn-inverse-danger btn-sm student-id-card-settings">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                {{-- End Signature --}}


                                {{-- Layout Type --}}
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('layout_type') }} <span class="text-danger">*</span></label>
                                    <div class="col-12 d-flex row">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" class="form-check-input" @if(isset($settings['layout_type'])  && $settings['layout_type'] == 'vertical') checked @endif name="layout_type" id="layout_type" value="vertical" required>
                                                {{ __('vertical') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" class="form-check-input" @if(isset($settings['layout_type'])  && $settings['layout_type'] == 'horizontal') checked @endif name="layout_type" id="layout_type" value="horizontal" required>
                                                {{ __('horizontal') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                {{-- End Layout Type --}}

                                {{-- Profile Image Style --}}
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('profile_image_style') }} <span class="text-danger">*</span></label>
                                    <div class="col-12 d-flex row">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" class="form-check-input" @if(isset($settings['profile_image_style']) && $settings['profile_image_style'] == 'round') checked @endif name="profile_image_style" id="profile_image_style" value="round" required>
                                                {{ __('round') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" class="form-check-input" @if(isset($settings['profile_image_style']) && $settings['profile_image_style'] == 'squre') checked @endif name="profile_image_style" id="profile_image_style" value="squre" required>
                                                {{ __('squre') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                {{-- End Profile Image Style --}}

                                {{-- Page Size --}}
                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="">{{ __('card_width') }} ({{ __('mm') }})<span class="text-danger">*</span></label>
                                    <input name="card_width" id="card_width" value="{{ $settings['card_width'] ?? '' }}" type="number" required placeholder="{{ __('card_width') }}" class="form-control"/>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="">{{ __('card_height') }} ({{ __('mm') }})<span class="text-danger">*</span></label>
                                    <input name="card_height" id="card_height" value="{{ $settings['card_height'] ?? '' }}" type="number" required placeholder="{{ __('card_height') }}" class="form-control"/>
                                </div>
                                {{-- End Page Size --}}

                                {{-- Fields --}}
                                <div class="form-group col-sm-12 col-md-12">
                                    <label for="">{{ __('select_fields') }} <span class="text-danger">*</span></label>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="student_name" class="form-check form-check-inline" @if(in_array('student_name',$settings['student_id_card_fields'])) checked @endif type="checkbox" name="student_id_card_fields[]" value="student_name"/>
                                            {{ __('student_name') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="gr_no" class="form-check form-check-inline" @if(in_array('student_name',$settings['student_id_card_fields'])) checked @endif type="checkbox" name="student_id_card_fields[]" value="gr_no"/>
                                            {{ __('gr_no') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="class_section" class="form-check form-check-inline" @if(in_array('class_section',$settings['student_id_card_fields'])) checked @endif type="checkbox" name="student_id_card_fields[]" value="class_section"/>
                                            {{ __('class_section') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="roll_number" class="form-check form-check-inline" type="checkbox" @if(in_array('roll_no',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="roll_no"/>
                                            {{ __('roll_no') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="dob" class="form-check form-check-inline" type="checkbox" @if(in_array('dob',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="dob"/>
                                            {{ __('dob') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="gender" class="form-check form-check-inline" type="checkbox" @if(in_array('gender',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="gender"/>
                                            {{ __('gender') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="blood_group" class="form-check form-check-inline" type="checkbox" @if(in_array('blood_group',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="blood_group"/>
                                            {{ __('blood_group') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="session_year" class="form-check form-check-inline" type="checkbox" @if(in_array('session_year',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="session_year"/>
                                        {{ __('session_year') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="guardian_name" class="form-check form-check-inline" type="checkbox" @if(in_array('guardian_name',$settings['student_id_card_fields'])) checked @endif name="student_id_card_fields[]" value="guardian_name"/>
                                            {{ __('father').'/'. __('guardian') }} {{ __('name') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="guardian_contact" class="form-check form-check-inline" @if(in_array('guardian_contact',$settings['student_id_card_fields'])) checked @endif type="checkbox" name="student_id_card_fields[]" value="guardian_contact"/>
                                            {{  __('father').'/'. __('guardian') }} {{ __('contact') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input id="address" class="form-check form-check-inline" @if(in_array('address',$settings['student_id_card_fields'])) checked @endif type="checkbox" name="student_id_card_fields[]" value="address"/>
                                            {{ __('address') }}
                                        </label>
                                    </div>
                                </div>
                                {{-- End Fields --}}

                            </div>

                            <h3 class="page-title">
                                <small class="theme-color">Note: These signature image are also used in other documents such as Bonafide Certificates, Leaving Certificates, and Student Result Cards.</small>
                            </h3>
                            <br>
                            <br>
                            <input class="btn btn-theme" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type='text/javascript'>
        if ($(".color-picker").length) {
            $('.color-picker').asColorPicker();
        }
    </script>
@endsection
