<?php
class RY_WPI_ActionScheduler_ListTable extends ActionScheduler_ListTable
{
    protected static $timezone;

    private static $time_periods;

    public function __construct(ActionScheduler_Store $store, ActionScheduler_Logger $logger, ActionScheduler_QueueRunner $runner)
    {
        self::$timezone = wp_timezone();

        parent::__construct($store, $logger, $runner);

        self::$time_periods = [
            [
                'seconds' => YEAR_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s year', '%s years', 'wpinfo-plugin'),
            ],
            [
                'seconds' => MONTH_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s month', '%s months', 'wpinfo-plugin'),
            ],
            [
                'seconds' => WEEK_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s week', '%s weeks', 'wpinfo-plugin'),
            ],
            [
                'seconds' => DAY_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s day', '%s days', 'wpinfo-plugin'),
            ],
            [
                'seconds' => HOUR_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s hour', '%s hours', 'wpinfo-plugin'),
            ],
            [
                'seconds' => MINUTE_IN_SECONDS,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s minute', '%s minutes', 'wpinfo-plugin'),
            ],
            [
                'seconds' => 1,
                /* translators: %s: amount of time */
                'names'   => _n_noop('%s second', '%s seconds', 'wpinfo-plugin'),
            ],
        ];
    }

    public function column_log_entries(array $row)
    {
        $log_entries_html = '<ol>';
        foreach ($row['log_entries'] as $log_entry) {
            $log_entries_html .= $this->get_log_entry_html($log_entry, self::$timezone);
        }
        $log_entries_html .= '</ol>';

        return $log_entries_html;
    }

    protected function get_log_entry_html(ActionScheduler_LogEntry $log_entry, DateTimezone $timezone)
    {
        $date = $log_entry->get_date();
        $date->setTimezone($timezone);
        return sprintf('<li><strong>%s</strong><br/>%s</li>', esc_html($date->format('Y-m-d H:i:s')), esc_html($log_entry->get_message()));
    }

    protected function get_schedule_display_string(ActionScheduler_Schedule $schedule)
    {
        $schedule_display_string = '';

        if (!$schedule->get_date()) {
            return '0000-00-00 00:00:00';
        }

        $next_timestamp = $schedule->get_date()->getTimestamp();
        $schedule_display_string .= $schedule->get_date()->format('Y-m-d H:i:s');
        $schedule_display_string .= '<br>';

        if (gmdate('U') > $next_timestamp) {
            $schedule_display_string .= sprintf(' (%s 前)', self::human_interval(gmdate('U') - $next_timestamp));
        } else {
            $schedule_display_string .= sprintf(' (%s)', self::human_interval($next_timestamp - gmdate('U')));
        }

        return $schedule_display_string;
    }

    private static function human_interval($interval, $periods_to_include = 2)
    {
        if ($interval <= 0) {
            return '現在';
        }

        $output = '';

        for ($time_period_index = 0, $periods_included = 0, $seconds_remaining = $interval; $time_period_index < count(self::$time_periods) && $seconds_remaining > 0 && $periods_included < $periods_to_include; $time_period_index++) {
            $periods_in_interval = floor($seconds_remaining / self::$time_periods[ $time_period_index ]['seconds']);

            if ($periods_in_interval > 0) {
                if (! empty($output)) {
                    $output .= ' ';
                }
                $output .= sprintf(_n(self::$time_periods[ $time_period_index ]['names'][0], self::$time_periods[ $time_period_index ]['names'][1], $periods_in_interval, 'wpinfo-plugin'), $periods_in_interval);
                $seconds_remaining -= $periods_in_interval * self::$time_periods[ $time_period_index ]['seconds'];
                $periods_included++;
            }
        }

        return $output;
    }
}
