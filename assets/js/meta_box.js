jQuery(function ($) {
    $('.wpi_ajax').on('click', function () {
        let $this = $(this),
            $loading = $this.parent().find('.spinner');
        $loading.addClass('is-active');
        $.get(ajaxurl + '?action=wpi_' + $this.attr('id') + '&id=' + document.getElementById('post_ID').value, function () {
            $loading.removeClass('is-active');
            location.reload();
        });
    });
});
