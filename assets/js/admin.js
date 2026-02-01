jQuery(document).ready(function ($) {

    // Make sections sortable
    $('#profootball-sections-container').sortable({
        handle: '.handle',
        placeholder: 'sortable-placeholder',
        update: function () {
            // Optional: update indexes after sort if needed, 
            // but WP handles associative arrays okay if names are unique.
        }
    });

    // Add New Section
    $('#add-new-section').on('click', function () {
        var index = Date.now(); // Simple unique index
        var tpl = $('#profootball-section-tpl').html();
        tpl = tpl.replace(/{{INDEX}}/g, index);

        $('#profootball-sections-container').append(tpl);
    });

    // Remove Section
    $(document).on('click', '.remove-section', function () {
        if (confirm('Are you sure you want to remove this section?')) {
            $(this).closest('.profootball-section-item').remove();
        }
    });

    // Add New Field
    $(document).on('click', '.add-new-field', function () {
        var $section = $(this).closest('.profootball-section-item');
        var s_index = $section.data('index');
        var f_index = Date.now();

        var tpl = $('#profootball-field-tpl').html();
        tpl = tpl.replace(/{{S_INDEX}}/g, s_index);
        tpl = tpl.replace(/{{F_INDEX}}/g, f_index);

        $section.find('.fields-list').append(tpl);
    });

    // Remove Field
    $(document).on('click', '.remove-field', function () {
        $(this).closest('tr').remove();
    });

    // Toggle Download Checkbox based on type
    $(document).on('change', '.field-type-select', function () {
        var type = $(this).val();
        var $row = $(this).closest('tr');
        if (type === 'file' || type === 'image') {
            $row.find('.download-toggle-wrap').fadeIn();
        } else {
            $row.find('.download-toggle-wrap').fadeOut();
        }
        updateLayoutPreview();
    });

    // Update preview on any change
    $(document).on('input change', '.section-title-input, .field-label-preview, .field-width-select', function () {
        updateLayoutPreview();
    });

    // Make visualizer update on sort
    $('#profootball-sections-container').on('sortupdate', function () {
        updateLayoutPreview();
    });

    function updateLayoutPreview() {
        var $visualizer = $('#profootball-layout-visualizer');
        if (!$visualizer.length) return;
        $visualizer.empty();

        $('.profootball-section-item').each(function () {
            var sectionTitle = $(this).find('.section-title-input').val() || 'Untitled Section';
            var $sectionBox = $('<div class="preview-section"><div class="preview-section-title">' + sectionTitle + '</div><div class="preview-row"></div></div>');

            $(this).find('.fields-list tr').each(function () {
                var label = $(this).find('.field-label-preview').val() || 'Field';
                var width = $(this).find('.field-width-select').val() || '12';

                var widthClass = 'preview-col-' + width;
                var $fieldMock = $('<div class="preview-field ' + widthClass + '"><span>' + label + '</span></div>');
                $sectionBox.find('.preview-row').append($fieldMock);
            });

            $visualizer.append($sectionBox);
        });
    }

    // Initialize preview
    updateLayoutPreview();

});
