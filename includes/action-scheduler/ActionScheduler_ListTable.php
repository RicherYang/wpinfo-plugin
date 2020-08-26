<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_ActionScheduler_ListTable extends ActionScheduler_ListTable
{
    protected $timezone;

    public function __construct(ActionScheduler_Store $store, ActionScheduler_Logger $logger, ActionScheduler_QueueRunner $runner)
    {
        $this->timezone = wp_timezone();

        parent::__construct($store, $logger, $runner);

        unset($this->columns['group']);
    }

    public function column_log_entries(array $row)
    {
        $log_entries_html = '<ol>';
        foreach ($row['log_entries'] as $log_entry) {
            $log_entries_html .= $this->get_log_entry_html($log_entry, $this->timezone);
        }
        $log_entries_html .= '</ol>';

        return $log_entries_html;
    }

    protected function get_log_entry_html(ActionScheduler_LogEntry $log_entry, DateTimezone $timezone)
    {
        $date = $log_entry->get_date();
        $date->setTimezone($timezone);
        return sprintf('<li><strong>%s</strong> %s</li>', esc_html($date->format('Y-m-d H:i:s')), esc_html($log_entry->get_message()));
    }
}
