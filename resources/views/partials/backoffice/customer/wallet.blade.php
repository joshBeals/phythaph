<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Wallet Balance</div>
            <div class="card-body">
                <h2>{{ ($user->walletBalance) }}</h2>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <strong>Wallet Transactions History</strong>
            </div>
            @if ($walletHistory->isEmpty())
            <div class="card-body">
                <div class="alert alert-info">
                    No Transaction Found
                </div>
            </div>
            @else
            <div class="card-body p-0">
                <div class="table4 p-25 bg-white mb-30">
                    <div class="table-responsive">
                        <table class="table mb-0" id="table">
                            <thead class="">
                                <tr>
                                    <th scope="col"> <span class="userDatatable-header"> Description</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Type</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Reference</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Amount</span></th>
                                    <th scope="col"> <span class="userDatatable-header"> Date</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($walletHistory as $w)
                                    <tr>
                                        <td class="userDatatable-content">{{$w->description}}</td>
                                        <td class="userDatatable-content">{{$w->type}}</td>
                                        <td class="userDatatable-content"><code>{{ $w->transaction->reference ?? '-' }}</code></td>
                                        <td class="userDatatable-content">{{Helper::formatToCurrency($w->amount / 100)}}</td>
                                        <td class="userDatatable-content">{{ $w->created_at ? $w->created_at->diffForHumans(): '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <strong>Wallet Transactions</strong>
            </div>
            <div class="card-body">
                <form id="topup" action="fund" method="POST">
                    @csrf
                    <input type="hidden" id="user_id" name="user_id" value="{{$user->id}}">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" min="1" class="form-control" name="amount" id="amount"
                            value="100" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="w-100 btn btn-success btn-md">
                                Topup Wallet
                            </button>
                        </div>
                        <div class="col-md-6">
                            <div id="withdraw" class="w-100 btn btn-danger btn-md">
                                Withdraw Funds
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        @include('partials.backoffice.spinner', ['hidden' => true])
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>

    $(function () {

        $('#withdraw').on('click', function(e) {
            amount = $('#amount').val();
            user = $('#user_id').val();
            if(amount >= 100){
              location.href = `/admin/customer/${user}/withdraw/${amount}`;
            }else{
                alert('Minimum withdrawal amount is 100');
            }
        })

        // $('#topup').on('submit', function(e) {
        //     e.preventDefault();
            
        //     amount_temp = $('#amount').val();

        //     if (!amount_temp) return;

        //     var 
        //         type = 'wallet_topup',
        //         scope = 'wallet_topup',
        //         paymentMethod = 'Paystack',
        //         publicKey = "{{ config('paystack.publicKey') }}",
        //         amount = parseFloat(amount_temp);
        //         handleError = function() {
        //             alert("Cannot initialize transaction, please try again later.");
        //             $('#spinner').hide();
        //         }
                
        //     // if(coupon_code) {
        //     //     amount = amount / 2
        //     // }

            
        //     $('#spinner').show();

        //     var posting = $.post( '/api/transaction/initialize', {
        //         amount: amount,
        //         scope: scope,
        //         type: type,
        //         description: "Wallet Topup",
        //         /* beautify preserve:start */
        //             user_id: {{$user->id}},
        //             /* beautify preserve:end */
        //     });
 
        //     // Put the results in a div
        //     posting.done(function( data ) {
        //         var resData = data
        //         if (!resData.success) {
        //             handleError();
        //             return;
        //         }
        //         var data = resData.data;
        //         var redirectTo = location.href;

        //         var mergedData = Object.assign({
        //                 /* beautify preserve:start */
        //             full_name: "{{$user->name}}",
        //             email: "{{$user->email}}",
        //             /* beautify preserve:end */
        //             },
        //             JSON.parse(data.payload),
        //             data
        //         ),
        //         metadata = [{
        //                 display_name: "Customer Name",
        //                 variable_name: "customer_name",
        //                 value: "{{$user->name}}"
        //             },
        //             {
        //                 display_name: "Customer ID",
        //                 variable_name: "customer_id",
        //                 value: "{{$user->id}}"
        //             },
        //             {
        //                 display_name: "Transaction Scope",
        //                 variable_name: "scope",
        //                 value: "Wallet Topup"
        //             },

        //         ];

        //         var paystack = function payWithPaystack() {
        //             var handler = PaystackPop.setup({
        //                 key: publicKey, // Replace with your public key
        //                 firstname: mergedData.full_name,
        //                 email: mergedData.email,
        //                 amount: mergedData.amount,
        //                 ref: mergedData.reference,
        //                 currency: "NGN",
        //                 metadata: Object.assign({
        //                     transaction_id: mergedData.id,
        //                     scope: mergedData.scope || "payment",
        //                     custom_fields: metadata || []
        //                 }),
        //                 callback: function(response) {
        //                     alert('Transaction Successfull');
        //                     // Make an AJAX call to your server with the reference to verify the transaction
        //                     $('#spinner').hide();
        //                 },
        //                 onClose: function() {
        //                     alert('Transaction was not completed, window closed.');
        //                 $('#spinner').hide();
        //                 },
        //             });
        //             handler.openIframe();
        //         };

        //         paystack();
        //     });

        //     posting.fail(function(xhr, status, error) {
        //         handleError();
        //     });

        // })
    })

</script>