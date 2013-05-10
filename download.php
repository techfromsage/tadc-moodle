<?php
require_once('../../config.php');
require_once('lib.php');


$id = optional_param('id', null, PARAM_INT);    // Course Module ID
$t = optional_param('t', null, PARAM_INT);
$courseCode = optional_param('code', null, PARAM_TEXT);
$bundleId = optional_param('bundleId', null, PARAM_TEXT);

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
    $course  = $DB->get_record('course', array('idnumber' => $courseCode), '*', MUST_EXIST);
    $tadc     = $DB->get_record_select('tadc', $DB->sql_compare_text('bundle_url') . " = ? AND course = ?", array($bundleId, $course->id), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('tadc', $tadc->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
global $USER;
//$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$context = context_module::instance($cm->id);
if(has_capability('mod/tadc:updateinstance', $context) || is_enrolled($context, null, '', true))
{
    $curl = new curl();
    $tadc_cfg = get_config('tadc');

    date_default_timezone_set('UTC');
    $curl->setopt(array('HTTPAUTH'=>CURLAUTH_DIGEST, 'USERPWD'=>$course->shortname . ":" . hash_hmac('sha256', $course->shortname.$tadc->bundle_url.date('Y-m-d'), $tadc_cfg->tadc_shared_secret)));
    $response = $curl->get($tadc_cfg->tadc_location . $tadc_cfg->tenant_code . '/bundles/' . $tadc->bundle_url . '/download');
    $info = $curl->get_info();
    if(@$info['http_code'] === 200)
    {
        header('Content-type: ' . $info['content_type']);
        echo($response);
    } else {
        http_response_code((@$info['http_code']) ? $info['http_code'] : 500);
        echo($response);
    }
} else {
    header("HTTP/1.1 403");
    echo("Unauthorized to download file");
}
