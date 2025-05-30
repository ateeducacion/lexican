@if(isset($success))
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