<?php
$metadata = has_meta($post->ID);

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
