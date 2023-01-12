<style>
    .app-sidebar .logo .logo-icon {
        background: url({{url(getSystemSettings('logo-icon', '/images/neptune.png'))}});
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;

    }
</style>
<div class="app-sidebar">
    <div class="logo">
        <a href="{{route('home')}}" class="logo-icon">
                <span class="logo-text">
                {{getSystemSettings('logo-title', 'MPWA')}}
            </span>
        </a>
        <div class="sidebar-user-switcher user-activity-online">
            <a href="/">
                <img src="{{asset(getSystemSettings('logo', 'images/avatars/avatar2.png'))}}">
                <span class="activity-indicator"></span>
                <span class="user-info-text">{{ Auth::user()->username}}<br></span>
            </a>
        </div>
    </div>
    <div class="app-menu">
        <ul class="accordion-menu">
            <li class="sidebar-title">
                Apps
            </li>
            <li class="{{request()->is('home') ? 'active-page' : ''}}">
                <a href="{{route('home')}}" class=""><i class="material-icons-two-tone">dashboard</i>{{__('system.home')}}</a>
            </li>
            <li class="{{request()->is('file-manager') ? 'active-page' : ''}}">
                <a href="{{route('file-manager')}}" class=""><i class="material-icons-two-tone">folder</i>{{__('File Manager')}}</a>
            </li>
            @if(hasLiveChatAccess())
                <li class="{{request()->is('livechat') ? 'active-page' : ''}}">
                    <a href="{{route('livechat.lists')}}"><i class="material-icons-two-tone">assessment</i>Live Chat</a>
                </li>
            @endif
            <x-select-device></x-select-device>
            @if(Session::has('selectedDevice'))
                <li class="{{request()->is('autoreply') ? 'active-page' : ''}}">
                    <a href="{{route('autoreply')}}" class=""><i class="material-icons-two-tone">message</i>{{__('system.autoreply')}}</a>
                </li>
                <li class="{{request()->is('autoreply-history') ? 'active-page' : ''}}">
                    <a href="{{route('autoreply-history')}}" class=""><i class="material-icons-two-tone">history</i>{{__('system.autoreply-history')}}</a>
                </li>
                <li class="{{request()->is('tag') ? 'active-page' : ''}}">
                    <a href="{{route('tag')}}"><i class="material-icons-two-tone">contacts</i>Phone Book</a>
                </li>
                <li class="{{request()->is('campaign/create') ? 'active-page' : ''}}">
                    <a href="{{route('campaign.create')}}" class=""><i class="material-icons-two-tone">email</i>Create Campaign</a>
                </li>
                <li class="{{request()->is('campaigns') ? 'active-page' : ''}}">
                    <a href="{{route('campaign.lists')}}" class=""><i class="material-icons-two-tone">history</i>List Campaign</a>
                </li>
                <li class="{{request()->is('message/test') ? 'active-page' : ''}}">
                    <a href="{{route('messagetest')}}" class=""><i class="material-icons-two-tone">note</i>{{__('system.test')}}</a>
                </li>
            @endif
            <li class="{{request()->is('templates') ? 'active-page' : ''}}">
                <a href="{{route('template.lists')}}" class=""><i class="material-icons-two-tone">extension</i>Message Templates</a>
            </li>
            <li class="{{request()->is('rest-api') ? 'active-page' : ''}}">
                <a href="{{route('rest-api')}}"><i class="material-icons-two-tone">api</i>{{__('system.restapi')}}</a>
            </li>
            <li class="{{request()->is('user/change-password') ? 'active-page' : ''}}">
                <a href="{{route('changePassword')}}"><i class="material-icons-two-tone">settings</i>Setting</a>
            </li>

            {{-- <li class="{{request()->is('schedule') ? 'active-page' : ''}}">
                <a href="{{route('scheduleMessage')}}" class=""><i class="material-icons-two-tone">schedule</i>Schedule Message</a>
            </li> --}}
            {{-- only level admin --}}
            @if(Auth::user()->level_id !== \App\Models\Level::LEVEL_CUSTOMER_SERVICE)
                <li class="sidebar-title">
                    Admin Menu
                </li>

                <li class="{{request()->is('admin/manage-user') ? 'active-page' : ''}}">
                    <a href="{{route('admin.manageUser')}}"><i class="material-icons-two-tone">people</i>User Manager</a>
                </li>
            @endif

            @if(Auth::user()->level_id === \App\Models\Level::LEVEL_SUPER_ADMIN)

                <li class="sidebar-title">
                    Super Admin Menu
                </li>

                <li class="{{request()->is('admin/manage-packages') ? 'active-page' : ''}}">
                    <a href="{{route('admin.managePackages')}}"><i class="material-icons-two-tone">book</i>Package Manager</a>
                </li>
                <li class="{{request()->is('settings') ? 'active-page' : ''}}">
                    <a href="{{route('settings')}}"><i class="material-icons-two-tone">settings</i>System Settings</a>
                </li>
            @endif


        </ul>
    </div>
</div>
