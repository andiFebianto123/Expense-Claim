@if ($crud->hasAccess('request') && ((isset($crud->requestCondition) && call_user_func_array($crud->requestCondition, array($entry))) || !isset($crud->requestCondition)))
<a href="javascript:void(0)" onclick="newRequestEntry(this)" data-route="{{ url($crud->route . '/new-request') }}" class="btn btn-primary" data-style="zoom-in"><span class="ladda-label"><i class="la la-plus"></i> New Request</span></a>
@endif

{{-- Button Javascript --}}
{{-- - used right away in AJAX operations (ex: List) --}}
{{-- - pushed to the end of the page, after jQuery is loaded, for non-AJAX operations (ex: Show) --}}
@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script>
    if (typeof newRequestEntry != 'function') {
	  function newRequestEntry(button) {
		// ask for confirmation before create new request
		// e.preventDefault();
		var route = $(button).attr('data-route');

		swal({
		  title: "{!! trans('backpack::crud.confirmation') !!}",
		  text: "{!! trans('backpack::crud.request_confirm') !!}",
		  icon: "info",
		  buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('backpack::crud.create') !!}"],
		}).then((value) => {
			if (value) {
				$.ajax({
			      url: route,
			      type: 'POST',
			      success: function(result) {
                    window.location.href = result.redirect_url;
			      },
			      error: function(result) {
			          // Show an alert with the result
                      var defaultText = "{!! trans('backpack::crud.request_confirmation_not_message') !!}";
                      if(result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0){
						  defaultText = result.responseJSON.message;
					  }
			          swal({
		              	title: "{!! trans('backpack::crud.request_confirmation_not_title') !!}",
                        text: defaultText,
		              	icon: "error",
		              	timer: 4000,
		              	buttons: false,
		              });
			      }
			  });
			}
		});

      }
	}

</script>
@if (!request()->ajax()) @endpush @endif
