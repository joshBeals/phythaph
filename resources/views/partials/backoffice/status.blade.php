@php
    $color = Helper::statusColorCode($value);
@endphp

<span class="badge badge-{{ $color }}">
{{ Str::upper( str_replace("_", " ", $value )) }}
</span>
