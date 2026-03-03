jQuery(document).ready(function ($) {

    // Gallery editor: remove image
    $(document).on('click', '.profootball-gallery-remove', function () {
        $(this).closest('.profootball-gallery-item').remove();
    });

    // Gallery editor: feedback for newly selected images
    $(document).on('change', '.profootball-gallery-add-label input[type="file"]', function (e) {
        var $input = $(this);
        var $container = $input.closest('.profootball-gallery-editor');
        var files = e.target.files;
        
        // Remove existing temporary previews
        $container.find('.profootball-gallery-temp-previews').remove();
        
        if (files.length > 0) {
            var $tempContainer = $('<div class="profootball-gallery-temp-previews"></div>');
            $tempContainer.append('<div class="temp-preview-header">Files to be uploaded:</div>');
            var $list = $('<div class="profootball-gallery-preview temp"></div>');
            
            for (var i = 0; i < files.length; i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var $item = $('<div class="profootball-gallery-item temp-upload"></div>');
                        $item.append('<img src="' + e.target.result + '" title="' + file.name + '">');
                        $item.append('<span class="temp-badge">New</span>');
                        $list.append($item);
                    };
                    reader.readAsDataURL(file);
                })(files[i]);
            }
            
            $tempContainer.append($list);
            $container.append($tempContainer);
        }
    });

    // General file upload feedback
    $(document).on('change', '.profootball-upload-label input[type="file"]', function(e) {
        if ($(this).closest('.profootball-gallery-add-label').length) return; // Skip gallery (handled above)
        
        var fileName = e.target.files[0] ? e.target.files[0].name : '';
        var $wrapper = $(this).closest('.profootball-upload-wrapper');
        
        $wrapper.find('.profootball-upload-feedback').remove();
        if (fileName) {
            $wrapper.append('<div class="profootball-upload-feedback"><span class="dashicons dashicons-media-text"></span> Ready: <strong>' + fileName + '</strong></div>');
        }
    });

    // Smooth scroll for anchor buttons
    $('.profootball-anchor-button').on('click', function (e) {
        var target = $(this).attr('href');
        if (target.startsWith('#')) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 800);
        }
    });

    // Simple Slider Logic
    $('.profootball-gallery-slider').each(function () {
        var $slider = $(this);
        var $wrapper = $slider.find('.slider-wrapper');
        var $items = $slider.find('.slider-item');
        var count = $items.length;
        var current = 0;

        if (count <= 1) {
            $slider.find('.slider-nav').hide();
            return;
        }

        $slider.find('.slider-next').on('click', function () {
            current = (current + 1) % count;
            updateSlider();
        });

        $slider.find('.slider-prev').on('click', function () {
            current = (current - 1 + count) % count;
            updateSlider();
        });

        function updateSlider() {
            var offset = current * -100;
            $wrapper.css('transform', 'translateX(' + offset + '%)');
        }
    });

});
