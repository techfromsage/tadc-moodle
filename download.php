<?php

/***
 * Allows an institution to use Moodle to determine enrollments in a particular course to restrict the downloading
 * of bundle PDFs
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/filelib.php');
require_once(dirname(__FILE__).'/lib.php');


$id = optional_param('id', null, PARAM_INT);    // Course Module ID
$t = optional_param('t', null, PARAM_INT);
$courseCode = optional_param('code', null, PARAM_TEXT);
$bundleId = optional_param('bundleId', null, PARAM_TEXT);
$tadc_cfg = get_config('tadc');
if(!$tadc_cfg->allow_downloads)
{
    print_error('notavailable');
}
if(!($id || $t || ($courseCode && $bundleId)))
{
    print_error('cannotcallscript');
}

if ($id) {
    $cm         = get_coursemodule_from_id('tadc', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $tadc  = $DB->get_record('tadc', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($t) {
    $tadc  = $DB->get_record('tadc', array('id' => $t), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $tadc->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('tadc', $tadc->id, $course->id, false, MUST_EXIST);
} elseif ($courseCode && $bundleId) {
    $course  = $DB->get_record('course', array($tadc_cfg->course_code_field => $courseCode), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID or course code and bundleID');
}

if(isset($cm))
{
    require_login($course, true, $cm);

    $context = context_module::instance($cm->id);
    $bundleId = $tadc->bundle_url;
} else {
    $context = context_course::instance($course->id);
}
    require_capability('mod/tadc:download', $context);


// Initialize an HTTP client and request the PDF from TADC using digest authentication
$curl = new curl();

// Standardize a timezone between client/server
date_default_timezone_set('UTC');
$courseCodeField = $tadc_cfg->course_code_field;
// Initialize the authorization
$curl->setopt(array('HTTPAUTH'=>CURLAUTH_DIGEST, 'USERPWD'=>$tadc_cfg->api_key . ":" . hash_hmac('sha256', $course->$courseCodeField.$bundleId.date('Y-m-d'), $tadc_cfg->tadc_shared_secret)));
$response = $curl->get($tadc_cfg->tadc_location . $tadc_cfg->tenant_code . '/bundles/' . $bundleId . '/download');
$info = $curl->get_info();
if(@$info['http_code'] === 200)
{
    header('Content-type: ' . $info['content_type']);
    echo($response);
} else {
    header('HTTP/1.1 ' . (@$info['http_code']) ? $info['http_code'] : 500);
    echo($response);
}

