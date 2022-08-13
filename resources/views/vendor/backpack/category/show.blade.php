@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
$crud->entity_name_plural => url($crud->route),
trans('backpack::crud.preview') => false,
];

$data = $crud->entry;

// if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
<section class="container-fluid d-print-none">
    <a href="javascript: window.print();" class="btn float-right"><i class="la la-print"></i></a>
    <h2>
        <span class="text-capitalize">{!! $crud->getHeading() ?? $crud->entity_name_plural !!} </span>
        <small>{{$crud->entry->id}}.</small>
        @if ($crud->hasAccess('list'))
        <small class=""><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i>
                {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
        @endif
    </h2>
</section>
@endsection

@section('content')
<div class="row mt-5">
    <div class="col-6">
        <div class="card">
            <div class="card-header">Category Information</div>
            <div class="card-body">
                <div class="row">
                    <table class="table table-striped table-hover table-bordered">
                        <tbody>
                            <tr>
                                <td>Category Name</td>
                                <td><strong>{{ $data->name }}</strong></td>
                            </tr>
                            <tr>
                                <td>Category Type</td>
                                <td><strong>{{ Helper::titleCase($data->type) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Category Description</td>
                                <td><strong>{{ $data->description ?? '-' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card">
            <div class="card-header">Category Requirements</div>
            <div class="card-body">
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Field</th>
                            <th scope="col">Options</th>
                        </tr>
                    </thead>
                    <tbody>
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
                    </tbody>
                </table>
                
            </div>
        </div>
    </div>

</div>
@endsection


@section('after_styles')
<link rel="stylesheet" href="{{ asset('css/dropzone.min.css').'?v='.config('backpack.base.cachebusting_string') }}">
<link rel="stylesheet"
    href="{{ asset('packages/backpack/crud/css/crud.css').'?v='.config('backpack.base.cachebusting_string') }}">
<link rel="stylesheet"
    href="{{ asset('packages/backpack/crud/css/show.css').'?v='.config('backpack.base.cachebusting_string') }}">
@endsection

@push('before_scripts')
<script src="{{ asset('js/dropzone.min.js').'?v='.config('backpack.base.cachebusting_string') }}">
</script>
@endpush

@section('after_scripts')
<script src="{{ asset('packages/backpack/crud/js/crud.js').'?v='.config('backpack.base.cachebusting_string') }}">
</script>
<script src="{{ asset('packages/backpack/crud/js/show.js').'?v='.config('backpack.base.cachebusting_string') }}">
</script>
@endsection
