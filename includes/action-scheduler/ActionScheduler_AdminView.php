<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_ActionScheduler_AdminView extends ActionScheduler_AdminView
{
    protected function get_list_table()
    {
        if (null === $this->list_table) {
            $this->list_table = new RY_WPI_ActionScheduler_ListTable(ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner());
            $this->list_table->process_actions();
        }

        return $this->list_table;
    }
}
