<div class=" text-center ">
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="img-fluid img-thumbnail rounded my-3">
    @if (backpack_user()->can('upload_customer_avatar'))
    <button class="btn btn-primary " id="upload-avatar">
        <span class="la  la-cloud-upload"></span> Upload/Replace Photo
    </button>
    <div class="text-center hidden loader-wrap">
        <img src="" alt="" class="loader">
    </div>

    <form action="" id="uploader-form">
        <input accept="image/*" type="file" name="file" id="uploader"
            style="position:absolute;top:-1000px;right:-2000px">
    </form>
    @endif
</div>
@push('after_scripts')
@if (backpack_user()->can('upload_customer_avatar'))
<script>
$(document).ready(function() {
    $("img.loader").attr('src', CamelCase.loaderImg);
    $("#upload-avatar").on('click', function() {
        console.log("Clicked")
        $("#uploader").trigger('click');
    });

    $("#uploader").on('change', function() {
        var formData = new FormData($("#uploader-form")[0]);

        $(".loader-wrap").removeClass('hidden');
        $("#upload-avatar").addClass('hidden');
        var timeout = 4000

        axios.post('/api/avatar/{{ $user->id }}', formData)
            .then(function(data) {
                BA.Notify.success("Profile Photo Uploaded");
                timeout = 100
            })
            .catch(function(err) {
                console.log(err);
                BA.Notify.error(
                    "There was a problem uploading the profile photo, please try again later"
                );
            }).then(function() {
                setTimeout(function() {
                    location.reload()
                }, timeout);
            })
    });
});
</script>
@endif
@endpush