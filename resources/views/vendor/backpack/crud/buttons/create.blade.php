@if ($crud->hasAccess('create') && ((isset($crud->createCondition) && call_user_func($crud->createCondition)) || !isset($crud->createCondition)))
	<button onclick="window.location='{{ url($crud->route.'/create') }}'" class="btn btn-primary" data-style="zoom-in"><span class="ladda-label"><i class="la la-plus"></i> {{ trans('backpack::crud.add') }} {{ $crud->entity_name }}</span></button>
@endif