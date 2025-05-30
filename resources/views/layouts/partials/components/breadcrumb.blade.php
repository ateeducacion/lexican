<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  @foreach($breadcrumbs as $key => $item)
          <li class="breadcrumb-item {{$item['class'] ?? ''}}" 
          @if( !empty($item['class']) && $item['class'] == 'active')
              aria-current="page"
          @endif >
          @unless( !empty($item['class']) && $item['class'] == 'active')
            <a href="{{$item['url']}}">{{$item['name']}}</a></li>
          @else
            {{$item['name']}}
          @endunless
  @endforeach
  </ol>
</nav>