
@php
    $color = Helper::statusColorCode($entry->{$column['name']});
@endphp

<span class="badge badge-{{ $color }}">
{{ Str::upper( str_replace("_", " ", $entry->{$column['name']} )) }}
</span>
