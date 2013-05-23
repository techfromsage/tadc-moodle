<?php

/**
 * Defines backup_tadc_activity_task class
 *
 * @package     mod_tadc
 * @category    backup
 * @copyright   2013 Talis Education Ltd.
 * @license     MIT
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/tadc/backup/moodle2/backup_tadc_stepslib.php');

class backup_tadc_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }
    /**
     * Defines a backup step to store the instance data in the tadc.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_tadc_activity_structure_step('tadc_structure', 'tadc.xml'));
    }
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of tadc resources
        $search="/(".$base."\/mod\/tadc\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@TADCINDEX*$2@$', $content);

        // Link to tadc resource view by moduleid
        $search="/(".$base."\/mod\/tadc\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@TADCVIEWBYID*$2@$', $content);

        return $content;
    }
}