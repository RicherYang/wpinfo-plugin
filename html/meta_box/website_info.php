<?php
$metadata = has_meta($post->ID);

$list = [
    'url' => '網址',
    'content_path' => 'content 網址',
    'rest_url' => 'REST 網址',
    'rest_api' => 'REST API'
]
?>
<table>
    <?php
    foreach ($list as $key => $name) {
        $find = false;
        foreach ($metadata as $data) {
            if ($data['meta_key'] == $key) {
                $find= true;
                echo '<tr>'
                    . '<td>' . $name . '</td>'
                    . '<td>'
                        . '<input type="text" name="meta[' . $data['meta_id'] . '][value]" value="' . esc_attr($data['meta_value']) . '">'
                        . '<input type="hidden" name="meta[' . $data['meta_id'] . '][key]" value="' . esc_attr($data['meta_key']) . '">'
                    . '</td>'
                    . '</tr>';
            }
        }
        if ($find === false) {
            update_post_meta($post->ID, $key, '');
        }
    }
    ?>
</table>

<p>
    佈景主題：<?php the_post_list(get_post_meta($post->ID, 'theme'), '，'); ?>
</p>
<p>
    外掛：<?php the_post_list(get_post_meta($post->ID, 'plugin'), '，'); ?>
</p>
