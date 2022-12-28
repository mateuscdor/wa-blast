<div class="card widget widget-stats-large">
    <div class="row">
        <div class="col-xl-8">
            <div class="widget-stats-large-chart-container">
                <div class="card-header logoutbutton"></div>
                <div class="card-body">
                    <div id="apex-earnings"></div>
                    <div class="imageee text-center">
                        @if(!$hasAccess)
                        <img src="{{asset('images/other/expired.png')}}" height="300px" alt="No access">
                        @else
                        <img src="{{asset('images/other/waiting.jpg')}}" height="300px" alt="Waiting">
                        @endif
                    </div>
                    <div class="statusss text-center">
                        @if(!$hasAccess)
                        <button class="btn btn-danger   " type="button" disabled>
                            Your subscription expired. Please renew your subscription.
                        </button>
                        @else
                        <button class="btn btn-primary" type="button" disabled>
                            <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                            Connecting to Node server...
                        </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="widget-stats-large-info-container">
                <div class="card-header">
                    <h5 class="card-title">Whatsapp Info<span class="badge badge-info badge-style-light">Updated 5 min ago</span></h5>
                </div>
                <div class="card-body account">
                    <ul class="list-group account list-group-flush">
                        <li class="list-group-item name">Nama: </li>
                        <li class="list-group-item number">Nomor: </li>
                        <li class="list-group-item device">Device: </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js" integrity="sha384-fKnu0iswBIqkjxrhQCTZ7qlLHOFEgNkRmK2vaO/LbTZSXdJfAu6ewRBdwHPhBo/H" crossorigin="anonymous"></script>
    <script>
        // if subscription not expired
        const is_expired_subscription = '{{!$hasAccess}}';
        if(!is_expired_subscription){
            console.log('Subscribed!');

            let socket;
            let device = '{{$deviceNumber}}';

            if('{{env('TYPE_SERVER')}}' === 'hosting') {
                socket = io();
            } else {
                socket = io.connect('{{env('WA_URL_SERVER')}}', {
                    transports: ['websocket', 'polling', 'flashsocket']
                });
            }

            socket.emit('StartConnection', device)
            socket.on('qrcode', ({token,data,message}) => {
                if(token === device ) {

                    let url = data
                    $('.imageee').html(` <img src="${url}" height="300px" alt="">`)
                    let count = 0;

                    $('.statusss').html(
                        `<button class="btn btn-warning" type="button" disabled>
                            <span class="" role="status" aria-hidden="true"></span>
                            ${message}
                        </button>`
                    );

                }
            })
            socket.on('connection-open',({token,user,ppUrl}) => {
                if(token === device ) {

                    $('.name').html(`Name: ${user.name}`)
                    $('.number').html(`Number: ${user.id}`)
                    $('.device').html(`Device / Token: Not detected - ${token}`)
                    $('.imageee').html(` <img src="${ppUrl}" height="300px" alt="">`)
                    $('.statusss').html(`  <button class="btn btn-success" type="button" disabled>
                                                    <span class="" role="status" aria-hidden="true"></span>
                                                   Connected
                                                </button>`)
                    $('.logoutbutton').html(` <button class="btn btn-danger" class="logout"  id="logout"  onclick="logout({{$number->body}})">
                                                   Logout
                                               </button>`)
                }
            })

            socket.on('Unauthorized',({token})=> {
                if(token === device ) {
                    $('.statusss').html(`<button class="btn btn-danger" type="button" disabled>
                                                    <span class="" role="status" aria-hidden="true"></span>
                                                   Unauthorized
                                                </button>`)
                }

            })
            socket.on('message',({token,message})=> {
                if(token === device ) {
                    $('.statusss').html(`<button class="btn btn-success" type="button" disabled>
                                                    <span class="" role="status" aria-hidden="true"></span>
                                                   ${message}
                                                </button>`)
                    // interval 3 second and reload
                    // setTimeout(() => {
                    //     location.reload();
                    // }, 3000);
                }
            })

            socket.on('reload-qr', ({token, message}) => {
                if(token === device){
                    $('.statusss').html(
                        `<button class="btn btn-success" type="button" disabled>
                            <span class="" role="status" aria-hidden="true"></span>
                            Recreating new QR Image...
                        </button>`
                    );
                    $('.imageee').html('<img src="{{asset('images/other/waiting.jpg')}}" height="300px" alt="Waiting">');
                    socket.emit('StartConnection', device);
                }
            });


            function logout(device){
                socket.emit('LogoutDevice',device)
            }
        }

    </script>
@endpush