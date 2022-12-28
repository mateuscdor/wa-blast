<div class="app-header">
    <nav class="navbar navbar-light navbar-expand-lg">
        <div class="container-fluid">
            <div class="navbar-nav" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link hide-sidebar-toggle-button" href="#"><i class="material-icons">first_page</i></a>
                    </li>

                </ul>

            </div>
            <div class="d-flex">

                <ul class="navbar-nav">


                    <li class="nav-item hidden-on-mobile">
                        <a class="nav-link nav-notifications-toggle" id="notificationsDropDown" href="#" data-bs-toggle="dropdown">4</a>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationsDropDown">
                            <form action="{{route('logout')}}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-header h6 " style="border: 0; background-color :white;">Logout</button>
                            </form>
                            {{-- <a href={{route('user.changePassword')}} class="dropdown-header h6" style="border: 0; background-color :white;">Setting</a>
                   --}} </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>
