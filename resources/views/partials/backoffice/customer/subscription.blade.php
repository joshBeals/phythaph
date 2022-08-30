@php
$plans = \App\Models\SubscriptionPlans::get();
$id = 0;
@endphp

<div class="row">
    <div class="col-md-7">
        @if(!$user->has_valid_subscription)
        <div class="alert alert-info">
            Customer has no subscription yet
        </div>
        @else
        <div class="card border-default">
            <div class="card-header">
                <h4>Subscription Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="m-0 p-0"><strong>Customer Name</strong></p>
                        <p>{{ $user->name }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="m-0 p-0"><strong>Current Plan</strong></p>
                        <p>{{ $user->plan->name }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="m-0 p-0"><strong>Start Date</strong></p>
                        <p>{{ Helper::shortDate($user->subscription->from) }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="m-0 p-0"><strong>End Date</strong></p>
                        <p>{{ Helper::shortDate($user->subscription->to) }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(count($user->subscriptions) == 0)
        <div class="alert alert-info">
            Customer has no subscription history yet
        </div>
        @else
        <div class="card border-default">
            <div class="card-header">
                <h4>Subscription History</h4>
            </div>
            <div class="card-body">
                <div class="table4  p-25 bg-white mb-30">
                    <div class="table-responsive">
                        <table class="table mb-0" id="table">
                            <thead class="">
                                <tr>
                                    <th scope="col"> <span class="userDatatable-header"> S/N</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Plan</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Reference</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Status</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Date</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->subscriptions as $sub)
                                <tr>
                                    <td>{{ ++$id }}</td>
                                    <td>{{ $sub->plan->name }}</td>
                                    <td><code>{{ $sub->transaction->reference }}</code></td>
                                    <td>{{ $sub->transaction->status }}</td>
                                    <td>{{ Helper::shortDate($sub->creared_at) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <div class="col-md-5">
        <div class="card border-default">
            <div class="card-header">
                <strong>@if(!$user->has_valid_subscription) Add @else Change @endif Subscription</strong>
            </div>
            <div class="card-body">
                <form action="update-plan" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{$user->id}}">
                    <div class="form-group">
                        <label>Membership Plans</label>
                        <select class="form-control" name="plan_id" id="plan_id" required>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @if($plan->id == $user->plan_id) selected @endif>
                                {{ $plan->name }}
                                <span>
                                    @if($user->hasSubscribedOnce())
                                    ({{ Helper::formatToCurrency($plan->renewal_fee) }})
                                    @else
                                    ({{ Helper::formatToCurrency($plan->signon_fee) }})
                                    @endif
                                </span>
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Years</label>
                        <input type="number" min="1" class="form-control" name="years" id="years" value="1" required disabled>
                    </div>
                    <div class=" form-group">
                        <label>Start Date</label>
                        <input type="date" name="from" class="form-control" id="from" required>
                    </div>
                    <button class="btn btn-primary mt-3">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>