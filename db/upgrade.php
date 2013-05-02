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
    if ($oldversion < 2013050201)
    {
        $table = new xmldb_table('tadc');

        $field = new xmldb_field('reason_code', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'container_creator');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('other_response_data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'reason_code');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013050201, 'tadc');
    }
}