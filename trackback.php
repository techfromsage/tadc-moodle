<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/tadc/lib.php');
require_once($CFG->dirroot.'/mod/tadc/locallib.php');

$args = array();
$args['t'] = required_param('t', PARAM_INT);
$args['status'] = required_param('status',PARAM_TEXT);
$args['request'] = required_param('request', PARAM_INT);
$args['api_key'] = required_param('api_key', PARAM_TEXT);

$args['reason_code'] = optional_param('reason_code',NULL,PARAM_TEXT);
$args['status_message'] = optional_param('status_message', NULL, PARAM_TEXT);
$args['bundle_id'] = optional_param('bundle_id', NULL, PARAM_TEXT);
$args['citation'] = optional_param('citation', NULL, PARAM_TEXT);

$args = array_filter($args);
$signature = required_param('signature', PARAM_TEXT);
$config = get_config('tadc');

if($config->api_key !== $args['api_key'])
{
    error_log("API Key does not match");
    die("API Key does not match");
}

if($signature !== tadc_verify_request_signature($config->tadc_shared_secret, $args))
{
    error_log("Signature does not match expected: " . tadc_verify_request_signature($config->tadc_shared_secret, $args));
    die("Signature does not match");
}

$tadc  = $DB->get_record('tadc', array('id' => $args['t']), '*', IGNORE_MISSING);
if($tadc)
{
    $course     = $DB->get_record('course', array('id' => $tadc->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('tadc', $tadc->id, $course->id, false, MUST_EXIST);

    $tadc->request_status = $args['status'];
    $tadc->tadc_id = $args['request'];
    if(isset($args['reason_code']))
    {
        $tadc->reason_code = $args['reason_code'];
    }
    if(isset($args['status_message']))
    {
        $tadc->status_message = $args['status_message'];
    }
    if(isset($args['citation']))
    {
        $tadc->citation = base64_decode($args['citation']);
        $tadc->citationformat = FORMAT_HTML;
    }
    if(isset($args['bundle_id']))
    {
        $tadc->bundle_url = $args['bundle_id'];
    }

    tadc_update_instance($tadc);
}