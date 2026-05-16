@extends('layouts.master')

@section('title')
    {{ __('session_years') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('session_years') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">

                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('session_years') }}
                            </h4>
                            <button type="button" class="btn create-session-year-btn btn-success" data-toggle="modal"
                                data-target="#createSessionYearModal">
                                Create Session Year
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ url('session_years_list') }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                    data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                    data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "session-year-list-<?= date('d-m-y') ?>
                                    ","ignoreColumn": ["operate"]}'
                                    data-query-params="sessionYearQueryParams" data-escape="true">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}
                                            </th>
                                            <th scope="col" data-field="name" data-sortable="false">
                                                {{ __('name') }}</th>
                                            <th scope="col" data-field="start_date" data-sortable="true">
                                                {{ __('start_date') }}</th>
                                            <th scope="col" data-field="end_date" data-sortable="true">
                                                {{ __('end_date') }}</th>
                                            <th scope="col" data-field="default" data-sortable="true" data-visible="true"
                                                data-formatter="defaultYearFormatter">
                                                {{ __('default') }}</th>
                                            <th data-escape="false" data-events="sessionYearEvents" scope="col"
                                                data-field="operate" data-sortable="false">{{ __('action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('session_years.edit-modal')
    @include('session_years.create-modal', ['sessionYears' => $sessionYears])
    @include('session_years.delete-clear-modal')
@endsection
