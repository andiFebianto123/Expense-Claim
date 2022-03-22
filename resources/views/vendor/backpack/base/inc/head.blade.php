<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
@if (config('backpack.base.meta_robots_content'))<meta name="robots" content="{{ config('backpack.base.meta_robots_content', 'noindex, nofollow') }}"> @endif

<meta name="csrf-token" content="{{ csrf_token() }}" /> {{-- Encrypted CSRF token for Laravel, in order for Ajax requests to work --}}
<title>{{ isset($title) ? $title.' :: '.config('backpack.base.project_name') : config('backpack.base.project_name') }}</title>
<link rel="shortcut icon" href="{{asset('images/logo-taisho.png')}}">
@yield('before_styles')
@stack('before_styles')

@if (config('backpack.base.styles') && count(config('backpack.base.styles')))
    @foreach (config('backpack.base.styles') as $path)
    <link rel="stylesheet" type="text/css" href="{{ asset($path).'?v='.config('backpack.base.cachebusting_string') }}">
    @endforeach
@endif

@if (config('backpack.base.mix_styles') && count(config('backpack.base.mix_styles')))
    @foreach (config('backpack.base.mix_styles') as $path => $manifest)
    <link rel="stylesheet" type="text/css" href="{{ mix($path, $manifest) }}">
    @endforeach
@endif

@yield('after_styles')
@stack('after_styles')

{{-- CSS loaders --}}
<style>
    .sk-fading-circle {
        margin: 100px auto;
        width: 45px;
        height: 45px;
        position: relative;
    }

    .sk-fading-circle .sk-circle {
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
    }

    .sk-fading-circle .sk-circle:before {
        content: '';
        display: block;
        margin: 0 auto;
        width: 15%;
        height: 15%;
        background-color: #2184ff;
        border-radius: 100%;
        -webkit-animation: sk-circleFadeDelay 1.2s infinite ease-in-out both;
                animation: sk-circleFadeDelay 1.2s infinite ease-in-out both;
    }
    .sk-fading-circle .sk-circle2 {
        -webkit-transform: rotate(30deg);
            -ms-transform: rotate(30deg);
                transform: rotate(30deg);
    }
    .sk-fading-circle .sk-circle3 {
        -webkit-transform: rotate(60deg);
            -ms-transform: rotate(60deg);
                transform: rotate(60deg);
    }
    .sk-fading-circle .sk-circle4 {
        -webkit-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
                transform: rotate(90deg);
    }
    .sk-fading-circle .sk-circle5 {
        -webkit-transform: rotate(120deg);
            -ms-transform: rotate(120deg);
                transform: rotate(120deg);
    }
    .sk-fading-circle .sk-circle6 {
        -webkit-transform: rotate(150deg);
            -ms-transform: rotate(150deg);
                transform: rotate(150deg);
    }
    .sk-fading-circle .sk-circle7 {
        -webkit-transform: rotate(180deg);
            -ms-transform: rotate(180deg);
                transform: rotate(180deg);
    }
    .sk-fading-circle .sk-circle8 {
        -webkit-transform: rotate(210deg);
            -ms-transform: rotate(210deg);
                transform: rotate(210deg);
    }
    .sk-fading-circle .sk-circle9 {
        -webkit-transform: rotate(240deg);
            -ms-transform: rotate(240deg);
                transform: rotate(240deg);
    }
    .sk-fading-circle .sk-circle10 {
        -webkit-transform: rotate(270deg);
            -ms-transform: rotate(270deg);
                transform: rotate(270deg);
    }
    .sk-fading-circle .sk-circle11 {
        -webkit-transform: rotate(300deg);
            -ms-transform: rotate(300deg);
                transform: rotate(300deg); 
    }
    .sk-fading-circle .sk-circle12 {
        -webkit-transform: rotate(330deg);
            -ms-transform: rotate(330deg);
                transform: rotate(330deg); 
    }
    .sk-fading-circle .sk-circle2:before {
        -webkit-animation-delay: -1.1s;
                animation-delay: -1.1s; 
    }
    .sk-fading-circle .sk-circle3:before {
        -webkit-animation-delay: -1s;
                animation-delay: -1s; 
    }
    .sk-fading-circle .sk-circle4:before {
        -webkit-animation-delay: -0.9s;
                animation-delay: -0.9s; 
    }
    .sk-fading-circle .sk-circle5:before {
        -webkit-animation-delay: -0.8s;
                animation-delay: -0.8s; 
    }
    .sk-fading-circle .sk-circle6:before {
        -webkit-animation-delay: -0.7s;
                animation-delay: -0.7s; 
    }
    .sk-fading-circle .sk-circle7:before {
        -webkit-animation-delay: -0.6s;
                animation-delay: -0.6s; 
    }
    .sk-fading-circle .sk-circle8:before {
        -webkit-animation-delay: -0.5s;
                animation-delay: -0.5s; 
    }
    .sk-fading-circle .sk-circle9:before {
        -webkit-animation-delay: -0.4s;
                animation-delay: -0.4s;
    }
    .sk-fading-circle .sk-circle10:before {
        -webkit-animation-delay: -0.3s;
                animation-delay: -0.3s;
    }
    .sk-fading-circle .sk-circle11:before {
        -webkit-animation-delay: -0.2s;
                animation-delay: -0.2s;
    }
    .sk-fading-circle .sk-circle12:before {
        -webkit-animation-delay: -0.1s;
                animation-delay: -0.1s;
    }

    @-webkit-keyframes sk-circleFadeDelay {
        0%, 39%, 100% { opacity: 0; }
        40% { opacity: 1; }
    }

    @keyframes sk-circleFadeDelay {
        0%, 39%, 100% { opacity: 0; }
        40% { opacity: 1; } 
    }
</style>

<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->