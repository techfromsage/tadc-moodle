<?php

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/tadc/locallib.php');
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID

$cm = get_coursemodule_from_id('tadc', $id, 0, false, MUST_EXIST);
$tadc = $DB->get_record('tadc', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

add_to_log($course->id, "tadc", "launch", "launch.php?id=$cm->id", "$tadc->id");

$tadc->cmid = $cm->id;
tadc_add_lti_properties($tadc);
tadc_do_lti_launch($tadc);
