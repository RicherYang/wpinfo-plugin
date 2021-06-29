<?php
class RY_WPI_Admin_ListTable_ErrorLog extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'error-logs',
            'plural' => 'error-logs',
            'ajax' => false,
            'screen' => 'error-logs',
        ]);
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = 15;
        $offset = ($this->get_pagenum() - 1) * $per_page;

        $where_sql = [];
        if (!empty($_REQUEST['pid'])) {
            $where_sql[] = $wpdb->prepare("post_id = %d", $_REQUEST['pid']);
        }
        if (!empty($_REQUEST['hcode'])) {
            $where_sql[] = $wpdb->prepare("http_code = %s", $_REQUEST['hcode']);
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}remote_error";
        if (!empty($where_sql)) {
            $sql .= ' WHERE ' . implode(' AND ', $where_sql);
        }
        $sql .= " ORDER BY remote_error_id DESC LIMIT $offset, $per_page";

        $this->items = $wpdb->get_results($sql);
        $count = (int) $wpdb->get_var("SELECT FOUND_ROWS()");

        $this->set_pagination_args([
            'total_items' => $count,
            'per_page' => $per_page,
            'total_pages' => ceil($count / $per_page),
        ]);
    }

    public function get_views()
    {
        global $wpdb;

        $url = admin_url('admin.php?page=wpi-error-log');
        $hcode = wp_unslash($_REQUEST['hcode'] ?? '');

        $views = [];
        $views['all'] = sprintf(
            '<a href="%1$s"%2$s>全部</a>',
            $url,
            $hcode == '' ? ' class="current"' : '',
        );

        $list = $wpdb->get_results("SELECT count(remote_error_id) AS items, http_code FROM {$wpdb->prefix}remote_error
            GROUP BY http_code
            ORDER BY items DESC");
        foreach ($list as $info) {
            $views[$info->http_code] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$s)</span></a>',
                add_query_arg('hcode', $info->http_code, $url),
                $hcode == $info->http_code ? ' class="current"' : '',
                $info->items,
                $info->http_code
            );
        }

        return $views;
    }

    public function get_columns()
    {
        return [
            'id' => '',
            'url' => '遠端網址',
            'http' => 'HTTP 狀態碼',
            'content' => '錯誤訊息',
            'time' => '建立時間',
        ];
    }

    public function get_sortable_columns()
    {
        return [];
    }

    protected function get_table_classes()
    {
        return ['widefat', 'striped'];
    }

    protected function get_bulk_actions()
    {
        return [];
    }

    protected function column_id($item)
    {
        echo $item->remote_error_id;
    }

    protected function column_url($item)
    {
        echo $item->get_url;
    }

    protected function column_http($item)
    {
        echo $item->http_code;
    }

    protected function column_content($item)
    {
        echo $item->error_content;
    }

    protected function column_time($item)
    {
        echo substr($item->get_date, 0, -3);
    }
}
