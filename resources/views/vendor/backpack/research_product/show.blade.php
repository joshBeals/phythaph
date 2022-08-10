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
<div class="row">
    <div class="col-6">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <p class="m-0 p-0">Product Category</p>
                        <p><strong>{{ $data->category->name }}</strong></p>
                    </div>
                    @foreach(json_decode($data->features) as $key => $value)
                    <div class="col-md-6">
                        <p class="m-0 p-0">{{ $key }}</p>
                        <p><strong>{{ $value }}</strong></p>
                    </div>
                    @endforeach
                    <div class="col-md-6">
                        <p class="m-0 p-0">Market Price (New)</p>
                        <p><strong>{{ $data->market_price_new ?? '-' }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <p class="m-0 p-0">Market Price (Imported)</p>
                        <p><strong>{{ $data->market_price_imported ?? '-' }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <p class="m-0 p-0">Market Price (Locally USed)</p>
                        <p><strong>{{ $data->market_price_local ?? '-' }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <p class="m-0 p-0">Market Price (Computer Village)</p>
                        <p><strong>{{ $data->market_price_computer_village ?? '-' }}</strong></p>
                    </div>
                </div>
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
