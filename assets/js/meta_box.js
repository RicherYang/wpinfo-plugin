jQuery(function ($) {
    $('#get_info').on('click', function () {
        var $loading = $(this).parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wpi_get_info&id=' + document.getElementById('post_ID').value, function () {
            $loading.removeClass('is-active');
        });
    });

    $('#get_theme_plugin').on('click', function () {
        var $loading = $(this).parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wpi_get_theme_plugin&id=' + document.getElementById('post_ID').value, function () {
            $loading.removeClass('is-active');
        });
    });

    $('#get_plugin_info').on('click', function () {
        var $loading = $(this).parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wpi_get_plugin_info&id=' + document.getElementById('post_ID').value, function () {
            $loading.removeClass('is-active');
        });
    });
});
