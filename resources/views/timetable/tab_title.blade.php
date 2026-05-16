<div class="repeater">
    <div class="row">
        <div class="input-group col-sm-12 col-md-2">
            {{ __('Subjects') }} <span class="text-danger"> *</span>
        </div>
        <div class="input-group col-sm-12 col-md-2">
            {{ __('teacher') }} <span class="text-danger">*</span>
        </div>
        <div class="input-group col-sm-12 col-md-2">
            {{ __('Start time') }} <span class="text-danger">*</span>
        </div>
        <div class="input-group col-sm-12 col-md-2">
            {{ __('End time') }} <span class="text-danger">*</span>
        </div>
        <div class="input-group col-sm-12 col-md-2">
            {{ __('Live Class Link') }}
        </div>
        <div class="input-group col-sm-12 col-md-1">
            {{ __('Name') }}
        </div>
        <div class="input-group col-sm-12 col-md-1">
            <button data-repeater-create type="button"
                class="addmore d-none btn btn-gradient-info btn-sm icon-btn ml-2 mb-2">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>

    <form class="pt-3" action="{{ url('timetable') }}" id="formdata" method="POST" novalidate="novalidate">
        @csrf
        <input required type="hidden" name="day" id="day" class="day">
        <input required type="hidden" name="session_year_id" id="session_year_id">
        <input required type="hidden" name="class_section_id" id="class_section_id">
        <input type="hidden" name="semester_id" id="semester_id">
