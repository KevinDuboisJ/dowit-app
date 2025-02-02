@if(!empty($getState()))
    @foreach($getState() as $variable => $value)
        <p class='fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white'>
            {{$value['name']}}@if(!$loop->last),@endif&nbsp;
        </p>
    @endforeach
@endif