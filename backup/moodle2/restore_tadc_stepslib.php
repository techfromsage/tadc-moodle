<?php
/**
 * Define all the restore steps that will be used by the restore_tadc_activity_task
 */

/**
 * Structure step to restore one url activity
 */
class restore_tadc_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('tadc', '/activity/tadc');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_tadc($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the url record
        $newitemid = $DB->insert_record('tadc', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        $this->add_related_files('mod_tadc', null, null);
    }
}
