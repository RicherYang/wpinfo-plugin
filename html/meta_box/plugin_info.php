<?php
$metadata = has_meta($post->ID);
$meta_id_key = array_column($metadata, 'meta_id', 'meta_key');
$metadata = array_combine(array_column($metadata, 'meta_id'), $metadata);

$list = [
    'at_org' => '官網上架',
    'url' => '官網',
    'version' => '最新版本',
    'used_count' => '使用網站數',
    'rest_key' => 'REST 辨識關鍵字'
]
?>
<table>
    <?php
    foreach ($list as $key => $name) {
        if (isset($meta_id_key[$key])) {
            echo '<tr>'
                . '<td>' . $name . '</td>'
                . '<td>'
                    . '<input type="text" name="meta[' . $meta_id_key[$key] . '][value]" value="' . esc_attr($metadata[$meta_id_key[$key]]['meta_value']) . '">'
                    . '<input type="hidden" name="meta[' . $meta_id_key[$key] . '][key]" value="' . esc_attr($metadata[$meta_id_key[$key]]['meta_key']) . '">'
                . '</td>'
                . '</tr>';
        }
    }
    ?>
</table>
