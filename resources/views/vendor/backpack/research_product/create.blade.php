@extends(backpack_view('blank'))

@php

$categories = \App\Models\Category::get();

  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.add') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
	<section class="container-fluid">
	  <h2>
        <span class="text-capitalize">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</span>
        <small>{!! $crud->getSubheading() ?? trans('backpack::crud.add').' '.$crud->entity_name !!}.</small>

        @if ($crud->hasAccess('list'))
          <small><a href="{{ url($crud->route) }}" class="d-print-none font-sm"><i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
        @endif
	  </h2>
	</section>
@endsection

@section('content')

<div class="row">
	<div class="{{ $crud->getCreateContentClass() }}">
		<!-- Default box -->

		@include('crud::inc.grouped_errors')
  		<div class="mt-4"></div>
		<form id="myform" method="post"
			action="{{ url($crud->route) }}"
			@if ($crud->hasUploadFields('create'))
			enctype="multipart/form-data"
			@endif
			>
			{!! csrf_field() !!}
  			<div class="bg-white p-3">
				<div class="form-group">
					<label for="category">Product Category</label>
					<select id="category" name="category_id" class="form-control" required>
						<option value="">Select Category</option>	
						@foreach($categories as $category)
							<option value="{{ $category->id }}">{{ $category->name }}</option>
						@endforeach
					</select>
					<input type="hidden" name="features" id="features">
					<div id="formFields" class="row mt-3"></div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<label for="">Market Price (New)</label>
						<input type="text" name="market_price_new" class="form-control">
					</div>
					<div class="form-group col-md-6">
						<label for="">Market Price (Imported)</label>
						<input type="text" name="market_price_imported" class="form-control">
					</div>
					<div class="form-group col-md-6">
						<label for="">Market Price (Locally Pre-Owned)</label>
						<input type="text" name="market_price_local" class="form-control">
					</div>
					<div class="form-group col-md-6">
						<label for="">Market Price (Buyback)</label>
						<input type="text" name="market_price_computer_village" class="form-control">
					</div>
				</div>
			</div>
			@include('crud::inc.form_save_buttons')
		</form>
	</div>
</div>

@endsection

@push('after_scripts')
<script>

var categories = @json($categories);


(function($) {
    var categoryDropdown = $("#category"),
	form = $("#myform"),
	formFields = $("#formFields"),
	fields = $(".field");

	function generateForm() {

		formFields.html('');
		
		categoryId = categoryDropdown.val();

		if (!categoryId) return;

		category = categories.find(function(i) {
			return i.id === parseInt(categoryId);
		});

		if(category.requirements.length > 0){
			var temp = '';
			category.requirements.forEach(function(requirement){
				if(requirement.field == 'dropdown'){
					var options = requirement?.options?.split(',');
					var op_temp = '';
					options?.forEach(function(op){
						op_temp += `<option value='${op}'>${op}</option>`;
					});
					temp += `
						<div class="form-group col-md-6">
							<label>${requirement.name}</label>
							<select type="text" id='${requirement.name}' class="form-control field" required>
								${op_temp}
							</select>
						</div>
					`;
				}else{
					temp += `
						<div class="form-group col-md-6">
							<label>${requirement.name}</label>
							<input type="text" id='${requirement.name}' class="form-control field" required>
						</div>
					`;
				}
			});
			formFields.html(temp);
		}
	}

    $(function() {
        categoryDropdown.on('change', generateForm);
		$('.btn-success').on('click', function(e) {
			e.preventDefault();
			var error = 0;
			if(categoryDropdown.val()){
				var item = {};
				formFields.find('input').each(function(){
					item [`${this.id}`] = this.value;
				});
				formFields?.find('select').each(function(){
					item [`${this.id}`] = this.value;
				});
				$("#features").val(JSON.stringify(item));

				form.submit();
			}
		});
    })

})(jQuery)

</script>
@endpush
