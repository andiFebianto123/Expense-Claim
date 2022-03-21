@extends(backpack_view('blank'))

@php
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
            <small>{!! $crud->getSubheading() ?? trans('backpack::crud.add') . ' ' . $crud->entity_name !!}.</small>

            @if ($crud->hasAccess('list'))
                <small><a href="{{ url($crud->route) }}" class="d-print-none font-sm"><i
                            class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>{{ $crud->entity_name_plural }}</span></a></small>
            @endif
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="{{ $crud->getCreateContentClass() }}">
            <div class="card">
                <div id="createInfo" class="card-body row">
                    <div class="form-group col-md-12">
                        <label>USD to IDR</label>
                        <input class="form-control" value="{{formatNumber($configs['usd_to_idr'] ?? null)}}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Start Exchange Date</label>
                        <input class="form-control" value="{{formatDate($configs['start_exchange_date'] ?? null)}}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Exchange Date</label>
                        <input class="form-control" value="{{formatDate($configs['end_exchange_date'] ?? null)}}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Currency</label>
                        <input class="form-control" id="currencyId" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Limit</label>
                        <input class="form-control" id="limitId" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="{{ $crud->getCreateContentClass() }}">
            <!-- Default box -->

            @include('crud::inc.grouped_errors')

            <form method="post" action="{{ url($crud->route) }}"
                @if ($crud->hasUploadFields('create')) enctype="multipart/form-data" @endif>
                {!! csrf_field() !!}
                <!-- load the view from the application if it exists, otherwise load the one in the package -->
                @if (view()->exists('vendor.backpack.crud.form_content'))
                    @include('vendor.backpack.crud.form_content', [
                        'fields' => $crud->fields(),
                        'action' => 'create',
                    ])
                @else
                    @include('crud::form_content', [
                        'fields' => $crud->fields(),
                        'action' => 'create',
                    ])
                @endif

                @include('crud::inc.form_save_buttons')
            </form>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script>
       $(document).ready(function(){
        var expenseTypes = @json($expenseTypes);
        var selectedExpenseTypeId = parseInt($('#expenseTypeId').val());
        var currentItem = null;
        console.log(selectedExpenseTypeId);

        selectExpenseType(selectedExpenseTypeId)

        $('#expenseTypeId').on('change', function() {
            selectExpenseType(parseInt(this.value));
        });

        function numberWithCommas(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        function selectExpenseType(selectedId) {
            currentItem = null;
            var index = expenseTypes.findIndex(item => item.expense_type_id.toString() === selectedId.toString());
            if(index !== -1){
                currentItem = expenseTypes[index];
                console.log(currentItem);
                item = currentItem;
                if(item.traf){
                    $('#documentFile').parents('div.form-group').addClass('required');
                }
                else{
                    $('#documentFile').parents('div.form-group').removeClass('required');
                }
                if (item.expense_type_id === selectedId) {
                    $('#currencyId').val(item.currency);
                    var multiply = 1;
                    if(item.limit_person){
                        if(cleaveElmtCache['total_person'] !== null && cleaveElmtCache['total_person'] !== undefined){
                            multiply = cleaveElmtCache['total_person'].getRawValue();
                            
                        }
                        else{
                            multiply = $('input[name="total_person"]').val();
                        }
                        if(multiply === null || multiply === undefined){
                            multiply = 0;
                        }
                    }
                    if(item.limit === null || item.limit === undefined){
                        $('#limitId').val('∞');
                    }
                    else{
                        $('#limitId').val(numberWithCommas(item.limit * (multiply)));
                    }
                }

                if (item.bp_approval && item.level === 'D7') {
                    $('input[name="is_bp_approval"]').parents('div.form-group').removeClass('d-none');
                } else {
                    $('input[name="is_bp_approval"]').parents('div.form-group').addClass('d-none');
                }

                if (item.limit_person) {
                    $('#totalPerson').parents('div.form-group').removeClass('d-none');
                } else {
                    if(cleaveElmtCache['total_person'] !== null && cleaveElmtCache['total_person'] !== undefined){
                        cleaveElmtCache['total_person'].setRawValue('');
                    }
                    $('#totalPerson').parents('div.form-group').addClass('d-none');
                }
            }
        }

        $('#totalPerson').parents('.form-group').find('input[type="hidden"]').on('change', function(){
            if(currentItem != null && currentItem.limit_person){
                if(currentItem.limit === null || currentItem.limit === undefined){
                    $('#limitId').val('∞');
                }
                else{
                    $('#limitId').val(numberWithCommas(currentItem.limit * (this.value.length == 0 ? 0 : this.value)));
                }
            }
        });
       });
    </script>
@endpush
