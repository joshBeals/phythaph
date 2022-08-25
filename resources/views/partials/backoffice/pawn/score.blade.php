<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Score Item</strong><span class="ml-3 badge badge-primary">{{ $data->id }}</span></div>
            <div class="card-body">
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Variable</th>
                            <th scope="col">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($data->category->checks)
                        @foreach($data->category->checks as $check)
                            <tr>
                                <td>
                                    <label for="">{{$check['variable']}}</label>
                                    <p>{{$check['description']}}</p>
                                </td>
                                <td>
                                    <select class="form-control" name="" id="">
                                        <option value="0">0</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        @endif
                        @if($data->requirements)
                        @foreach($data->requirements as $req)
                            <tr>
                            @foreach($req as $key => $value)
                                @if($value)
                                    <td>{{ Helper::titleCase($value) }}</td>
                                @else
                                    <td>-</td>
                                @endif
                            @endforeach
                            </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
                <button class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
</script>