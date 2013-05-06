<?php
require_once('../../config.php');
require_once('lib.php');


// Make sure this is a legitimate posting

if (!$formdata = data_submitted() or !confirm_sesskey()) {
    print_error('cannotcallscript');
}

$id = required_param('id', PARAM_INT);    // Course Module ID

$referralMessage = required_param('referralMessage', PARAM_TEXT);

if (! $cm = get_coursemodule_from_id('tadc', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('tadcmisconf');
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
//require_capability('mod/survey:participate', $context);

if (! $tadc = $DB->get_record("tadc", array("id"=>$cm->instance))) {
    print_error('invalidtadcid', 'survey');
}

$tadc->resubmit = true;
$tadc->referral_message = $referralMessage;
$tadc->instance = $cm->instance;

tadc_update_instance($tadc);

redirect(new moodle_url("/mod/tadc/view.php", array('id'=>$cm->id)));