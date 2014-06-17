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

/**
 * Attempts to determine the rough end date for the course
 *
 * @param stdClass $course
 * @return bool|int
 */
function tadc_get_course_end(stdClass $course)
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
    if($courseLength === $course->startdate)
    {
        return null;
    }
    return $courseLength;
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
    $sectionCreator = (isset($tadc->section_creator) ? $tadc->section_creator : null);
    $containerCreator = (isset($tadc->container_creator) ? $tadc->container_creator : null);
    $sectionTitle = (isset($tadc->section_title) ? $tadc->section_title : null);
    $containerTitle = (isset($tadc->container_title) ? $tadc->container_title : null);
    $type = (isset($tadc->type) ? $tadc->type : null);
    if(!empty($sectionCreator) && $sectionCreator !== $containerCreator)
    {
        $requestMarkup .= $tadc->section_creator . ' ';
    } elseif (!empty($containerCreator))
    {
        $requestMarkup .= $tadc->container_creator . ' ';
    }
    if(isset($tadc->publication_date) && !empty($tadc->publication_date))
    {
        $requestMarkup .= $tadc->publication_date . ' ';
    }
    if(!empty($sectionTitle))
    {
        $requestMarkup .= "'" . $tadc->section_title . "' ";
    }
    if($type === 'book' && !empty($sectionTitle) && ((!empty($containerTitle)) || (isset($tadc->container_identifier) && !empty($tadc->container_identifier))))
    {
        $requestMarkup .= 'in ';
    }
    if(!empty($containerTitle))
    {
        $requestMarkup .= '<em>' . $tadc->container_title . '</em>';
        if(isset($tadc->edition) && !empty($tadc->edition))
        {
            $requestMarkup .= ', ' . $tadc->edition;
        }
        $requestMarkup .= ', ';
    } elseif(isset($tadc->container_identifier) && !empty($tadc->container_identifier))
    {
        $requestMarkup .= '<em>' . preg_replace('/^(\w*:)/e', 'strtoupper("$0") . " "', $tadc->container_identifier) . '</em>, ';
    }
    if(isset($tadc->volume) && !empty($tadc->volume))
    {
        $requestMarkup .= 'vol. ' . $tadc->volume . ', ';
    }

    if(isset($tadc->issue) && !empty($tadc->issue))
    {
        $requestMarkup .= 'no. ' . $tadc->issue . ', ';
    }

    if(!empty($containerCreator) && !empty($sectionCreator) && ($sectionCreator !== $containerCreator))
    {
        $requestMarkup .= $tadc->container_creator . ' ';
    }
    if($type === 'book' && isset($tadc->publisher) && !empty($tadc->publisher))
    {
        $requestMarkup .= $tadc->publisher . ' ';
    }
    if(isset($tadc->start_page) && !empty($tadc->start_page) && isset($tadc->end_page))
    {
        $requestMarkup .= 'pp. ' . $tadc->start_page. '-' . $tadc->end_page;
    } elseif(isset($tadc->start_page) && !empty($tadc->start_page))
    {
        $requestMarkup .= 'p. ' . $tadc->start_page;
    }

    return chop(trim($requestMarkup),",") . '.';
}

/**
 * Adds properties to a TADC object that replicates an LTI object so we can piggyback off of the existing LTI module
 * @param stdClass $tadc
 */
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
    $endDate = tadc_get_course_end($course);
    if(!empty($endDate))
    {
        $customLTIParams[] = 'course_end='.date('Y-m-d', $endDate);
    }
    $customLTIParams[] = 'source=' . TADC_SOURCE_URI;
    $customLTIParams[] = 'trackback=' . urlencode(new moodle_url('/mod/tadc/trackback.php', array('t'=>$tadc->id, 'api_key'=>$pluginSettings->api_key)));
    $tadc->resourcekey = $pluginSettings->api_key;
    $tadc->password = $pluginSettings->tadc_shared_secret;
    $tadc->instructorcustomparameters= implode("\n", $customLTIParams);
    $tadc->debuglaunch = false;
}

/**
 * LTI launch URL for TADC follows a consistent pattern, based on the url/tenancy code
 *
 * @param $tadcHost
 * @param $tadcTenantCode
 * @return string
 */
function tadc_generate_launch_url($tadcHost, $tadcTenantCode)
{
    if(substr($tadcHost, -1) !== "/")
    {
        $tadcHost .= "/";
    }
    return $tadcHost . $tadcTenantCode . TADC_LTI_LAUNCH_PATH;
}

/**
 * This is replicated for the LTI module, basically because we can't use the LTI return url from lti module, since these
 * aren't *actually* lti resources (meaning stored in the lti table)
 *
 * @param stdClass $tadc
 * @return string
 */
function tadc_do_lti_launch(stdClass $tadc)
{
    global $CFG;

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

    $course = get_course($tadc->course);
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

/**
 * When given a course ID from Moodle, will return the TADC course ID (as specified in the module settings regex)
 *
 * @param string $courseId
 * @return string
 */
function tadc_format_course_id_for_tadc($courseId)
{
    $tadc_cfg = get_config('tadc');
    if($tadc_cfg->course_code_regex === '%COURSE_CODE%')
    {
        return $courseId;
    }
    foreach(explode('%COURSE_CODE%', $tadc_cfg->course_code_regex) as $cruft)
    {
        $courseId = preg_replace("/" . $cruft . "/", "", $courseId);
    }
    return $courseId;
}

/**
 * Finds all Moodle courses that match the regex (if defined) for the course id
 *
 * @param string $courseId
 * @return array
 */
function tadc_courses_from_tadc_course_id($courseId)
{
    global $DB;
    $tadc_cfg = get_config('tadc');
    $courseId = str_replace('%COURSE_CODE%', $courseId, $tadc_cfg->course_code_regex);
    $rel = ($tadc_cfg->course_code_regex === '%COURSE_CODE%' ? '=' : 'REGEXP');
    return $DB->get_records_select('course', $tadc_cfg->course_code_field . " $rel ?", array($courseId));
}

/**
 * This generates the expected HMAC signature in the trackback
 *
 * @param $secret
 * @param array $args
 * @return string
 */
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