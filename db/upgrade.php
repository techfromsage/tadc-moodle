<?php
function xmldb_tadc_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    // Adding completion fields to scorm table
    if ($oldversion < 2013050101) {
        $table = new xmldb_table('tadc');

        $field = new xmldb_field('container_creator', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('status_message', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tadc_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013050101, 'tadc');
    }
}