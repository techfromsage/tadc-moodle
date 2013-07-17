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
    if ($oldversion < 2013050604)
    {
        upgrade_mod_savepoint(true, 2013050604, 'tadc');
    }

    if($oldversion < 2013051301)
    {
        $table = new xmldb_table('tadc');
        $field = new xmldb_field('edition', XMLDB_TYPE_TEXT, null, null, null, null, null, 'needed_by');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('bundle_url', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'needed_by');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
        }

        $index = new xmldb_index('bundle_url_idx', null, array('bundle_url'));
        if(!$dbman->index_exists($table, $index))
        {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2013051301, 'tadc');
    }
    if ($oldversion < 2013051302)
    {
        upgrade_mod_savepoint(true, 2013051302, 'tadc');
    }
    if ($oldversion < 2013052409)
    {
        upgrade_mod_savepoint(true, 2013052409, 'tadc');
    }
    if ($oldversion < 2013060701)
    {
        $table = new xmldb_table('tadc');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'other_response_data');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, 2, null, null, null, null, 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
        }

        upgrade_mod_savepoint(true, 2013060701, 'tadc');
    }
    if ($oldversion < 2013061001)
    {
        upgrade_mod_savepoint(true, 2013061001, 'tadc');
    }

    if ($oldversion < 2013061701) {

        // since renaming isn't supported across dbs we need to drop and add the index
        // Drop old course_id_idx....
        // Define index course_id_idx (not unique) to be dropped form tadc
        $table = new xmldb_table('tadc');
        $index = new xmldb_index('course_id_idx', XMLDB_INDEX_NOTUNIQUE, array('course_id'));

        // Conditionally launch drop index course_id_idx
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }


        // rename the field in the table
        // Rename field course_id on table tadc to course
        $table = new xmldb_table('tadc');
        $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if($dbman->field_exists($table, $field))
        {
            // Launch rename field course_id
            $dbman->rename_field($table, $field, 'course');
        } else {
            $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
            if(!$dbman->field_exists($table, $field))
            {
                $dbman->add_field($table, $field);
            }
        }



        // Add the index back but with new name

        // Define index course_id_idx (not unique) to be added to tadc
        $table = new xmldb_table('tadc');
        $index = new xmldb_index('course_idx', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch add index course_id_idx
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }


        // tadc savepoint reached
        upgrade_mod_savepoint(true, 2013061701, 'tadc');
    }

    if($oldversion < 2013071201)
    {
        upgrade_mod_savepoint(true, 2013071201, 'tadc');
    }
}