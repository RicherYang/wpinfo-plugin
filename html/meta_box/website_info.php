<?php
$metadata = has_meta($post->ID);

$list = [
    'url' => '網址',
    'description' => '網站描述',
    'rest_url' => 'REST 網址',
    'rest_api' => 'REST API'
]
?>
<table>
    <?php
    foreach ($metadata as $data) {
        if (isset($list[$data['meta_key']])) {
            echo '<tr>'
                . '<td>' . $list[$data['meta_key']] . '</td>'
                . '<td>'
                    . '<input type="text" name="meta[' . $data['meta_id'] . '][value]" value="' . esc_attr($data['meta_value']) . '">'
                    . '<input type="hidden" name="meta[' . $data['meta_id'] . '][key]" value="' . esc_attr($data['meta_key']) . '">'
                . '</td>'
                . '</tr>';
        }
    }
    ?>
</table>

<p>
    佈景主題：<?php the_post_list(get_post_meta($post->ID, 'theme')); ?>
</p>
<p>
    外掛：<?php the_post_list(get_post_meta($post->ID, 'plugin')); ?>
</p>
