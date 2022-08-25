@php
$top_level = $top_level ?? false;
@endphp
<div class="tab-container mb-2 @if(!$top_level)tab-pane @endif" @if(!$top_level) role="tabpanel"
    aria-labelledby="customer-tab" @endif>
    <div class="nav-tabs-custom row" id="form_tabs">

        @if(!$top_level)
        <div class="col-md-3">
            @else
            <div class="col-md-12">
                @endif

                <ul class="nav @if(!$top_level) nav-pills nav-stacked flex-column @else nav-tabs @endif "
                    role="tablist">
                    <li role="presentation" class="nav-item">
                        <a href="#tab_item-info" aria-controls="tab_item-info" role="tab"
                            tab_name="item-info" data-toggle="tab" class="nav-link active">Pawn Item Info</a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a href="#tab_score" aria-controls="tab_score" role="tab"
                            tab_name="score" data-toggle="tab" class="nav-link ">Score Item</a>
                    </li>
                </ul>
                @if(!$top_level)
            </div>
            <div class="col-md-12">
                @endif
                <div class="tab-content p-0 col-12">
                    <div role="tabpanel" class="tab-pane  active" id="tab_item-info">
                        @include('partials.backoffice.pawn.pawn_item', ['data' => $data])
                    </div>
                    <div role="tabpanel" class="tab-pane " id="tab_score">
                        @include('partials.backoffice.pawn.score', ['data' => $data])
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
    label {
        font-weight: bold;
    }
    </style>