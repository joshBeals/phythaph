<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Score Item</strong><span class="ml-3 badge badge-primary">{{ $data->id }}</span></div>
            <div class="card-body">
                @if(!$data->score)
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Variable</th>
                            <th scope="col">Score</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <input type="hidden" id="pawn_id" value="{{$data->id}}">
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
                                        <option value="20">1</option>
                                        <option value="40">2</option>
                                        <option value="60">3</option>
                                        <option value="80">4</option>
                                        <option value="100">5</option>
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
                <button class="btn btn-primary" id="score_btn">Submit</button>
                @else
                <div class="alert alert-info">
                    Item score: {{$data->score}}/100
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
(function($) {

    var tbody = $("#tbody");

    $('#score_btn').on('click', function(e) {
        var temp = 0, score = 0, count = 0;
        tbody?.find('select').each(function(){
            temp += parseInt(this.value);
            count++;
        });
        score = temp/count;
        location.href = `/admin/user-pawns/${$('#pawn_id').val()}/score/${score}`;
    });

})(jQuery)
</script>