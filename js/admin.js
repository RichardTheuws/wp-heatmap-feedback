(function($) {
    'use strict';

    $(document).ready(function() {
        // Functionaliteit voor het dynamisch toevoegen en verwijderen van formuliervelden
        var fieldIndex = $('#feedback_form_fields .field').length;

        $('#add_form_field').on('click', function() {
            var newField = $('#field_template').html().replace(/INDEX/g, fieldIndex);
            $('#feedback_form_fields').append(newField);
            fieldIndex++;
        });

        $('#feedback_form_fields').on('click', '.remove_field', function() {
            $(this).closest('.field').remove();
        });
    });
})(jQuery);