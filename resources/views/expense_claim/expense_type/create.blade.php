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
        var isBod = $('input[type=hidden][name=is_bod]').val();
        var isApproval = $('input[type=hidden][name=is_bp_approval]').val();
        if (isBod === '0' || !isBod) {
            $('#bodLevel').parent().hide();
        } else if (isBod === '1') {
            $('#bodLevel').parent().show();
        }

        if (isApproval === '0' || !isApproval) {
            $('#limitBusiness').parent().hide();

        } else if (isApproval === '1') {
            $('#limitBusiness').parent().show();
        }

        $('input[type=hidden][name=is_bod]').change(function() {
            if (this.value === '0') {
                $('#bodLevel').parent().hide();
            } else if (this.value === '1') {
                $('#bodLevel').parent().show();
            }
        })

        $('input[type=hidden][name=is_bp_approval]').change(function() {
            if (this.value === '0') {
                $('#limitBusiness').parent().hide();
            } else if (this.value === '1') {
                $('#limitBusiness').parent().show();
            }
        })
    </script>
@endpush
