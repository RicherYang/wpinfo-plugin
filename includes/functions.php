<?php
function check_and_update_post($args)
{
    $post = get_post($args['ID']);
    if (empty($post)) {
        return;
    }

    $do_update = false;
    foreach ($args as $key => $value) {
        if($post->$key != $value) {
            $do_update = true;
            break;
        }
    }

    if ($do_update) {
        wp_update_post($args);
    }
}
