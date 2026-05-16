@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('grade') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('grade') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div class='row'>
                            <div class="col-12 col-md-3 form-group">
                                <label for="filter_session_year" class="text-nowrap">
                                    {{ __('session_year') }}
                                </label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <h4 class="page-title mb-4">
                            {{ __('create') . ' ' . __('grade') }}
                        </h4>
                        <div class="form-group">
                            {{-- Template for New Grade --}}
                            <div class="grade_content_div" style="display: none;">
                                <div class="grade_content">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label>{{ __('starting_range') }} </label>
                                            <input type="number" name="grade[0][starting_range]"
                                                class="temp_starting_range form-control"
                                                placeholder="{{ __('starting_range') }}" required />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>{{ __('ending_range') }} </label>
                                            <input type="number" name="grade[0][ending_range]"
                                                class="temp_ending_range form-control"
                                                placeholder="{{ __('ending_range') }}" required />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>{{ __('grade') }} </label>
                                            <input type="text" name="grade[0][grades]" class="temp_grade form-control"
                                                placeholder="{{ __('grade') }}" required />
                                        </div>
                                        <div class="form-group col-md-1 pl-0 mt-4">
                                            <button type="button" class="btn btn-icon btn-inverse-danger remove-grades"
                                                title="Remove Grade">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- End Template for New Grade --}}
                            <form id="create-grades" action="{{ url('create-grades') }}" method="POST">
                                <div class="extra_content">
                                    @for ($i = 0; $i < count($grades); $i++)
                                        <div class="grade_content">
                                            <div class="row">
                                                <input type="hidden" name="grade[{{ $i }}][id]"
                                                    class="form-control hidden" value={{ $grades[$i]['id'] }} />
                                                <div class="form-group col-md-4">
                                                    <label>{{ __('starting_range') }} </label>
                                                    @if (isset($grades[$i - 1]))
                                                        @php
                                                            $min = $grades[$i - 1]['ending_range'];
                                                            $min = $min + 1;
                                                        @endphp
                                                        <input type="number" min="{{ $min }}"
                                                            name="grade[{{ $i }}][starting_range]"
                                                            class="starting_range form-control"
                                                            placeholder="{{ __('starting_range') }}"
                                                            value="{{ $grades[$i]['starting_range'] }}" />
                                                    @else
                                                        <input type="number" min="0"
                                                            name="grade[{{ $i }}][starting_range]"
                                                            class="starting_range form-control"
                                                            placeholder="{{ __('starting_range') }}"
                                                            value="{{ $grades[$i]['starting_range'] }}" />
                                                    @endif
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>{{ __('ending_range') }} </label>
                                                    @if (isset($grades[$i + 1]))
                                                        @php
                                                            $max = $grades[$i + 1]['starting_range'];
                                                            $max = $max - 1;
                                                        @endphp
                                                        <input type="number"
                                                            name="grade[{{ $i }}][ending_range]"
                                                            max="{{ $max }}" class="ending_range form-control"
                                                            placeholder="{{ __('ending_range') }}"
                                                            value="{{ $grades[$i]['ending_range'] }}" />
                                                    @else
                                                        <input type="number"
                                                            name="grade[{{ $i }}][ending_range]" max=100
                                                            class="ending_range form-control"
                                                            placeholder="{{ __('ending_range') }}"
                                                            value="{{ $grades[$i]['ending_range'] }}" />
                                                    @endif
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label>{{ __('grade') }} </label>
                                                    <input type="text" name="grade[{{ $i }}][grades]"
                                                        class="grade form-control" placeholder="{{ __('grade') }}"
                                                        value="{{ $grades[$i]['grade'] }}" />
                                                </div>
                                                <div class="form-group col-md-1 pl-0 mt-4">
                                                    <button type="button"
                                                        class="btn btn-icon btn-inverse-danger remove-grades"
                                                        data-id="{{ $grades[$i]['id'] }}" title="Remove Grade">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                                <div class="extra-grade-content"></div>
                                <div class="col-md-4 pl-0 mb-4">
                                    <button type="button" class="btn btn-success add-grade-content" title="Add new row">
                                        Add New Data
                                    </button>
                                </div>
                                <input type="submit" class="btn btn-theme" value={{ __('submit') }} />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @section('js')
        <script type="text/javascript">
            $('#filter_session_year').on('change', function() {
                const sessionYearId = $(this).val();

                $.ajax({
                    url: "{{ url('grades/by-session-year') }}/" + sessionYearId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            rebuildGradesUI(response.grades);
                        }
                    },
                    error: function() {
                        alert('Failed to load grades.');
                    }
                });
            });

            // Load grades for the initially selected session year
            $('#filter_session_year').trigger('change');

            function rebuildGradesUI(grades) {
                const container = $('.extra_content');
                container.empty();

                grades.forEach((grade, index) => {
                    let min = index > 0 ? grades[index - 1].ending_range + 1 : 0;
                    let max = grades[index + 1] ? grades[index + 1].starting_range - 1 : 100;

                    container.append(`
                <div class="grade_content">
                    <div class="row">
                        <input type="hidden" name="grade[${index}][id]" value="${grade.id}" />

                        <div class="form-group col-md-4">
                            <label>{{ __('starting_range') }}</label>
                            <input type="number"
                                   name="grade[${index}][starting_range]"
                                   class="starting_range form-control"
                                   min="${min}"
                                   value="${grade.starting_range}">
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('ending_range') }}</label>
                            <input type="number"
                                   name="grade[${index}][ending_range]"
                                   class="ending_range form-control"
                                   max="${max}"
                                   value="${grade.ending_range}">
                        </div>

                        <div class="form-group col-md-3">
                            <label>{{ __('grade') }}</label>
                            <input type="text"
                                   name="grade[${index}][grades]"
                                   class="grade form-control"
                                   value="${grade.grade}">
                        </div>

                        <div class="form-group col-md-1 pl-0 mt-4">
                            <button type="button"
                                    class="btn btn-icon btn-inverse-danger remove-grades"
                                    data-id="${grade.id}">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `);
                });
            }
        </script>
    @endsection
