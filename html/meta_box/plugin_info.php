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
    資訊更新時間：<?=date_i18n('Y-m-d h:i:s', get_post_meta($post->ID, 'info_time', true)) ?>
</p>
