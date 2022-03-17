@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
    trans('backpack::crud.admin') => backpack_url('dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.edit') => false,
];

// if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</span>
            <small>{!! $crud->getSubheading() ?? trans('backpack::crud.edit') . ' ' . $crud->entity_name !!}.</small>

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
                <div id="createInfo" class="card-body"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="{{ $crud->getEditContentClass() }}">
            <!-- Default box -->

            @include('crud::inc.grouped_errors')

            <form method="post" action="{{ url($crud->route . '/' . $entry->getKey()) }}"
                @if ($crud->hasUploadFields('update', $entry->getKey())) enctype="multipart/form-data" @endif>
                {!! csrf_field() !!}
                {!! method_field('PUT') !!}

                @if ($crud->model->translationEnabled())
                    <div class="mb-2 text-right">
                        <!-- Single button -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                {{ trans('backpack::crud.language') }}:
                                {{ $crud->model->getAvailableLocales()[request()->input('_locale') ? request()->input('_locale') : App::getLocale()] }}
                                &nbsp; <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                @foreach ($crud->model->getAvailableLocales() as $key => $locale)
                                    <a class="dropdown-item"
                                        href="{{ url($crud->route . '/' . $entry->getKey() . '/edit') }}?_locale={{ $key }}">{{ $locale }}</a>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                <!-- load the view from the application if it exists, otherwise load the one in the package -->
                @if (view()->exists('vendor.backpack.crud.form_content'))
                    @include('vendor.backpack.crud.form_content', [
                        'fields' => $crud->fields(),
                        'action' => 'edit',
                    ])
                @else
                    @include('crud::form_content', [
                        'fields' => $crud->fields(),
                        'action' => 'edit',
                    ])
                @endif
                <!-- This makes sure that all field assets are loaded. -->
                <div class="d-none" id="parentLoadedAssets">{{ json_encode(Assets::loaded()) }}</div>
                @include('crud::inc.form_save_buttons')
            </form>
        </div>
    </div>
@endsection
@push('after_scripts')
    <script>
        var expenseTypes = @json($expenseTypes);
        var configs = @json($configs);

        var usdToIdr = parseFloat(configs.usd_to_idr.value);
        var isLimit = false;
        var selectedExpenseTypeId = parseInt($('#expenseTypeId').val());

        $('#totalPersonId').parent().hide();

        $('#createInfo')
            .append(
                $('<div/>')
                .addClass("form-group col-sm-12 p-0")
                .append(
                    $('<label/>')
                    .text("USD to IDR")
                ).append(
                    $("<input/>")
                    .attr("id", "usdToIdr")
                    .attr("name", "usdToIdr")
                    .attr("readonly", "readonly")
                    .attr("disabled", "disabled")
                    .addClass("form-control")
                    .val(usdToIdr.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'))
                )
            ).append(
                $('<div/>')
                .addClass("form-group col-sm-12 p-0")
                .append(
                    $('<label/>')
                    .text("Currency")
                ).append(
                    $("<input/>")
                    .attr("id", "currencyId")
                    .attr("name", "currency")
                    .attr("readonly", "readonly")
                    .attr("disabled", "disabled")
                    .addClass("form-control")
                )
            ).append(
                $('<div/>')
                .addClass("form-group col-sm-12 p-0")
                .append(
                    $('<label/>')
                    .text("Limit")
                ).append(
                    $("<input/>")
                    .attr("id", "limitId")
                    .attr("name", "limit")
                    .attr("readonly", "readonly")
                    .attr("disabled", "disabled")
                    .addClass("form-control")
                )
            );

        selectExpenseType(selectedExpenseTypeId)

        $('#expenseTypeId').on('change', function() {
            selectExpenseType(parseInt(this.value));
        });

        function numberWithCommas(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function selectExpenseType(selectedId) {
            var item = expenseTypes.filter(item => item.expense_type_id === selectedId)[0];
            if (item.expense_type_id === selectedId) {
                $('#currencyId').val(item.currency);
                $('#limitId').val(numberWithCommas(item.limit));
            }

            if (item.bp_approval && item.level === 'D7') {
                addBusinessApproval();
            } else {
                $('#businessApprovalId').parent().parent().remove();
            }

            if (item.limit_person) {
                addLimitPerson();
            } else {
                $('#limitPersonId').parent().parent().remove();
            }
        }

        function addBusinessApproval() {
            $('select[name=expense_type_id]')
                .parent()
                .parent()
                .append(
                    $('<div/>')
                    .addClass("form-group col-sm-12 required")
                    .append(
                        $('<div/>')
                        .addClass('checkbox').append(
                            $("<input/>")
                            .attr("id", "businessApprovalId")
                            .attr("name", "is_bp_approval")
                            .attr("type", "checkbox")
                        ).append(
                            $('<label/>')
                            .addClass("form-check-label font-weight-normal ml-1")
                            .text("Business Approval")
                        )
                    )

                )
        }

        function addLimitPerson() {
            $('select[name=expense_type_id]')
                .parent()
                .parent()
                .append(
                    $('<div/>')
                    .addClass("form-group col-sm-12 required")
                    .append(
                        $('<div/>')
                        .addClass('checkbox').append(
                            $("<input/>")
                            .attr("id", "limitPersonId")
                            .attr("name", "is_limit_person")
                            .attr("type", "checkbox")
                        ).append(
                            $('<label/>')
                            .addClass("form-check-label font-weight-normal ml-1")
                            .text("Limit Person")
                        )
                    )
                );

            $("#limitPersonId").on('change', function() {
                isLimit = !isLimit;
                if (isLimit) {
                    $('select[name=expense_type_id]')
                        .parent()
                        .parent()
                        .append(
                            $('<div/>')
                            .addClass("form-group col-sm-12 required")
                            .attr("id", "totalPersonId")
                            .append(
                                $('<div/>')
                                .addClass("form-group col-sm-12 p-0")
                                .append(
                                    $('<label/>')
                                    .text("Total Person")
                                ).append(
                                    $("<input/>")
                                    .attr("name", "total_person")
                                    .attr("type", "number")
                                    .addClass("form-control")
                                )
                            )
                        )
                } else if (!isLimit) {
                    $('#totalPersonId').remove();
                }

                $(this).val(isLimit);
            })
        }
    </script>
@endpush
