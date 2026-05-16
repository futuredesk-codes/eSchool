<!DOCTYPE html>
@php
    $lang = Session::get('language');
@endphp
@if ($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en">
    @endif
@else
    <html lang="en">
@endif

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') || {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.include')
    @yield('css')

</head>

<body class="sidebar-fixed">
    <div class="container-scroller">

        {{-- header --}}
        @include('layouts.header')

        <div class="container-fluid page-body-wrapper">

            {{-- siderbar --}}
            @include('layouts.sidebar')

            <div class="main-panel">

                @yield('content')

                {{-- footer --}}
                @include('layouts.footer')

            </div>

        </div>

    </div>

    @include('layouts.footer_js')

    {{-- After Update Notes Modal --}}
    @include('after-update-note-modal')


    @yield('js')

    @yield('script')

    {{-- This js code is to sync session year dropdown with sessionStorage (UI Component is present in header.blade.php) --}}
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('filter_session_year');
            if (!dropdown) return;

            const stored = sessionStorage.getItem('session_year_id');

            if (!stored) {
                const value = dropdown.value;
                sessionStorage.setItem('session_year_id', value);
                syncSessionYear(value);
            } else if (dropdown.value !== stored) {
                dropdown.value = stored;
            }

            dropdown.addEventListener('change', function() {
                const value = this.value;
                sessionStorage.setItem('session_year_id', value);
                syncSessionYear(value);
            });

            function syncSessionYear(value) {
                fetch("{{ route('set.session.year') }}", {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_year_id: value
                    })
                });
            }
        });
    </script> --}}
</body>

</html>
