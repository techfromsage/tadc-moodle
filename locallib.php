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
 * Internal library of functions for module tadc
 *
 * All the tadc specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage tadc
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/mod/tadc/lib.php");

/**
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 */
//function tadc_do_something_useful(array $things) {
//    return new stdClass();
//}
function generateRequestForm()
{
    global $CFG, $DB, $COURSE, $USER, $OUTPUT;
    $course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);
    $enrolled = $DB->get_records_sql("

SELECT c.id, u.id

FROM {course} c
JOIN {context} ct ON c.id = ct.instanceid
JOIN {role_assignments} ra ON ra.contextid = ct.id
JOIN {user} u ON u.id = ra.userid
JOIN {role} r ON r.id = ra.roleid

where c.id = " . $COURSE->id);

    $enrolment = $DB->get_record('enrol', array('courseid'=>$COURSE->id, 'enrol'=>'manual'),'*', MUST_EXIST);

    $count = count($enrolled) > 0 ? count($enrolled) : null;
    //$startDate = format_time('Y-m-d', $course->startdate);
    //print_object($course->startdate);
    $startDate = date_format_string($course->startdate, '%Y-%m-%d');
    $endDate = date_format_string($course->startdate + $enrolment->enrolperiod, '%Y-%m-%d');
    $requestParams = array('courseCode'=>$COURSE->shortname,
        'courseName'=>$COURSE->fullname,
        'startDate'=>$startDate,
        'endDate'=>$endDate,
        'requesterEmail'=>$USER->email,
        'anticipatedStudentNumbers'=>$count,
        'requesterName'=>$USER->firstname . ' ' . $USER->lastname
    );

    $params = array();

    foreach($requestParams as $key=>$val)
    {
        $params[] = $key . '=' . urlencode($val);
    }

    $formUrl = "http://drp.dev:8080/life/request/generate?" . implode("&", $params);
    //$strrequired = get_string('required');
    $config = get_config('tadc');
    echo('<iframe class="tadc-request-form" id="tadc-request-form" width="100%" height="515" frameborder="0" src="' . $formUrl . '"></iframe>');
}