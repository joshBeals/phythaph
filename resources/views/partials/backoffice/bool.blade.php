@php

    $color = '';
    switch($value) {
        case '1' :
        case 1:
        case true:
            $color = 'badge-success';
            $value = 'Active';
            break;
        case '0' :
        case 0 :
        case false:
            $color = 'badge-danger';
            $value = 'In Active';
            break;
        default :
            $color = 'badge-info';
            break;
    }
@endphp

<span class="badge {{ $color}}">
{{ strtoupper( $value ) }}
</span>
