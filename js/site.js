jQuery(function ($) {
    $('#get_info').on('click', function () {
        var $loading = $(this).parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wei_get_info&id=' + acf.data.post_id, function () {
            $loading.removeClass('is-active');
        });
    });

    $('#get_theme_plugin').on('click', function () {
        var $loading = $(this).parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wei_get_theme_plugin&id=' + acf.data.post_id, function () {
            $loading.removeClass('is-active');
        });
    });
});
