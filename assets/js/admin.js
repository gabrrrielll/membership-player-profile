jQuery(document).ready(function($) {
    
    // Make sections sortable
    $('#profootball-sections-container').sortable({
        handle: '.handle',
        placeholder: 'sortable-placeholder',
        update: function() {
            // Optional: update indexes after sort if needed, 
            // but WP handles associative arrays okay if names are unique.
        }
    });

    // Add New Section
    $('#add-new-section').on('click', function() {
        var index = Date.now(); // Simple unique index
        var tpl = $('#profootball-section-tpl').html();
        tpl = tpl.replace(/{{INDEX}}/g, index);
        
        $('#profootball-sections-container').append(tpl);
    });

    // Remove Section
    $(document).on('click', '.remove-section', function() {
        if (confirm('Are you sure you want to remove this section?')) {
            $(this).closest('.profootball-section-item').remove();
        }
    });

    // Add New Field
    $(document).on('click', '.add-new-field', function() {
        var $section = $(this).closest('.profootball-section-item');
        var s_index = $section.data('index');
        var f_index = Date.now();
        
        var tpl = $('#profootball-field-tpl').html();
        tpl = tpl.replace(/{{S_INDEX}}/g, s_index);
        tpl = tpl.replace(/{{F_INDEX}}/g, f_index);
        
        $section.find('.fields-list').append(tpl);
    });

    // Remove Field
    $(document).on('click', '.remove-field', function() {
        $(this).closest('tr').remove();
    });

});
