<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title') - {{ config('app.name', 'Laravel') }} </title>

        <!-- Scripts -->
        <script>
            var baseurl = '{{ env('APP_URL', URL::to('/')) }}';
            // constantes 
            var tematicas_max = '{{ config('ctes.tematicas_max') }}'
        </script>
        <script src="{{ asset('js/app.js') }}" ></script>
        
        @yield('js')

        {{--
        <!-- Fonts -->
        ver fonts cargadas en css 
        --}}

        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        <link href="{{ asset('css/diccionario_personal.css') }}" rel="stylesheet">
        <link href="{{ asset('css/diccionario_aula.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
        @yield('css')

    </head>

    <body class="@yield('dc-main-class', formatClassStr(Route::currentRouteName()) )">
        <div id="app">
            <nav class="navbar navbar-expand-md navbar-dark shadow-sm bg-primary">
                <div class="container">
                    <img class="d-block d-md-none" id="escudoMb" src="{{ asset('/imagenes/GobCanEscudo.svg') }}" alt="Escudo Gobierno De Canarias">
                    <img class="d-none d-md-block" id="escudo" src="{{ asset('/imagenes/logoGobCanBlanco.png') }}" alt="Escudo Gobierno De Canarias">
                    {{-- <a class="navbar-brand d-block d-md-none" href="{{ url('/') }}">
                        <h1>LEXI<span>CAN</span></h1>
                    </a> --}}
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <h1 class="logo">LEXI<span>CÁN</span></h1>
                        {{-- {{ config('app.name', 'Laravel') }} --}}
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        <ul class="navbar-nav mr-auto">

                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ml-auto">
                            <!-- Authentication Links -->
                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('cas.login') }}">{{ __('Login') }}</a>
                                </li>
                                {{-- pongo el logout para los usuarios de cas que no tiene autorizacion  --}}
                                <li class="nav-item">
                                    <a class="nav-link" href="#"
                                    onclick="
                                        console.log('click');
                                        event.preventDefault();
                                        document.getElementById('logout-cas-form').submit();"
                                     >
                                            {{ __('diccionario.logout') }}
                                    </a>
                                </li>
                                <form id="logout-cas-form" action="{{ route('cas.logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                                {{-- @if(Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif --}}
                            @else
                                @yield('nav')
                                {{-- Logout CAS --}}
                                <li class="nav-item">
                                    <a class="nav-link" href="#"
                                    onclick="
                                        console.log('click');
                                        event.preventDefault();
                                        document.getElementById('logout-cas-form').submit();"
                                     >
                                            {{ __('diccionario.logout') }}
                                    </a>
                                </li>
                                <form id="logout-cas-form" action="{{ route('cas.logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>

                            @endguest

                        </ul>
                    </div>
                </div>
            </nav>
            <div id="header-line">
                @yield('header-line-content')
            </div>
            <div id="header-tabs">
                <div class="container">
                    @yield('headertabs')
                </div>
            </div>

            <main class="py-4">
                <div class="container">
                    @yield('content')
                </div>
                <div class="container">
                    @yield('footer')
                </div>
            </main>
            @include('diccionario.cookies')
        </div>
        

        @yield('bodyLast')
        @yield('scripts')

    </body>

</html>