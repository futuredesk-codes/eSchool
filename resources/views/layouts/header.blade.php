<!-- partial:../../partials/_navbar.html -->
@if (Session::get('locale'))
    {{ app()->setLocale(Session::get('locale')) }}
@endif
<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="{{ URL::to('/home') }}"> <img
                src="{{ env('LOGO1') ? url(Storage::url(env('LOGO1'))) : url('assets/logo.svg') }}" alt="logo"> </a>
        <a class="navbar-brand brand-logo-mini" href="{{ URL::to('/') }}"> <img
                src="{{ asset('storage/' . env('FAVICON')) }}" alt="logo"> </a>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="fa fa-bars"></span>
        </button>
        @php
            $email_config_verify_value = DB::table('settings')
                ->select('message')
                ->where('type', 'email_configration_verification')
                ->first();
            if ($email_config_verify_value) {
                $message = $email_config_verify_value->message;
            } else {
                $message = 0;
            }
        @endphp
        @if ($message == 0)
            @can('email-setting-create')
                <div class="mx-auto order-0 d-none d-lg-block">
                    <div class="alert alert-fill-danger my-2" role="alert">
                        <i class="fa fa-exclamation"></i> Email Configration is not verified <a
                            href="{{ route('setting.email-config-index') }}" class="alert-link">Click here to redirect to
                            email configration</a>.
                    </div>
                </div>
            @endcan
        @endif
        @php
            $current_version = getSettings('system_version');
            $current_version = $current_version['system_version'];
            $current_session_id = getSettings('session_year')['session_year'];
            $current_session = DB::table('session_years')
                ->select('id', 'name')
                ->where('id', $current_session_id)
                ->whereNull('deleted_at')
                ->first();
            $session_years = DB::table('session_years')->whereNull('deleted_at')->orderBy('id', 'ASC')->get();
            $filter_session_year_selected = session('session_year', $current_session_id);
        @endphp
        <ul class="navbar-nav navbar-nav-left d-flex">
            <li class="nav-item">
                <a class="nav-link" href="#" aria-expanded="false">
                    <span class="badge badge-success">{{ $current_version }} v</span>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav navbar-nav-right d-none d-lg-flex">
            <li>
                <div class="d-flex align-items-center gap-between-elements-1">
                    <span class="info-tooltip">
                        <i class="fa fa-info-circle text-muted"></i>
                        <span class="tooltip-text">
                            This filter lets you view data for different session years. The system’s active session year
                            can only be changed from Settings.
                        </span>
                    </span>

                    <select id="filter_session_year_global" data-scope="global"
                        class="session-year-dropdown filter_session_year">
                        @foreach ($session_years as $session_year)
                            <option value="{{ $session_year->id }}"
                                {{ $session_year->id == $filter_session_year_selected ? 'selected' : '' }}>
                                {{ $session_year->name }}
                                @if ($current_session && $session_year->id == $current_session->id)
                                    - System Default
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </li>

            <a class="nav-link" href="{{ url('clear') }}">
                <input class="btn-inverse-info btn" type="submit" value="{{ __('cache_clear') }}">
            </a>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#"
                    data-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-language"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                    aria-labelledby="messageDropdown">
                    @foreach (get_language() as $key => $language)
                        <a class="dropdown-item preview-item" href="{{ url('set-language') . '/' . $language->code }}">
                            <div class="preview-thumbnail">
                            </div>
                            <div
                                class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                <h6 class="preview-subject ellipsis mb-1 font-weight-normal">{{ $language->name }}</h6>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                    @endforeach
                </div>
            </li>
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown"
                    aria-expanded="true">
                    <div class="nav-profile-img">
                        <img src="{{ Auth::user()->image }}" alt="image" onerror="onErrorImage(event)">
                    </div>
                    <div class="nav-profile-text">
                        <p class="mb-1 text-black">{{ Auth::user()->first_name }}</p>
                    </div>
                </a>
                <div class="dropdown-menu navbar-dropdown dropdown-menu-custom-style" aria-labelledby="profileDropdown">
                    @can('update-admin-profile')
                        <a class="dropdown-item" href="{{ route('edit-profile') }}">
                            <i class="fa fa-user mr-2"></i>{{ __('update_profile') }}</a>
                        <div class="dropdown-divider"></div>
                    @endcan
                    <a class="dropdown-item" href="{{ route('resetpassword') }}">
                        <i class="fa fa-refresh mr-2 text-success"></i>{{ __('change_password') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}">
                        <i class="fa fa-sign-out mr-2 text-primary"></i> {{ __('signout') }}
                    </a>
                </div>
            </li>
        </ul>

        <ul class="navbar-nav navbar-nav-right d-flex d-md-none">
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown"
                    aria-expanded="true">
                    <div class="nav-profile-img">
                        <img src="{{ Auth::user()->image }}" alt="image" onerror="onErrorImage(event)">
                    </div>
                    <div class="nav-profile-text">
                        <p class="mb-1 text-black">{{ Auth::user()->first_name }}</p>
                    </div>
                </a>
                <div class="dropdown-menu navbar-dropdown dropdown-menu-custom-style" aria-labelledby="profileDropdown">
                    @can('update-admin-profile')
                        <a class="dropdown-item" href="{{ route('edit-profile') }}">
                            <i class="fa fa-user mr-2"></i>{{ __('update_profile') }}</a>
                        <div class="dropdown-divider"></div>
                    @endcan
                    <a class="dropdown-item" href="{{ route('resetpassword') }}">
                        <i class="fa fa-refresh mr-2 text-success"></i>{{ __('change_password') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}">
                        <i class="fa fa-sign-out mr-2 text-primary"></i> {{ __('signout') }}
                    </a>
                </div>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas">
            <span class="fa fa-bars"></span>
        </button>
    </div>
</nav>
