@extends('layouts.master')

@section('title')
    {{ __('chat_settings') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('chat_settings') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('chat_settings') }}
                        </h4>
                        <hr>
                        @php
                            $max_files_or_images_in_one_message = ini_get("max_file_uploads");
                            $maxFileSizeStr = ini_get("upload_max_filesize");
                            $maxFileSizeBytes = return_bytes($maxFileSizeStr);
                            $maxFileSizeMB = $maxFileSizeBytes / (1024 * 1024); // Convert bytes to megabytes
                            $max_file_size_in_bytes = round($maxFileSizeMB, 2) . ' MB'; // Round to 2 decimal places for MB
                            function return_bytes($size_str)
                            {
                                switch (substr($size_str, -1)) {
                                    case 'M':
                                    case 'm':
                                        return (int)$size_str * 1048576;
                                    case 'K':
                                    case 'k':
                                        return (int)$size_str * 1024;
                                    case 'G':
                                    case 'g':
                                        return (int)$size_str * 1073741824;
                                    default:
                                        return $size_str;
                                }
                            }
                        @endphp
                        <form id="frmData" class="general-setting" action="{{ route('chat_setting.update')}}" novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="row mt-5">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_files_upload') }} <span class="text-info">(In Single Message)</span></label>
                                    <input name="max_files_or_images_in_one_message" value="{{ isset($settings['max_files_or_images_in_one_message']) ? $settings['max_files_or_images_in_one_message'] : '10' }}" type="number" required placeholder="{{ __('max_files_upload') }}" class="form-control"  min="2" max="{{$max_files_or_images_in_one_message}}"/>
                                    <p><strong>Note : </strong> The Maximum File Uploads is allowed only {{$max_files_or_images_in_one_message}} </p>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_file_size') }} <span class="text-info">(In MB)</span></label>
                                    <input name="max_file_size_in_bytes" value="{{ isset($settings['max_file_size_in_bytes']) ? $settings['max_file_size_in_bytes'] : '10' }}" type="number" required placeholder="{{ __('max_file_size') }}" class="form-control" min="1" max="{{$maxFileSizeMB}}"/>
                                    <p><strong>Note : </strong> The Maximum File Upload Size is allowed {{$max_file_size_in_bytes}} </p>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_characters') }} <span class="text-info">(In Text Message)</span></label>
                                    <input name="max_characters_in_text_message" value="{{ isset($settings['max_characters_in_text_message']) ? $settings['max_characters_in_text_message'] : '500' }}" type="number" required placeholder="{{ __('max_characters') }}" class="form-control" min="10" max="1000"/>
                                </div>

                            </div>

                            <h4 class="card-title">
                                {{ __('delete_messages') }}
                            </h4>
                            <hr>

                            <h4 class="card-title mt-5 mb-3">
                                {{ __('automatically') }}  {{ __('delete') }}  {{ __('messages') }}
                            </h4>

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12">
                                    <div class="alert alert-danger" role="alert">
                                        {{ __('Kindly configure the cron job on your server to execute the URL every day, this will facilitate the regular delete old chat messages with files') }}
                                    </div>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('automatically_messages_removed_days') }}</label>
                                    <input name="automatically_messages_removed_days" value="{{ isset($settings['automatically_messages_removed_days']) ? $settings['automatically_messages_removed_days'] : '30' }}" type="number" placeholder="{{ __('automatically_messages_removed_days') }}" class="form-control" required/>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label><strong>{{ __('Cron Job URL') }}</strong> :</label>

                                    {!! Form::text('info-link', url('delete-chat-message/cron-job'), ['class' => 'form-control', 'readonly']) !!}
                                </div>
                            </div>

                            <input class="btn btn-theme" type="submit" value="Submit">
                        </form>
                    </div>
                </div>


            </div>

        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('manually') }}  {{ __('delete') }}  {{ __('messages') }}
                        </h4>
                        <hr>
                        <form id="chat-delete" class="chat-delete" action="{{ route('chat_message.delete')}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row mt-5">
                                <div class="form-group col-md-6">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="from_date" placeholder="{{__('from_date')}}" class="datepicker-popup form-control" required>
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-md-5">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="to_date" placeholder="{{__('to_date')}}" class="datepicker-popup form-control" required>
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-md-1 mt-4">
                                    <button class="btn btn-xs btn-gradient-danger btn-icon"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
