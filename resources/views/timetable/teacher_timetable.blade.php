@extends('layouts.master')

@section('title')
    {{ __('teacher_timetable') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('list') . ' ' . __('teacher_timetable') }}
            </h3>
        </div>
        <div class="row">

            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            @can('timetable-create')
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-2">
                                        <label for="session_year" class="form-label text-nowrap">
                                            {{ __('session_year') }}
                                        </label>
                                        <select name="session_year" id="session_year" class="form-control session_year">
                                            <option value="">{{ __('select') }}</option>
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="select_error text-danger d-none">This field is required.</div>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-2">
                                        <label>{{ __('teacher') }}</label>
                                        <select required name="class_section_id" id="teacher_timetable_teacher_id"
                                            class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">{{ __('select') }}</option>
                                            @foreach ($teacher as $teacher)
                                                <option value="{{ $teacher->id }}">
                                                    {{ $teacher->user->first_name . ' ' . $teacher->user->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endcan
                        </h4>

                        <h4 class="card-title">
                            @cannot('timetable-create')
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-3">
                                        <label for="session_year" class="form-label text-nowrap">
                                            {{ __('session_year') }}
                                        </label>
                                        <select name="session_year" id="session_year" class="form-control session_year">
                                            <option value="">{{ __('select') }}</option>
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="select_error text-danger d-none">This field is required.</div>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-3">
                                        <label>{{ __('class') }} {{ __('section') }}</label>
                                        <select required name="class_section_id" id="teacher_timetable_class_section"
                                            class="form-control" style="width:100%;" tabindex="-1" aria-hidden="true"
                                            data-scope="teacher">
                                            <option value="">{{ __('select') }}</option>
                                        </select>
                                    </div>
                                </div>
                            @endcannot
                        </h4>
                        <div class="alert alert-warning text-center w-75 m-auto warning_no_data" role="alert"
                            style="display: none">
                            <strong>{{ __('no_data_found') }}</strong>
                        </div>

                        <div class="row set_timetable"></div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('live_class_url') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 section-create-form" id="create-form" action="{{ url('link-url-update') }}"
                            novalidate="novalidate">
                            <input type="hidden" name="edit_id" id="edit_id" value="" />
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('live_class_url') }} <span class="text-danger">*</span></label>
                                        <input name="live_class_url" id="live_class_url" type="text"
                                            placeholder="{{ __('live_class_url') }}" class="form-control" required />
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('link_name') }} <span class="text-danger">*</span></label>
                                        <input name="link_name" id="link_name" type="text"
                                            placeholder="{{ __('link_name') }}" class="form-control" required />
                                    </div>
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
    <script>
        $(document).on('click', '.classurl', function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            $('#edit_id').val(id);
            $('#editModal').modal('show');

            let url = baseUrl + '/get-timetable-data/' + id;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response) {
                        $('#live_class_url').val(response.live_class_url || '');
                        $('#link_name').val(response.link_name || '');
                    }
                },
                error: function(xhr) {
                    console.error('Failed to fetch timetable data:', xhr.responseText);
                }
            });
        });
    </script>
@endsection
