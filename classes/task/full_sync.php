<?php namespace local_bamboohr\task;

/**
 * An example of a scheduled task.
 */
class full_sync extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Sync from BambooHR directory';
    }

    /**
     * Execute the task.
     */
    public function execute() {
      require_once(dirname(__FILE__) . '/../../lib.php');
      mtrace('Update menu options');
      local_bamboohr_update_menu_options();
      mtrace('Sync from directory listing - update only');
      local_bamboohr_sync_directory();
    }
}
