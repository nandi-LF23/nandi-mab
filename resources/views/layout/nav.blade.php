<nav class="navbar navbar-expand-md navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/home') }}">
                  
                    <img src="{{ asset('/images/logo.svg') }}" width="150" height="30" alt="" />
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
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                        @can('role-create')
                        <li class="nav-item list-group-item"> <a class="nav-link" href="{{ route('users.index') }}">Manage Users</a></li>
                                
                        <li class="nav-item list-group-item"><a class="nav-link" href="{{ route('roles.index') }}">Manage Roles</a></li>
                        @endcan
                         <li class="nav-item list-group-item"><a class="nav-link" href="/fields">Field Management</a></li>
                        @can('role-create')
                        <li class="nav-item list-group-item">
                            <a class="nav-link" href="{{ route('HardwareConfig') }}">Hardware Config</a>
                        </li>

                        <li class="nav-item list-group-item">
                            <a class="nav-link" href="{{ route('HardwareManagement') }}">Hardware Management</a>
                        </li>
                        
                        
                        @endcan
                        @can('graph-node')

                        <li class="nav-item list-group-item">
                            <a class="nav-link" href="{{ route('SMTable') }}">Soil Moisture</a>
                        </li>

                        <li class="nav-item list-group-item">
                            <a class="nav-link" href="{{ route('WMTable') }}">Wells</a>
                        </li>

                        <li class="nav-item list-group-item">
                            <a class="nav-link" href="{{ route('WMTableV1') }}">Wells (V1)</a>
                        </li>
                        @endcan   

                        <li class="nav-item list-group-item">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
