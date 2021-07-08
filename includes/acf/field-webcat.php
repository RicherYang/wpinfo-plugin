<?php

class RY_WPI_Acf_field_webcat extends acf_field
{
    public function initialize()
    {
        $this->name = 'webcat';
        $this->label = 'WPI 網站分類資訊';
        $this->category = 'WPI';
        $this->defaults = [];
    }

    public function render_field($field)
    {
        global $post;

        $terms = get_terms([
            'taxonomy' => 'website-category',
            'object_ids' => $post->ID,
            'orderby' => 'none'
        ]);
        $terms = array_combine(array_column($terms, 'term_id'), $terms);
        $terms[0] = (object) [
            'name' => ''
        ];

        $html = $this->get_info($post->ID, 0, $terms);
        if ($html != '') {
            echo '<table class="wp-list-table widefat striped">'
                . '<thead><tr>'
                    . '<th>分類</th>'
                    . '<th>父分類</th>'
                    . '<th>連結網址</th>'
                    . '<th>文章數量</th>'
                . '</tr></thead>'
                . $html
                . '</table>';
        }
    }

    protected function get_info($website_ID, $parent_category_id, &$terms)
    {
        global $wpdb;

        $html = '';
        $list = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}website_category
            WHERE website_id = $website_ID AND parent_category_id = $parent_category_id
            ORDER BY count DESC");

        if (count($list)) {
            foreach ($list as $info) {
                $html .= '<tr><td>' . ($terms[$info->category_id]->name ?? $info->category_id) . '</td>'
                . '<td>' . ($terms[$info->parent_category_id]->name ?? $info->parent_category_id) . '</td>'
                . '<td>' . $info->url . '</td>'
                . '<td>' . $info->count . '</td>'
                . '<td>' . $info->description . '</td></tr>';
                $html .= $this->get_info($website_ID, $info->category_id, $terms);
            }
        }

        return $html;
    }

    public function render_field_settings($field)
    {
    }

    public function validate_value($valid, $value, $field, $input)
    {
    }
}

acf_register_field_type('RY_WPI_Acf_field_webcat');
