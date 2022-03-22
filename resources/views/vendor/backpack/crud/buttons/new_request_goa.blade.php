@if ($crud->hasAccess('request_goa') && ((isset($crud->requestGoaCondition) && call_user_func_array($crud->requestGoaCondition, array($entry))) || !isset($crud->requestGoaCondition)))
<a href="javascript:void(0)" onclick="newRequestGoaEntry(this)" data-route="{{ url($crud->route . '/new-request') }}" class="btn btn-info" data-style="zoom-in"><span class="ladda-label"><i class="la la-plus"></i> New Request for GoA Holder</span></a>
@endif
<div class="modal fade" id="modalRequestGoa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content position-relative">
        <div class="modal-header">
          <h5 class="modal-title">New Request for GoA Holder</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="bodyRequestGoa">
           <div class="form-group required">
               <label>GoA Holder</label>
               <select class="form-control" name="goa_id" style="width:100%" id="selectGoa">
                    <option value=""></option>
                    @foreach ($crud->goaUser as $goa)
                        <option value="{{$goa->user->id}}">{{$goa->user->name}}</option>
                    @endforeach
                </select>
           </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" id="btnSaveRequestGoa"><i class="la la-save"></i> Create</button>
        </div>
        <div class="position-absolute d-none" id="loaderSaveRequestGoa" style="top:0;left:0;right:0;bottom:0;background-color:rgba(240, 243, 249, 0.5);z-index:100">
            @include('loaders')
        </div>
      </div>
    </div>
  </div>

@push('after_styles')
  <link href="{{ asset('packages/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" /> 
  <link href="{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
  <style>
    .form-group.required > label:not(:empty):not(.form-check-label)::after {
        content: ' *';
        color: #ff0000;
    }
</style>
@endpush
{{-- Button Javascript --}}
{{-- - used right away in AJAX operations (ex: List) --}}
{{-- - pushed to the end of the page, after jQuery is loaded, for non-AJAX operations (ex: Show) --}}
@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script src="{{ asset('packages/select2/dist/js/select2.full.min.js') }}"></script>
<script>
    if (typeof newRequestGoaEntry != 'function') {
        $('#selectGoa').select2({
            theme: "bootstrap",
            placeholder: "",
       });
	  function newRequestGoaEntry(button) {
        toggleLoadingSaveRequestGoa(false);
        clearErrorRequestGoa();
        $('#modalRequestGoa').appendTo($('body'));
        $('#modalRequestGoa').modal('show');
        $('#selectGoa').val(null).trigger('change');
      }

      $('#btnSaveRequestGoa').click(function(){
          saveRequestGoaEntry();
      });

    function toggleLoadingSaveRequestGoa(isLoading){
        if(isLoading){
            $('#loaderSaveRequestGoa').removeClass('d-none');
            $('#modalRequestGoa').data('bs.modal')._config.backdrop = 'static';
            $('#modalRequestGoa').data('bs.modal')._config.keyboard = false;
        }
        else{
            $('#loaderSaveRequestGoa').addClass('d-none');
            var modal = $('#modalRequestGoa').data('bs.modal');
            if(modal != null){
                modal._config.backdrop = true;
                modal._config.keyboard = true;
            }
        }
    }
        function clearErrorRequestGoa(){
            $('#bodyRequestGoa div.invalid-feedback').remove();
            $('#bodyRequestGoa select.is-invalid').removeClass('is-invalid');
            $('#bodyRequestGoa div.text-danger').removeClass('text-danger');
        }

      function saveRequestGoaEntry(){
            toggleLoadingSaveRequestGoa(true);
            $.ajax({
                url: "{{backpack_url('expense-user-request/new-request-goa')}}",
                type: 'POST',
                data:{
                    goa_id: $('#selectGoa').val()
                },
                success: function(result) {
                window.location.href = result.redirect_url;
                },
                error: function(result) {
                    clearErrorRequestGoa();
                    toggleLoadingSaveRequestGoa(false);
                    // Show an alert with the result
                    var defaultText = "{!! trans('backpack::crud.request_confirmation_not_message') !!}";
                    if(result.status == 422){
                        var message = '';
                        var tempMessage = result.responseJSON.errors;
                        var parent = $('#bodyRequestGoa');
                        for(var key in tempMessage){
                            message = '';
                            tempMessage[key].forEach(element => {
                            message += '<div class="invalid-feedback d-block">' + element + '</div>';
                            });
                            var element = parent.find('select[name="' + key + '"]');
                            if(element.length == 1){
                                var parents = element.parents('div.form-group');
                                parents.append(message);
                            }
                        } 
                        return;
                    }
                    else if(result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0){
                        defaultText = result.responseJSON.message;
                    }
                    new Noty({
                        type: "error",
                        text: defaultText,
                    }).show();
                }
            });
        }
	}

</script>
@if (!request()->ajax()) @endpush @endif
