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
        var expenseTypes = @json($expenseTypes);
        var selectedExpenseTypeId = parseInt($('#expenseTypeId').val());

        $('#totalPersonId').parent().hide();

        expenseTypes.forEach(item => {
            if (item.expense_type_id === selectedExpenseTypeId) {
                $('#currencyId').val(item.currency);
                $('#limitId').val(numberWithCommas(item.limit));
            }
        });

        $('#expenseTypeId').on('change', function() {
            expenseTypes.forEach(item => {
                if (item.expense_type_id === parseInt(this.value)) {
                    $('#currencyId').val(item.currency);
                    $('#limitId').val(numberWithCommas(item.limit));
                }
            });
        });



        function numberWithCommas(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        $("#limitPersonId").on('change', function() {
            var isCheck = $(this).siblings('input[type=hidden][name=is_limit_person]').val();
            if (isCheck === '0') {
                $('#totalPersonId').parent().show();
            } else if (isCheck == '1') {
                $('#totalPersonId').parent().hide();
            }
        })
    </script>
@endpush
