@if ($crud->hasAccess('download_journal_ap'))
<a 
    class="btn btn-primary" data-style="zoom-in"
    href="javascript:void(0)"
    onclick="bulkCloneEntries(this)"
>
    <span class="ladda-label">
        <i class="la la-download"></i>AP Journal
    </span>
</a>
@endif

@push('after_scripts')
<script>
        // href="{{url('expense-finance-ap/download-ap-journal')}}"
  if (typeof bulkCloneEntries != 'function') {
    function bulkCloneEntries(button) {

        if (typeof crud.checkedItems === 'undefined' || crud.checkedItems.length == 0)
        {
            new Noty({
            type: "warning",
            text: "<strong>{{ trans('backpack::crud.bulk_no_entries_selected_title') }}</strong><br>{{ trans('backpack::crud.bulk_no_entries_selected_message') }}"
          }).show();

          return;
        }

        var message = "Are you sure you want to Download AP Journal these :number entries?";
        message = message.replace(":number", crud.checkedItems.length);

        // show confirm message
        swal({
        title: "{{ trans('backpack::base.warning') }}",
        text: message,
        icon: "warning",
        buttons: {
          cancel: {
          text: "{{ trans('backpack::crud.cancel') }}",
          value: null,
          visible: true,
          className: "bg-secondary",
          closeModal: true,
        },
          delete: {
          text: "Download",
          value: true,
          visible: true,
          className: "bg-primary",
        }
        },
      }).then((value) => {
        if (value) {
          var ajax_calls = [];
              var clone_route = "{{url('expense-finance-ap/download-ap-journal')}}";
            //   console.log(crud.checkedItems);

          // submit an AJAX delete call
          $.ajax({
            url: clone_route,
            type: 'POST',
            data: { entries: crud.checkedItems },
            cache: false,
            success: function(result) {
              // Show an alert with the result
              var link = document.createElement('a');
            //   link.href = window.URL.createObjectURL(result);
            link.href = result.file
                link.download = result.name;
                document.body.appendChild(link);
                link.click();
                link.remove();
                    new Noty({
                    type: "success",
                    text: "<strong>Download success</strong><br>"
                  }).show();

              crud.checkedItems = [];
              crud.table.ajax.reload();
            },
            error: function(result) {
              // Show an alert with the result
                    new Noty({
                    type: "danger",
                    text: "<strong>Download failed</strong><br>One or more entries could not be created. Please try again."
                  }).show();
            }
          });
        }
      });
      }
  }
</script>
@endpush
