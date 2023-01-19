<x-layout-auth>
    @slot('title','Login')
    <style>
        .app .app-auth-container .logo a {
            background: none;
            padding-left: 10px;
        }
        .app .app-auth-container .logo {
            display: flex;
            align-items: center;
            gap: 2px;
        }
    </style>
    <div class="app app-auth-sign-in align-content-stretch d-flex flex-wrap justify-content-end">
        <div class="app-auth-background">

        </div>
        <div class="app-auth-container">
            <div class="logo">
                <img src="{{url(getSystemSettings('logo-icon', '/images/neptune.png'))}}" style="width: 50px; height: 50; object-fit: contain">
                <a href="/">{{getSystemSettings('logo-title', 'WAMD')}}</a>
            </div>
           @if (session()->has('alert'))
              <x-alert>
                  @slot('type',session('alert')['type'])
                  @slot('msg',session('alert')['msg'])
              </x-alert>
           @endif
{{--            <p class="auth-description">Not have an account? ? <a href="register">Register</a></p>--}}
            <span class="divider"></span>
            <form action="{{route('login')}}" method="POST">
                @csrf
                <div class="auth-credentials m-b-xxl">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" class="form-control m-b-md" id="username" aria-describedby="username">
    
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password" aria-describedby="password" >
                </div>
    
                <div class="auth-submit">
                    <button type="submit" name="login" class="btn btn-primary">Sign in</button>
                    {{-- <a href="#" class="auth-forgot-password float-end">Forgot password?</a> --}}
                </div>
            </form>
            <div class="divider"></div>
{{--            <div class="auth-alts">--}}
{{--                <a href="#" class="auth-alts-google"></a>--}}
{{--                <a href="#" class="auth-alts-facebook"></a>--}}
{{--                <a href="#" class="auth-alts-twitter"></a>--}}
{{--            </div>--}}
        </div>
    </div>
</x-layout-auth>