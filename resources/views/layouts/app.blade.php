<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="{{$keywords ?? ''}}">
    <meta name="description" content="{{$description ?? ''}}">
    <title>{{$user ? $user['nick_name'].' - ' : ''}}{{$title ?? '战国MeEdu'}}</title>
    <link crossorigin="anonymous" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN"
          href="https://lib.baomitu.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('/frontend/css/frontend.css')}}">
    <script src="{{asset('frontend/js/frontend.js')}}"></script>
    <script src="http://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
    @yield('css')
</head>
<body class="bg-f6">

<div class="container-fluid nav-box bg-fff">
    <div class="row">
        <div class="col-sm-12">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <nav class="navbar navbar-expand-lg bg-fff">
                            <div class="navbar-brand" style="display: flex">
                                <img src="{{$gConfig['system']['logo']}}" height="40" alt="{{config('app.name')}}">
                                <form class="form-inline ml-4" method="get" action="{{route('search')}}" style="float:right">
                                    @csrf
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="keywords" placeholder="搜索"
                                               required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </nav>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<main>
    @yield('content')
</main>

<div class="container-fluid nav-box bg-fff">
    <div class="row">
        <div class="col-sm-12">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <nav class="navbar fixed-bottom navbar-expand-lg bg-fff">
                            <div class="container">
                                <div class="navbar-brand" style="display: flex;justify-content: center;width:100%;text-align: center">
                                    <a class="nav-link" href="{{url('/')}}" style="width:25%" >首页 <span
                                                class="sr-only">(current)</span></a>
                                    <a class="nav-link" href="{{route('courses')}}" style="width:25%"  >课程</a>
                                    <a class="nav-link" href="{{route('role.index')}}" style="width:25%" >订阅</a>
                                    @if(!$user)
                                        <!--<div class="nav-link dropup" style="width:25%" >
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="{{route('login')}}">登录</a>
                                                <a class="dropdown-item" href="{{route('register')}}">注册</a>
                                            </div>
                                            <a class="dropdown-toggle" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                我的
                                            </a>
                                        </div>-->
                                        <a id="weixinweb" class="nav-link" href="{{route('socialite', 'weixinweb')}}" style="width:25%" >我的</a>
                                        <a id="weixinphone" class="nav-link" href="{{route('socialite', 'weixinphone')}}" style="width:25%" >我的</a>
                                    @else
                                        <div class="nav-link dropup" style="width:25%" >
                                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                <a class="dropdown-item" href="{{route('member')}}">会员中心</a>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="event.preventDefault();
                                                                 document.getElementById('logout-form').submit();">安全退出</a>
                                                <form class="d-none" id="logout-form" action="{{ route('logout') }}"
                                                      method="POST"
                                                      style="display: none;">
                                                    @csrf
                                                </form>
                                            </div>
                                            <a class="dropdown-toggle"
                                               id="navbarDropdown"
                                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{$user['nick_name']}}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </nav>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('footer')
    <footer class="container-fluid footer-box py-3">
        <div class="row">
            <div class="col-sm-12">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-12">
                        <span>
                            © {{date('Y')}} {{config('app.name')}} · <a href="http://www.beian.miit.gov.cn" class="c-2"
                                                                        target="_blank">{{$gConfig['system']['icp']}}</a>
                        </span>
                            <span class="float-right">PowerBy <a href="https://meedu.vip" class="c-2" target="_blank">MeEdu</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
@show

<script>
    @if(get_first_flash('success'))
    flashSuccess("{{get_first_flash('success')}}");
    @endif
    @if(get_first_flash('warning'))
    flashWarning("{{get_first_flash('warning')}}");
    @endif
    @if(get_first_flash('error'))
    flashError("{{get_first_flash('error')}}");
    @endif
    let viewer = window.navigator.userAgent.toLowerCase();
    if(viewer.match(/MicroMessenger/i) == 'micromessenger'){
        $("#weixinweb").hide();
        $("#weixinphone").show();
    }else{
        $("#weixinweb").show();
        $("#weixinphone").hide();
    }
</script>
@yield('js')
<div style="display:none">{!! config('meedu.system.js') !!}</div>
</body>
</html>