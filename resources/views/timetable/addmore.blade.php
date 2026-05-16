<div class="input-group col-sm-12 col-md-2">
    <select required name="subject_id" id="subject" class="form-control set_subject_id" style="width:100%;" tabindex="-1" aria-hidden="true"></select>
</div>
<div class="input-group col-sm-12 col-md-2">
    <select required name="teacher_id" id="teacher" class="form-control set_teacher_id" style="width:100%;" tabindex="-1" aria-hidden="true"></select>
</div>
<div class="input-group col-sm-12 col-md-2">
    <input required type="time" name="start_time" class="timetable_start_time form-control" placeholder="{{__('start_time')}}">
</div>
<div class="input-group col-sm-12 col-md-2">
    <input required type="time" name="end_time" class="timetable_end_time form-control" placeholder="{{__('end_time')}}">
</div>
<div class="input-group col-sm-12 col-md-2">
    <input type="text" name="live_class_url" class="form-control" placeholder="{{__('Link')}}">
</div>
<div class="input-group col-sm-12 col-md-1">
    <input type="text" name="link_name" class="form-control" placeholder="{{__('Link')}}">
</div>
<button data-repeater-delete type="button" class="row_remove btn btn-gradient-danger btn-sm icon-btn ml-2">
    <i class="fa fa-trash"></i>
</button>
