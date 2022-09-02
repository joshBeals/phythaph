<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Sell Information</strong></div>
            <div class="card-body">
                <div class="row">
                    <input type="hidden" id="sell_id" value="{{$data->id}}">
                    <div class="col-md-4 mb-3">
                        <strong>Customer</strong>
                        <p>{{ $data->user->first_name }} {{ $data->user->last_name }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Category</strong>
                        <p>{{ $data->category->name }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Type</strong>
                        <p>{{ Helper::titleCase($data->type) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Price</strong>
                        <p>{{ Helper::formatToCurrency($data->price) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Inspection Type</strong>
                        <p>{{ Helper::titleCase($data->inspection_type) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Inspection Date</strong>
                        <p>{{ $data->inspection_date }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <strong>Status</strong>
                        <div>@include('partials.backoffice.status', ['value' => $data->status])</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><strong>File Uploads</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <form id="imageUpload">
                                    <div class="form-group">
                                        <label for="file">Upload Image</label>
                                        <input type="file" id="image" accept="image/*" class="form-control" required>
                                    </div>
                                    <button id="img_btn" type="submit" class="btn btn-sm btn-primary">Upload</button>
                                    <span id="img_btn_spin" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                </form>
                                <div class="mt-5">
                                    <div class="row">
                                    @foreach($data->getFiles() as $file)
                                    @if($file->detail[0]->title == 'image')
                                    <div class="col-md-4 mb-2"><img style="width: 100%;" src="{{ $file->detail[0]->file_path }}" alt=""></div>
                                    @endif
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><strong>File Uploads</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <form id="fileUpload">
                                    <div class="form-group">
                                        <label for="file">Upload Proof of Ownership</label>
                                        <input type="file" id="file" accept=".pdf, .doc, .docx" class="form-control" required>
                                    </div>
                                    <button id="file_btn" type="submit" class="btn btn-sm btn-primary">Upload</button>
                                    <span id="file_btn_spin" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                </form>
                                <div class="mt-5">
                                    <table class="table table-striped table-hover table-bordered">
                                        <tbody>
                                            @foreach($data->getFiles() as $file)
                                            @if($file->detail[0]->title == 'file')
                                            <tr>
                                                <td>Proof of Ownership</td>
                                                <td><a href="{{ $file->detail[0]->file_path }}">view</strong></td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Item Features</strong></div>
            <div class="card-body">
                <div class="row">
                    <table class="table table-striped table-hover table-bordered">
                        <tbody>
                            @if($data->item_features)
                            @foreach(json_decode($data->item_features) as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td><strong>{{ $value ?? '-' }}</strong></td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
(function($) {

    var imageUpload = $("#imageUpload"),
	fileUpload = $("#fileUpload");

    imageUpload.on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append("title", "image");
        formData.append("file", $('#image')[0].files[0]);
        $('#img_btn').hide();
        $('#img_btn_spin').show();
        $.ajax({
            url : `/api/sell-upload/${$('#sell_id').val()}`,
            type : 'POST',
            data : formData,
            processData: false,  // tell jQuery not to process the data
            contentType: false,  // tell jQuery not to set contentType
            success : function(data) {
                console.log(data.data);
                alert(data.data);
                $('#image').val('');
                $('#img_btn_spin').hide();
                $('#img_btn').show();
                location.reload();
            },
            error : function(jqXHR, exception) {
                alert('File Not Uploaded');
                $('#image').val('');
                $('#img_btn_spin').hide();
                $('#img_btn').show();
            }
        });
    });

    fileUpload.on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append("title", "file");
        formData.append("file", $('#file')[0].files[0]);
        $('#file_btn').hide();
        $('#file_btn_spin').show();
        $.ajax({
            url : `/api/sell-upload/${$('#sell_id').val()}`,
            type : 'POST',
            data : formData,
            processData: false,  // tell jQuery not to process the data
            contentType: false,  // tell jQuery not to set contentType
            success : function(data) {
                console.log(data.data);
                alert(data.data);
                $('#file').val('');
                $('#file_btn_spin').hide();
                $('#file_btn').show();
                location.reload();
            },
            error : function(jqXHR, exception) {
                alert('File Not Uploaded');
                $('#file').val('');
                $('#file_btn_spin').hide();
                $('#file_btn').show();
            }
        });
    });

})(jQuery)
</script>