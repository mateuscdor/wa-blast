<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- 
    
Copyright By Ilman Sunanuddin, M pedia
Email : Ilmansunannudin2@gmail.com / admin@m-pedia.my.id
Whatsap : 6282298859671
------------------------------------------------------------------
Dilarang share atau menjual belikan source code ini tanpa izin ya bos! biar berkah hehe
--}}

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{getSystemSettings('site-description', 'MPWA MD V3.5.0 ,Whatsapp gateway Multi device Beta version')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="keywords" content="waapi,wa gateway, whatsapp blast, wamp, mpwa, m pedia wa gateway, wa gateway m pedia, ">
    <meta name="author" content="Ilman Sunanuddin , M pedia">
    <title>@yield('title') | {{getSystemSettings('site-title', 'MPWA Multi device version')}}</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
    <link href="{{ asset('plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/perfectscroll/perfect-scrollbar.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/pace/pace.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/highlight/styles/github-gist.css')}}" rel="stylesheet">
    <script src="{{asset('plugins/jquery/jquery-3.5.1.min.js')}}"></script>
    <link href="{{asset('css/main.min.css')}}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{url(getSystemSettings('logo-icon', 'images/avatars/avatar2.png'))}}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{url(getSystemSettings('logo-icon', 'images/avatars/avatar2.png'))}}" />

    @stack('head')
    @stack('head-scripts')
    @stack('styles')
    <style>
        .nav-link.nav-notifications-toggle {
            width: 35px;
            height: 35px;
            padding: 0!important;
        }
        div.dataTables_wrapper div.dataTables_length select {
            width: auto;
        }
        div.dataTables_wrapper>.row>.col-sm-12 {
            overflow: auto;
        }
        table>tbody>tr.selected {
             background-color: #d9d9d9 !important;
         }
        tr[data-id] {
            cursor: pointer;
        }
        .multi_input__selector {
            background-color: rgba(109, 202, 10, 0.19);
            color: #6dca0a;
            font-size: 12px;
            font-weight: 600;
            border-radius: 100px;
            width: fit-content;
            white-space: nowrap;
            padding: 2px 10px;
            display: block;
            user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -webkit-user-select: none;
        }
    </style>

</head>

<body>

<div class="app align-content-stretch d-flex flex-wrap">
    @include('layouts.sidebar')
    <div class="app-container">
        <div class="search">
            <form>
                <input class="form-control" type="text" placeholder="Type here..." aria-label="Search">
            </form>
            <a href="#" class="toggle-search"><i class="material-icons">close</i></a>
        </div>
        @include('layouts.app-header')
        <div class="app-content">
            @yield('content')
        </div>
    </div>
</div>
@stack('footer')

    <!-- Javascripts -->
<script src="{{asset('plugins/bootstrap/js/popper.min.js')}}"></script>
<script src="{{asset('plugins/bootstrap/js/bootstrap.min.js')}}"></script>
<script src="{{asset('plugins/perfectscroll/perfect-scrollbar.min.js')}}"></script>
<script src="{{asset('plugins/pace/pace.min.js')}}"></script>
<script src="{{asset('plugins/highlight/highlight.pack.js')}}"></script>
<script src="{{asset('plugins/blockUI/jquery.blockUI.min.js')}}"></script>
<script src="{{asset('js/main.min.js')}}"></script>
<script src="{{asset('js/custom.js?t=' . getLastJSTime() . rand())}}"></script>
<script src="{{asset('js/pages/blockui.js')}}"></script>
<script>
    $('[data-stop-propagation]').click(function(e){
       e.stopPropagation();
    });
</script>
@stack('scripts')
</body>

</html>