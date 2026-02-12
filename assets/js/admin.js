jQuery(document).ready(function ($) {

    // Make sections sortable
    $('#profootball-sections-container').sortable({
        handle: '.handle',
        placeholder: 'sortable-placeholder',
        update: function () {
            reindexAll();
            updateLayoutPreview();
        }
    });

    // Make fields sortable within sections
    function initFieldSortable() {
        $('.fields-list').sortable({
            items: '.field-config-row',
            placeholder: 'sortable-placeholder',
            update: function () {
                reindexAll();
                updateLayoutPreview();
            }
        });
    }
    initFieldSortable();

    // Add New Section
    $('#add-new-section').on('click', function () {
        var index = Date.now(); // Simple unique index
        var tpl = $('#profootball-section-tpl').html();
        tpl = tpl.replace(/{{INDEX}}/g, index);

        $('#profootball-sections-container').append(tpl);
        initFieldSortable();
    });

    // Toggle Section Visibility
    $(document).on('click', '.toggle-section', function () {
        var $section = $(this).closest('.profootball-section-item');
        $section.toggleClass('open');
        $section.find('.section-fields-container').slideToggle();
    });

    // Remove Section
    $(document).on('click', '.remove-section', function () {
        if (confirm('Are you sure you want to remove this section?')) {
            $(this).closest('.profootball-section-item').remove();
            updateLayoutPreview();
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

        // Open the section if closed
        if (!$section.hasClass('open')) {
            $section.addClass('open');
            $section.find('.section-fields-container').slideDown();
        }
    });

    // Remove Field
    $(document).on('click', '.remove-field', function () {
        $(this).closest('.field-config-row').remove();
        updateLayoutPreview();
    });

    // Toggle Download Text based on checkbox
    $(document).on('change', '.download-toggle-wrap input[type="checkbox"]', function () {
        if ($(this).is(':checked')) {
            $(this).closest('.download-toggle-wrap').find('.download-text-wrap').fadeIn();
        } else {
            $(this).closest('.download-toggle-wrap').find('.download-text-wrap').fadeOut();
        }
    });

    // Toggle Download Checkbox based on type
    $(document).on('change', '.field-type-select', function () {
        var type = $(this).val();
        var $row = $(this).closest('.field-config-row');
        if (type === 'file' || type === 'image') {
            $row.find('.download-toggle-wrap').fadeIn();
        } else {
            $row.find('.download-toggle-wrap').fadeOut();
        }

        if (type === 'shortcut_buttons') {
            $row.find('.field-label-preview').val('Shortcuts');
            $row.find('.field-width-select').val('12').trigger('change');
            $row.find('.ump-mapping-select').val('').trigger('change');
        }

        if (type === 'select' || type === 'multiselect' || type === 'nationality') {
            $row.find('.field-options-wrap').fadeIn();
            var $textarea = $row.find('.field-options-wrap textarea');
            var $small = $row.find('.field-options-wrap small');
            if (type === 'nationality') {
                $textarea.attr('placeholder', 'e.g. 100px or 120px');
                $small.text('Enter flag width (e.g. 100px). Default is 40px.');
                $row.find('.nat-name-toggle-wrap').fadeIn();
            } else {
                $textarea.attr('placeholder', 'Option 1|Option 2, Option 3');
                $small.text('Use commas or new lines. "value|label" supported.');
                $row.find('.nat-name-toggle-wrap').fadeOut();
            }
        } else {
            $row.find('.field-options-wrap').fadeOut();
        }
        updateLayoutPreview();
    });

    // Update preview on any change
    $(document).on('input change', '.section-title-input, .field-label-preview, .field-width-select', function () {
        if ($(this).hasClass('field-width-select')) {
            var width = $(this).val();
            var $row = $(this).closest('.field-config-row');
            // Remove old col classes
            $row.removeClass(function (index, className) {
                return (className.match(/(^|\s)col-\S+/g) || []).join(' ');
            });
            $row.addClass('col-' + width);
        }
        updateLayoutPreview();
    });

    // Make visualizer update on sort
    $('#profootball-sections-container').on('sortupdate', function () {
        reindexAll();
        updateLayoutPreview();
    });

    function updateLayoutPreview() {
        var $visualizer = $('#profootball-layout-visualizer');
        if (!$visualizer.length) return;
        $visualizer.empty();

        $('.profootball-section-item').each(function () {
            var sectionTitle = $(this).find('.section-title-input').val() || 'Untitled Section';
            var $sectionBox = $('<div class="preview-section"><div class="preview-section-title">' + sectionTitle + '</div><div class="preview-row"></div></div>');

            $(this).find('.fields-list .field-config-row').each(function () {
                var label = $(this).find('.field-label-preview').val() || 'Field';
                var width = $(this).find('.field-width-select').val() || '12';
                var type = $(this).find('.field-type-select').val();

                var widthClass = 'preview-col-' + width;
                var extraClass = (type === 'empty_space') ? ' preview-empty' : '';
                var content = (type === 'empty_space') ? '' : '<span>' + label + '</span>';

                var $fieldMock = $('<div class="preview-field ' + widthClass + extraClass + '">' + content + '</div>');
                $sectionBox.find('.preview-row').append($fieldMock);
            });

            $visualizer.append($sectionBox);
        });
    }

    function reindexAll() {
        $('.profootball-section-item').each(function (s_idx) {
            $(this).attr('data-index', s_idx);

            // Update section title input name
            $(this).find('.section-title-input').attr('name', 'profootball_player_sections[' + s_idx + '][title]');

            // Update all fields in this section
            $(this).find('.fields-list .field-config-row').each(function (f_idx) {
                $(this).find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        // Replace BOTH indices: section index and field index
                        var newName = name.replace(/profootball_player_sections\[\d+\]\[fields\]\[\d+\]/, 'profootball_player_sections[' + s_idx + '][fields][' + f_idx + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        });
    }

    // Initialize preview
    updateLayoutPreview();

});
