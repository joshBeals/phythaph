<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Customer Information</strong></div>
            <div class="card-body">
                <div class="row">
                    <table class="table table-striped table-hover table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Customer Name</strong></td>
                                <td>{{ $data->first_name }} {{ $data->last_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td>{{ $data->email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Account Type</strong></td>
                                <td>{{ Helper::titleCase($data->account_type) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Phone</strong></td>
                                <td>{{ $data->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gender</strong></td>
                                <td>{{ Helper::titleCase($data->gender ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Birthday</strong></td>
                                <td>{{ $data->birthday ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>