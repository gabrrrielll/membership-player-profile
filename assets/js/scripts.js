jQuery(document).ready(function ($) {

    // Gallery editor: remove image
    $(document).on('click', '.profootball-gallery-remove', function () {
        $(this).closest('.profootball-gallery-item').remove();
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
