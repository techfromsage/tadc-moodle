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
 *
 * @package    mod
 * @subpackage tadc
 * @copyright  2013 Talis Education Ltd
 * @license    MIT
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__)."/lib.php");

define('TADC_LTI_LAUNCH_PATH', '/lti/launch');
define('TADC_SOURCE_URI', 'http://schemas.talis.com/tadc/v1/referrers/moodle/2');

$_tadc_client = null;


/**
 * @param stdClass $request
 * @param string $tenant
 * @return array
 */
function tadc_build_request($request)
{
    global $COURSE, $USER;
    $course_code_field = get_config('tadc', 'course_code_field');
    $ts = new DateTime();
    $params = array_merge(array('url_ver'=>'Z39.88-2004', 'url_ctx_fmt'=>'info:ofi/fmt:kev:mtx:ctx', 'url_tim'=>$ts->format(DateTime::ISO8601)), tadc_resource_to_referent($request));
    $rfr_id = new moodle_url('/mod/tadc/view.php', array('t'=>$request->id));
    $params['rfr_id'] = $rfr_id->out();
    $params['rfr.type'] = 'http://schemas.talis.com/tadc/v1/referrers/moodle/1';

    $startDate = @$request->course_start ? $request->course_start : $COURSE->startdate;
    $endDate = @$request->course_end ? $request->course_end : get_course_end($COURSE);

    $size = @$request->expected_enrollment;
    if(empty($size))
    {
        $context = context_course::instance($COURSE->id);
        $size = count(get_enrolled_users($context));
    }
    $params = array_merge($params, array(
        'rfe.code'=>tadc_format_course_id_for_tadc($COURSE->$course_code_field),
        'rfe.name'=>$COURSE->fullname,
        'rfe.sdate'=>date_format_string($startDate, '%Y-%m-%d'),
        'rfe.edate'=>date_format_string($endDate, '%Y-%m-%d'),
        'req.email'=>$USER->email,
        'rfe.size'=>$size,
        'req.name'=>$USER->firstname . ' ' . $USER->lastname
    ));
    if($params['rfe.size'] == 0) // or false, or null
    {
        $params['rfe.size'] = 1;
    }

    return $params;
}

function get_course_end($course)
{
    global $DB;
    $context = context_course::instance($course->id);
    $enrolled_users = get_enrolled_users($context);
    // flail about to get some semblance of course end date
    $courseLength = 0;
    foreach($enrolled_users as $user)
    {
        if($endTimeStamp = enrol_get_enrolment_end($course->id, $user->id))
        {
            if($endTimeStamp > $courseLength)
            {
                $courseLength = $endTimeStamp;
            }
        }
    }

    if($courseLength == 0)
    {
        $enrolment = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'),'*');
        if($enrolment)
        {
            $courseLength = $enrolment->enrolperiod;
        }
        if($courseLength !== 0)
        {
            $courseLength = $course->startdate + $courseLength;
        }
    }
    return $courseLength;
}

/**
 * Generates a TADC request from a request resource, sends the request and returns the response from the TADC service
 *
 * @param stdClass $request
 * @return array
 */
function tadc_submit_request_form($request)
{
    $tadc = get_config('tadc');
    $params = tadc_build_request($request, $tadc->tenant_code);
    $params['svc.trackback'] = $tadc->trackback_endpoint . '&itemUri=' . $request->id . '&api_key='.urlencode($tadc->api_key);
    $params['svc.metadata'] = 'request';
    if(@$request->referral_message)
    {
        $params['svc.refer'] = 'true';
        $params['svc.message'] = $request->referral_message;
    }
    if(@$request->tadc_id)
    {
        $params['ctx_id'] = $tadc->tadc_location . $tadc->tenant_code . "/request/" . $request->tadc_id;
    }
    $client = new \mod_tadc_tadcclient($tadc->tadc_location . $tadc->tenant_code, $tadc->api_key, $tadc->tadc_shared_secret);
    $response = $client->submit_request($params);
    return json_decode($response, true);
}


/**
 * Generate an HTML citation for the digitisation request
 * @deprecated
 * @param stdClass $tadc
 * @return string
 */
function tadc_generate_html_citation($tadc)
{
    $requestMarkup = '';
    if(@$tadc->section_creator && $tadc->section_creator != @$tadc->container_creator)
    {
        $requestMarkup .= $tadc->section_creator . ' ';
    } elseif ((!@$tadc->section_creator && @$tadc->container_creator) || (@$tadc->section_creator && @$tadc->section_creator === @$tadc->container_creator))
    {
        $requestMarkup .= $tadc->container_creator . ' ';
    }
    if(@$tadc->publication_date)
    {
        $requestMarkup .= $tadc->publication_date . ' ';
    }
    if(@$tadc->section_title)
    {
        $requestMarkup .= "'" . $tadc->section_title . "' ";
    }
    if(@$tadc->type === 'book' && @$tadc->section_title && (@$tadc->container_title || @$tadc->container_identifier))
    {
        $requestMarkup .= 'in ';
    }
    if(@$tadc->container_title)
    {
        $requestMarkup .= '<em>' . $tadc->container_title . '</em>';
        if(@$tadc->edition)
        {
            $requestMarkup .= ', ' . $tadc->edition;
        }
        $requestMarkup .= ', ';
    } elseif(@$tadc->container_identifier)
    {
        $requestMarkup .= '<em>' . preg_replace('/^(\w*:)/e', 'strtoupper("$0") . " "', $tadc->container_identifier) . '</em>, ';
    }
    if(@$tadc->volume)
    {
        $requestMarkup .= 'vol. ' . $tadc->volume . ', ';
    }

    if(@$tadc->issue)
    {
        $requestMarkup .= 'no. ' . $tadc->issue . ', ';
    }

    if(@$tadc->section_creator && @$tadc->container_creator && (@$tadc->section_creator !== @$tadc->container_creator))
    {
        $requestMarkup .= $tadc->container_creator . ' ';
    }
    if(@$tadc->type === 'book' && @$tadc->publisher)
    {
        $requestMarkup .= $tadc->publisher . ' ';
    }
    if(@$tadc->start_page && @$tadc->end_page)
    {
        $requestMarkup .= 'pp. ' . $tadc->start_page . '-' . $tadc->end_page;
    } elseif(@$tadc->start_page)
    {
        $requestMarkup .= 'p. ' . $tadc->start_page;
    }

    return chop(trim($requestMarkup),",") . '.';
}


function tadc_add_lti_properties(stdClass &$tadc)
{
    $pluginSettings = get_config('tadc');

    $tadc->toolurl = tadc_generate_launch_url($pluginSettings->tadc_location, $pluginSettings->tenant_code);
    $tadc->instructorchoiceacceptgrades = false;
    $tadc->instructorchoicesendname = true;
    $tadc->instructorchoicesendemailaddr = true;
    $tadc->instructorchoiceallowroster = false;
    $tadc->launchcontainer = null;
    $tadc->servicesalt = uniqid('', true);
    $course = get_course($tadc->course);
    $customLTIParams = array('launch_identifier='.uniqid());
    $baseCourseCode = $course->{$pluginSettings->course_code_field};

    if(isset($pluginSettings->course_code_regex))
    {
        if(preg_match("/".$pluginSettings->course_code_regex."/", $baseCourseCode, $matches))
        {
            if(!empty($matches) && isset($matches[1]))
            {
                $baseCourseCode = $matches[1];
            }
        }
    }
    $customLTIParams[] = 'course_code='.$baseCourseCode;
    $customLTIParams[] = 'course_name='.$course->fullname;
    $customLTIParams[] = 'course_start='.date('Y-m-d', $course->startdate);
    $customLTIParams[] = 'source=' . TADC_SOURCE_URI;
    $customLTIParams[] = 'trackback=' . urlencode(new moodle_url('/mod/tadc/trackback.php', array('t'=>$tadc->id, 'api_key'=>$pluginSettings->api_key)));
    $tadc->resourcekey = $pluginSettings->api_key;
    $tadc->password = $pluginSettings->tadc_shared_secret;
    $tadc->instructorcustomparameters= implode("\n", $customLTIParams);
    $tadc->debuglaunch = false;
}

function tadc_generate_launch_url($tadcHost, $tadcTenantCode)
{
    if(substr($tadcHost, -1) !== "/")
    {
        $tadcHost .= "/";
    }
    return $tadcHost . $tadcTenantCode . TADC_LTI_LAUNCH_PATH;
}

function tadc_do_lti_launch(stdClass $tadc)
{
    global $PAGE, $CFG;

    if(!isset($tadc->resourcekey))
    {
        tadc_add_lti_properties($tadc);
    }

    //There is no admin configuration for this tool. Use configuration in the tadc instance record plus some defaults.
    $lticonfig = (array)$tadc;

    $lticonfig['sendname'] = $tadc->instructorchoicesendname;
    $lticonfig['sendemailaddr'] = $tadc->instructorchoicesendemailaddr;
    $lticonfig['customparameters'] = $tadc->instructorcustomparameters;
    $lticonfig['acceptgrades'] = $tadc->instructorchoiceacceptgrades;
    $lticonfig['allowroster'] = $tadc->instructorchoiceallowroster;
    $lticonfig['forcessl'] = '0';

    //Default the organizationid if not specified
    if (empty($lticonfig['organizationid'])) {
        $urlparts = parse_url($CFG->wwwroot);

        $lticonfig['organizationid'] = $urlparts['host'];
    }
    $key = $tadc->resourcekey;
    $secret = $tadc->password;
    $endpoint = $tadc->toolurl;
    $endpoint = trim($endpoint);

    //If the current request is using SSL and a secure tool URL is specified, use it
    if (lti_request_is_using_ssl() && !empty($tadc->securetoolurl)) {
        $endpoint = trim($tadc->securetoolurl);
    }

    //If SSL is forced, use the secure tool url if specified. Otherwise, make sure https is on the normal launch URL.
    if ($lticonfig['forcessl'] == '1') {
        if (!empty($tadc->securetoolurl)) {
            $endpoint = trim($tadc->securetoolurl);
        }

        $endpoint = lti_ensure_url_is_https($endpoint);
    } else {
        if (!strstr($endpoint, '://')) {
            $endpoint = 'http://' . $endpoint;
        }
    }

    $orgid = $lticonfig['organizationid'];

    $course = $PAGE->course;
    $requestparams = lti_build_request($tadc, $lticonfig, $course);

    $launchcontainer = lti_get_launch_container($tadc, $lticonfig);
    $returnurlparams = array('course' => $course->id, 'launch_container' => $launchcontainer, 'instanceid' => $tadc->id);

    if ( $orgid ) {
        $requestparams["tool_consumer_instance_guid"] = $orgid;
    }

    if (empty($key) || empty($secret)) {
        $returnurlparams['unsigned'] = '1';
    }

    // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
    $url = new moodle_url('/mod/tadc/return.php', $returnurlparams);
    $returnurl = $url->out(false);

    if ($lticonfig['forcessl'] == '1') {
        $returnurl = lti_ensure_url_is_https($returnurl);
    }

    $requestparams['launch_presentation_return_url'] = $returnurl;

    if (!empty($key) && !empty($secret)) {
        $parms = lti_sign_parameters($requestparams, $endpoint, "POST", $key, $secret);

        $endpointurl = new moodle_url($endpoint);
        $endpointparams = $endpointurl->params();

        // Strip querystring params in endpoint url from $parms to avoid duplication.
        if (!empty($endpointparams) && !empty($parms)) {
            foreach (array_keys($endpointparams) as $paramname) {
                if (isset($parms[$paramname])) {
                    unset($parms[$paramname]);
                }
            }
        }

    } else {
        //If no key and secret, do the launch unsigned.
        $parms = $requestparams;
    }

    $debuglaunch = ( $tadc->debuglaunch == 1 );

    $content = lti_post_launch_html($parms, $endpoint, $debuglaunch);

    echo $content;    
}



function tadc_format_course_id_for_tadc($courseId)
{
    $tadc_cfg = get_config('tadc');
    if($tadc_cfg->course_code_format === '%COURSE_CODE%')
    {
        return $courseId;
    }
    foreach(explode('%COURSE_CODE%', $tadc_cfg->course_code_format) as $cruft)
    {
        $courseId = preg_replace("/" . $cruft . "/", "", $courseId);
    }
    return $courseId;
}

function tadc_courses_from_tadc_course_id($courseId)
{
    global $DB;
    $tadc_cfg = get_config('tadc');
    $courseId = str_replace('%COURSE_CODE%', $courseId, $tadc_cfg->course_code_format);
    $rel = ($tadc_cfg->course_code_format === '%COURSE_CODE%' ? '=' : 'REGEXP');
    return $DB->get_records_select('course', $tadc_cfg->course_code_field . " $rel ?", array($courseId));
}

function tadc_verify_request_signature($secret, array $args = array())
{
    // Generate our key to sign
    $keys = array();
    foreach(array_keys($args) as $key)
    {
        $keys[] = urlencode($key);
    }
    $values = array();
    foreach(array_values($args) as $value)
    {
        $values[] = urlencode($value);
    }

    $params = array_combine($keys, $values);

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1)
    uksort($params, 'strcmp');

    $pairs = array();
    foreach ($params as $parameter => $value) {
        if (is_array($value)) {
            // If two or more parameters share the same name, they are sorted by their value
            // Ref: Spec: 9.1.1 (1)
            natsort($value);
            foreach ($value as $duplicate_value) {
                $pairs[] = $parameter . '=' . $duplicate_value;
            }
        } else {
            $pairs[] = $parameter . '=' . $value;
        }
    }
    $key = urlencode(implode("&", $pairs));
    return hash_hmac('sha256', $key, $secret);
}
/**
 * A client class to interact with the TADC service
 *
 * Class mod_tadc_tadcclient
 */
class mod_tadc_tadcclient {
    private $_conn;
    private $_base_url;
    private $_key;
    private $_secret;

    /**
     * @param string $tadc_location
     * @param string $api_key
     * @param string $shared_secret
     */
    public function __construct($tadc_location, $api_key, $shared_secret)
    {
        $this->_base_url = $tadc_location;
        $this->_key = $api_key;
        $this->_secret = $shared_secret;
        $this->_conn = new \Curl(array('cache'=>false, 'debug'=>false));
    }

    /**
     * Generates the api signature and POSTs a request to the TADC service
     *
     * @param $params
     * @return bool
     */
    public function submit_request($params)
    {
        $params['res.signature'] = $this->generate_hash($params);
        $params['res.api_key'] = $this->_key;
        return $this->_conn->post($this->_base_url . '/request/', $this->generate_query_string($params), array('httpheader'=>array('Accept: application/json')));
    }

    /**
     * Generates the API signature from the query vars.  This SHOULD NOT contain 'api_key' or 'signature'
     *
     * @param array $params
     * @return string
     */
    private function generate_hash(array $params)
    {
        $values = array($this->_key);
        $keys = array_keys($params);
        sort($keys);
        foreach($keys as $key)
        {
            $values[] = $params[$key];
        }
        return hash_hmac('sha256', implode('|',$values), $this->_secret);
    }

    /**
     * OpenURL requests don't conform to PHP's built in query var generator, so we have to build the query string
     * manually
     *
     * @param array $params
     * @return string
     */
    private function generate_query_string(array $params)
    {
        $query = array();
        foreach($params as $key=>$val)
        {
            array_push($query, $key . '=' . urlencode($val));
        }
        return implode('&', $query);
    }
}