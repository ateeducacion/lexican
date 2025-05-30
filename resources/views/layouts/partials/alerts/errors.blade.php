@if(isset($errors))
<div class="alert alert-danger">
    <ul>
        @foreach($errors->all() as $msg)
            <h4>
                <li>{{ $msg }}</li>
            </h4>
        @endforeach
    </ul>
</div>
@endif