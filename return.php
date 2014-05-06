<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle the return back to Moodle from the tool provider
 *
 * @package    mod
 * @subpackage lti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/tadc/lib.php');
require_once($CFG->dirroot.'/mod/tadc/locallib.php');

$courseid = required_param('course', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

$errormsg = optional_param('lti_errormsg', '', PARAM_RAW);

$launchcontainer = optional_param('launch_container', LTI_LAUNCH_CONTAINER_WINDOW, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$tadc = $DB->get_record('tadc', array('id' => $instanceid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('tadc', $tadc->id, $tadc->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course);

if (!empty($errormsg)) {
    $url = new moodle_url('/mod/tadc/return.php', array('course' => $courseid));
    $PAGE->set_url($url);

    $pagetitle = strip_tags($course->shortname);
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);

    $PAGE->set_pagelayout('embedded');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($tadc->name, true, array('context' => $context)));

    echo get_string('lti_launch_error', 'lti');

    echo htmlspecialchars($errormsg);

    echo $OUTPUT->footer();
} else {

    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    $url = $courseurl->out();

    //

    //Avoid frame-in-frame action

    //Output a page containing some script to break out of frames and redirect them

    echo '<html><body>';

    $script = "
        <script type=\"text/javascript\">
        //<![CDATA[
            if(window != top){
                top.location.href = '{$url}';
            }
        //]]
        </script>
    ";

    $clickhere = get_string('return_to_course', 'tadc', (object)array('link' => $url));

    $noscript = "
        <noscript>
            {$clickhere}
        </noscript>
    ";

    echo $script;
    echo $noscript;

    echo '</body></html>';
}
