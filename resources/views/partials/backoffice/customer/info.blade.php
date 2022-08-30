@php
$top_level = $top_level ?? false;
$walletHistory = \App\Models\UserWalletBalanceHistory::where('user_id', $data->id)->get();
$user = $data->decorate();
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
                        <a href="#tab_customer-info" aria-controls="tab_customer-info" role="tab"
                            tab_name="customer-info" data-toggle="tab" class="nav-link active">Customer Info</a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a href="#tab_wallet" aria-controls="tab_wallet" role="tab"
                            tab_name="wallet" data-toggle="tab" class="nav-link ">Wallet</a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a href="#tab_subscription" aria-controls="tab_subscription" role="tab"
                            tab_name="subscription" data-toggle="tab" class="nav-link ">Subscription</a>
                    </li>
                </ul>
                @if(!$top_level)
            </div>
            <div class="col-md-12">
                @endif
                <div class="tab-content p-0 col-12">
                    <div role="tabpanel" class="tab-pane  active" id="tab_customer-info">
                        @include('partials.backoffice.customer.customer_data', ['data' => $data])
                    </div>
                    <div role="tabpanel" class="tab-pane " id="tab_wallet">
                        @include('partials.backoffice.customer.wallet', ['walletHistory' => $walletHistory, 'user' => $user])
                    </div>
                    <div role="tabpanel" class="tab-pane " id="tab_subscription">
                        @include('partials.backoffice.customer.subscription', ['user' => $user])
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