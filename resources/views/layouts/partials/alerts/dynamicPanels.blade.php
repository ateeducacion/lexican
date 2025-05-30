@if ($type == 'success' || $type == 'both') 
<div data-panel="success">
    <div>
        <p>{{$bodySuccess}}</p>
    </div>
</div>
@endif
@if ($type == 'error' || $type == 'both') 
<div data-panel="error">
    <div>
        <p>{{$bodyError}}</p>
    </div>
</div>
@endif