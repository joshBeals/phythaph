@extends(backpack_view('blank'))

@php

$categories = \App\Models\Category::get();
$users = \App\Models\User::get();

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
  			<div class="card">
				<div class="card-header">
					<div class="d-flex justify-content-between">
						<label for="sell"></label>
						<button type="button" style="display: none;" id="modal_btn" class="btn btn-primary" onclick="$('#myModal').show()">
							Add Option
						</button>
					</div>
				</div>
				<div class="card-body">
					<form id="myform" method="post"
						action="{{ url($crud->route) }}"
						@if ($crud->hasUploadFields('create'))
						enctype="multipart/form-data"
						@endif
					>
						{!! csrf_field() !!}
						<div class="row bg-white p-3">
							<div class="form-group col-md-6">
								<label for="category">Customer</label>
								<select id="user" name="user_id" class="form-control select2" required>
									<option value="">Select Customer</option>	
									@foreach($users as $user)
										<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
									@endforeach
								</select>
							</div>
							<div class="form-group col-md-6">
								<label for="category">Category</label>
								<select id="category" name="category_id" class="form-control select2" required>
									<option value="">Select Category</option>	
									@foreach($categories as $category)
										<option value="{{ $category->id }}">{{ $category->name }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-12 mt-3">
								<strong>ITEM FEATURES</strong>
								<input type="hidden" name="item_features" id="features">
								<div id="formFields" class="row mt-3"></div>
							</div>
							<div class="col-12 mt-3">
								<strong>PRICING / INSPECTION</strong>
								<div class="row">
									<div class="form-group col-md-6">
										<label for="category">Type</label>
										<select name="type" class="form-control" required>
											<option value="">-</option>
											<option value="new">New</option>
											<option value="pre-owned">Pre-Owned</option>
										</select>
									</div>
									<div class="form-group col-md-6">
										<label for="category">Price</label>
										<input type="number" name="price" class="form-control" required>
									</div>
									<div class="form-group col-md-6">
										<label for="category">Inspection Type</label>
										<select name="inspection_type" class="form-control" required>
											<option value="">-</option>
											<option value="home">Home</option>
											<option value="office">Office</option>
										</select>
									</div>
									<div class="form-group col-md-6">
										<label for="category">Inspection Date</label>
										<input type="date" name="inspection_date" class="form-control" required>
									</div>
								</div>
							</div>
						</div>
						<div class="mt-4"></div>
						@include('crud::inc.form_save_buttons')
					</form>
				</div>
			</div>
			<div class="mt-4"></div>
	</div>
</div>

<div class="myModal" id="myModal" style="display: none;">
	<div class="inner" id="inner">
		<div class="card">
			<div class="card-header">Add Option</div>
			<div class="card-body">
  				<form action="/api/option" method="POST">
					<input type="hidden" id="category_id" name="category_id">
					<div class="form-group">
						<label for="options">Requirement</label>
						<select name="requirement" id="options" class="form-control" required></select>
					</div>
					<div class="form-group">
						<label for="options">Option To Add</label>
						<input name="option" class="form-control" required>
					</div>
					<div class="mt-3">
						<button type="submit" class="btn btn-primary" data-dismiss="modal">Submit</button>
						<div onclick="$('#myModal').hide()" class="btn btn-danger" data-dismiss="modal">Close</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<style>
	body{
		position: relative;
	}
	.myModal{
		position: absolute;
		top: 0;
		left: 0;
		min-height: 100vh;
		width: 100vw;
		z-index: 25000;
		background: rgba(0,0,0,.5);
		display: flex;
		justify-content: center;
		align-items: flex-start;
		animation: load 300ms ease-in-out;
	}
	.inner{
		width: 30%;
		margin: 20px;
		padding: 20px;
		border-radius: 10px;
		background: white;
	}
	@keyframes load{
		0%{
			opacity: 0;
		}100%{
			opacity: 1;
		}
	}
	@media screen and (max-width: 800px) {
		.inner{
			margin: 5px;
			width: 50%;
		}
	}
</style>
@endsection

@push('after_scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>

var categories = @json($categories);
var users = @json($users);


(function($) {
    var categoryDropdown = $("#category"),
	userDropdown = $("#user"),
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
		
		$('#modal_btn').show();
		$('#category_id').val(categoryId);

		if(category?.requirements?.length > 0){
			var temp = ''; 
			var option = "<option value=''>-</option>";
			category?.requirements?.forEach(function(requirement){
				if(requirement?.field == 'dropdown'){
					var options = requirement?.options?.split('|');
					var op_temp = '';
					var opt_test = options?.sort(function(a, b){
						a = typeof a === 'string' ? a.toLowerCase() : a.toString();
						b = typeof b === 'string' ? b.toLowerCase() : b.toString();
						return a.localeCompare(b);
					});
					opt_test?.forEach(function(op){
						op_temp += `<option value='${op}'>${op}</option>`;
					});
					temp += `
						<div class="form-group col-md-6">
							<label>${requirement?.name}</label>
							<select type="text" id='${requirement?.name}' class="form-control select2 field" required>
								<option value=''>-</option>
								${op_temp}
							</select>
						</div>
					`;
					
					option += `<option value='${requirement?.name}'>${requirement?.name}</option>`;
				}else{
					temp += `
						<div class="form-group col-md-6">
							<label>${requirement?.name}</label>
							<input type="text" id='${requirement?.name}' class="form-control field" required>
						</div>
					`;
				}
			});

			formFields.html(temp);
			$('#options').html(option);
			$('.select2').select2();
		}
	}

    $(function() {
		categoryDropdown.select2();
		userDropdown.select2();
		$('#myModal').on('shown.bs.modal', function () {
			$('#myInput').trigger('focus')
		});
        categoryDropdown.on('change', generateForm);
		$('.btn-success').on('click', function(e) {
			e.preventDefault();
			var error = 0;
			if(categoryDropdown.val()){
				var item = {};

				formFields?.find('input').each(function(){
					item[`${this.id}`] = this.value;
				});

				formFields?.find('select').each(function(){
					item[`${this.id}`] = this.value;
				});

				$("#features").val(JSON.stringify(item));

				form.submit();
			}
		});
    })

})(jQuery)

</script>
@endpush
