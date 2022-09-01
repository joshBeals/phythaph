@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
$crud->entity_name_plural => url($crud->route),
trans('backpack::crud.preview') => false,
];

$customer = $data = $crud->entry;

// if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
<section class="container-fluid d-print-none">
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
    <div class="col-md-6">

        <!-- Default box -->
        <div class="">
            @if ($crud->model->translationEnabled())
            <div class="row">
                <div class="col-md-12 mb-2">
                    <!-- Change translation button group -->
                    <div class="btn-group float-right">
                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            {{trans('backpack::crud.language')}}:
                            {{ $crud->model->getAvailableLocales()[request()->input('locale')?request()->input('locale'):App::getLocale()] }}
                            &nbsp; <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($crud->model->getAvailableLocales() as $key => $locale)
                            <a class="dropdown-item"
                                href="{{ url($crud->route.'/'.$entry->getKey().'/show') }}?locale={{ $key }}">{{ $locale }}</a>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            <div class="card mb-25">
                <input type="text" id="id" value="{{$data->id}}" hidden>
                <div class="card-header">
                    <strong class="card-title mb-0">Withdrawal Information</strong>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="pb-2 m-0">Fullname</p>
                            <h5>{{ ucwords($data->user->first_name) }} {{ ucwords($data->user->last_name) }}</h5>
                        </div>
                        <div class="col-md-6">
                            <p class="pb-2 m-0">Status</p>
                            <h5>@include('partials.backoffice.status', ['value' => $data->status])</h5>
                        </div>
                    </div>
                    <div class="row mb-5">

                        <div class="col-md-6 mt-5">
                            <p class="pb-2 m-0">Source</p>
                            <h5 class="text-left" id="">{{ ucwords($data->source )}}</h5>
                        </div>

                        <div class="col-md-6 mt-5">
                            <p class="pb-2 m-0">Amount</p>
                            <h5 class="text-left" id="">{{ Helper::formatToCurrency($data->amount) }}</h5>
                        </div>

                        <div class="col-md-6 mt-5">
                            <p class="pb-2 m-0">Disburse Amount</p>
                            <h5 class="text-left" id="">{{ Helper::formatToCurrency($data->disburse_amount) }}</h5>
                        </div>

                        <div class="col-md-6 mt-5">
                            <p class="pb-2 m-0">Date Requested</p>
                            <h5 class="text-left" id="">{{Helper::shortDate($data->created_at)}}</h5>
                        </div>

                    </div>
                </div>
            </div>
        </div><!-- /.box -->

    </div>

    <div class="col-md-6">

        <div class="card mb-25">
            <div class="card-header">
                <strong class="card-title mb-0">Bank Information</strong>
            </div>
            <div class="card-body p-4">
                @if($data->user->bank)
                <div class="row">
                    <div class="col-md-6">
                        <p class="pb-2 m-0">Bank</p>
                        <h5 class="text-left" id="">{{ strtoupper($data->user->bank->bank_name) }}</h5>
                    </div>

                    <div class="col-md-6">
                        <p class="pb-2 m-0">Account Number</p>
                        <h5 class="text-left" id="">{{ strtoupper($data->user->bank->account_number) }}</h5>
                    </div>

                    <div class="col-md-6 mt-5">
                        <p class="pb-2 m-0">Account Name</p>
                        <h5 class="text-left text-green" id="">{{ strtoupper($data->user->bank->account_name) }}</h5>
                    </div>

                    <div class="col-md-6 mt-5">
                        <p class="pb-2 m-0">BVN</p>
                        <h5 class="text-left" id="">{{ strtoupper($data->user->bank->bvn) }} <b
                                class="text-green">({{ $data->user->bank->bvn_name ?? "NAME NOT AVAILABLE"}})
                            </b></h5>
                    </div>

                </div>
                @else
                <div class="alert alert-info">
                    Customer has no bank record yet!
                </div>
                @endif
            </div>
        </div>

        <div class="card mb-25">

            @if($data->status == 'pending')
            <div class="card-header">
                <strong class="card-title mb-0">Disburse</strong>
            </div>

            <div class="card-body p-4">

                <div class="panel-body">
                    <form id="process-form">
                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}
                        <input id='form-process-action' type="hidden" name="action" value="mark_processed" />
                        <div class="listy decision-actions">
                            <div class="__row">
                                <div class="col-6">
                                    <button type="button" id="mark_processed" class="btn btn-primary btn-decision"
                                        data-action="mark-processed" data-toggle="button" aria-pressed="false"
                                        autocomplete="off" onclick="mark_as_processed()">
                                        <i class="voyager-check-circle"></i> Mark As Processed</button>
                                        <p class="text"><small>This assumes you have performed the transfer somehow</small></p>
                                </div>
                                <div class="col-6">
                                    <button type="button" id="process_transfer" class="btn btn-success btn-decision"
                                        data-action="process-transfer" data-toggle="button" aria-pressed="false"
                                        autocomplete="off">
                                        <i class="voyager-wallet"></i> Process Transfer</button>
                                </div>
                            </div>
                            @if($data->status == 'disbursed')
                            <div class="spacer spacer-50"></div>
                            <div class="__row ">
                                <div class="alert alert-danger">
                                    <h3>Head Up</h3>
                                    <p>This payout has already been disbursed, while it might not have been remitted to the customer yet, it might be in flight.</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div id="process-form-loader" class="ba-loader d-none">
                            @include('partials.backoffice.spinner', ['hidden' => false])
                            <span> Processing payout, Please wait</span>
                        </div>


                    </form>
                </div>
            </div>
            @else
            <div class="card-header">
                <strong class="card-title mb-0">Disbursal Information</strong>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <p class="pb-2 m-0">Processed By</p>
                        <h5>{{ ucwords($data->process_admin->name) }}</h5>
                    </div>
                    <div class="col-md-6">
                        <p class="pb-2 m-0">Process Date</p>
                        <h5>{{Helper::shortDate($data->processed_at)}}</h5>
                    </div>
                </div>
                <div class="row mb-5">

                @if($data->transaction)
                    <div class="col-md-6 mt-5">
                        <p class="pb-2 m-0">Transaction Reference</p>
                        <h5 class="text-left" id=""><code>{{ $data->transaction->reference }}</code></h5>
                    </div>
                @endif
                    @if($data->pending_disbursal)
                    <div class="col-md-6 mt-5">
                        <p class="pb-2 m-0">Pending Disbursal</p>
                        <h5 class="text-left" id="">Yes</h5>
                    </div>
                    @endif

                </div>
            </div>
            @endif
        </div>
    </div>

</div>

<script>
    function mark_as_processed(){
        var mark_processed = document.querySelector('#mark_processed');

        mark_processed.addEventListener('click', () => {
            window.location.href = `/withdraw/${document.querySelector('#id').value}/process`;
        })
    }
</script>

@endsection
