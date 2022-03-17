<!-- text input -->
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <input type="hidden" name="{{ $field['name'] }}" value="{{ old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '' }}">
    @if(isset($field['prefix']) || isset($field['suffix'])) <div class="input-group"> @endif
        @if(isset($field['prefix'])) <div class="input-group-prepend"><span class="input-group-text">{!! $field['prefix'] !!}</span></div> @endif
        <input
            data-init-function="bpFieldInitCleaveElement"
            type="text" 
            data-cleave-decimal-mark = "{{isset($field['decimal_mark']) ? $field['decimal_mark'] : '.'}}"
            data-cleave-delimiter = "{{isset($field['delimiter']) ? $field['delimiter'] : ','}}"
            data-cleave-negative = "{{isset($field['negative']) ? $field['negative'] : false }}" data-cleave-need-separator="{{isset($field['need_separator']) ? $field['need_separator'] : true }}" data-cleave-leading-zero="{{isset($field['allow_leading_zero']) ? $field['allow_leading_zero'] : false }}" 
            data-cleave data-cleave-decimal = "{{isset($field['decimal']) ? $field['decimal'] : 0}}" data-cleave-integer = "{{isset($field['integer']) ? $field['integer'] : 9}}"
            @include('crud::fields.inc.attributes')
        >
        @if(isset($field['suffix'])) <div class="input-group-append"><span class="input-group-text">{!! $field['suffix'] !!}</span></div> @endif
    @if(isset($field['prefix']) || isset($field['suffix'])) </div> @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')
@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp
     @push('crud_fields_scripts')
     <script src="{{ asset('js/app.js') }}"></script>
     <script>
         var cleaveOpts = {
             numeral: true,
             numeralDecimalMark: '.',
             delimiter: ',',
             numeralPositiveOnly: true,
         }
         var cleaveElmtCache = [];
         function bpFieldInitCleaveElement(){
            $('input[type=text][data-cleave]').each(function(){
                var $fake = $(this);
                var $field = $fake.parents('.form-group').find('input[type="hidden"]');
                var copyOpts = Object.assign({}, cleaveOpts);
                copyOpts.numeralDecimalMark = $fake.data('cleaveDecimalMark');
                copyOpts.delimiter = $fake.data('cleaveDelimiter');
                copyOpts.numeralDecimalScale = $fake.data('cleaveDecimal');
                copyOpts.numeralIntegerScale = $fake.data('cleaveInteger');
                var cleaveNeedSeparator = $fake.data('cleaveNeedSeparator');
                var cleaveLeadingZero = $fake.data('cleaveLeadingZero');
                var cleaveNegative = $fake.data('cleaveNegative');
                copyOpts.onValueChanged = function (e) {
                    $field.val(e.target.rawValue).trigger('change');
                }
                if(!cleaveNeedSeparator){
                copyOpts.numeralThousandsGroupStyle = 'none';
                }
                if(cleaveLeadingZero){
                copyOpts.stripLeadingZeroes = false;
                }
                copyOpts.numeralPositiveOnly = !cleaveNegative;
                var cleaveElmt = new Cleave($fake, copyOpts);
                var $existingVal = $field.val();
    
                if($existingVal.length){
                    cleaveElmt.setRawValue($existingVal);
                }
                cleaveElmtCache[$field.attr('name')] = cleaveElmt;
            });
         }
     </script>
     @endpush
@endif