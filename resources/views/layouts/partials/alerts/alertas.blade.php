<div id="alertas">

    {{-- Avisos y errores --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <h4>
                        <li>{!! $error !!}</li>
                    </h4>
                @endforeach
            </ul>
        </div>
    @endif
    @if(isset($errores))
        @if($info->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($info->all() as $msg)
                        <h4>
                            <li>{{ $msg }}</li>
                        </h4>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
    @if(isset($info ))
        @if($info->any())
            <div class="alert alert-info">
                <ul>
                    @foreach($info->all() as $msg)
                        <h4>
                            <li>{{ $msg }}</li>
                        </h4>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
    @if(isset($success ))
        @if($info->any())
            <!-- success any -->
            <div class="alert alert-success">
                <ul>
                    @foreach($success->all() as $msg)
                        <h4>
                            <li>{{ $msg }}</li>
                        </h4>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
    @if(\Session::has('successArray') && count(\Session::get('successArray'))>0 )
        <!-- success array -->
        <div class="alert alert-success">
            <ul>
                @foreach(\Session::get('successArray') as $mensaje)
                    <h4>
                        <li>{{ $mensaje }}</li>
                    </h4>
                @endforeach
            </ul>
        </div>
    @endif
    @if(\Session::has('success'))
        <!-- success session -->
        <div class="alert alert-success">
            <ul>
                <h4>
                    @if( is_array( \Session::get('success') ))
                        @foreach( \Session::get('success')  as $msg )                        
                            <li>{{ $msg }}</li>
                        @endforeach
                    @else
                        <li>{{ \Session::get('success') }}</li>
                    @endif
                </h4>
            </ul>
        </div>
    @endif
    @if(\Session::has('errores'))
        <div class="alert alert-danger">
            <ul>
                <h4>
                    @if( is_array( \Session::get('errores') ))
                        @foreach( \Session::get('errores') as $msg )                        
                            <li>{{ $msg }}</li>
                        @endforeach
                    @else
                        <li>{{ \Session::get('errores') }}</li>
                    @endif
                </h4>
            </ul>
        </div>
    @endif
</div>