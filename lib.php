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
 * Library of interface functions and constants for module tadc
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the tadc specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage tadc
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/tadc.php');

/** example constant */
//define('tadc_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function tadc_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return false;
        case FEATURE_MOD_ARCHETYPE:     return MOD_ARCHETYPE_RESOURCE;
        default:                        return null;
    }
}

/**
 * Saves a new instance of the tadc into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $tadc An object from the form in mod_form.php
 * @param mod_tadc_mod_form $mform
 * @return int The id of the newly inserted tadc record
 */
function tadc_add_instance(stdClass $tadc, mod_tadc_mod_form $mform = null) {
    global $DB;

    if(@$tadc->doi)
    {
        $tadc->document_identifier = 'doi:' . $tadc->doi;
    } elseif(@$tadc->pmid)
    {
        $tadc->document_identifier = 'pmid:' . $tadc->pmid;
    }

    if(@$tadc->isbn)
    {
        $tadc->container_identifier = 'isbn:' . $tadc->isbn;
    } elseif(@$tadc->issn)
    {
        $tadc->container_identifier = 'issn:' . $tadc->issn;
    }

    $tadc->id = $DB->insert_record('tadc', $tadc);
    $response = tadc_submit_request_form($tadc);
    $id = explode("/", $response['id']);
    $tadc->request_id = $id[count($id) - 1];
    $tadc->request_status = $response['status'];
    if(isset($response['message']))
    {
        $tadc->status_message = $response['message'];
    }
    if(isset($response['metadata']))
    {
        $md = $response['metadata'];
        if(@$md['editionTitle'] && !@$tadc->container_title)
        {
            $tadc->container_title = $md['editionTitle'];
        }
        if(@$md['editionCreators'] && !empty($md['editionCreators']) && !@$tadc->container_creator)
        {
            $tadc->container_creator = implode('; ', $md['editionCreators']);
        }
        if($tadc->type === 'book' && @$md['isbn13'] && @!empty($md['isbn13']) && !@$tadc->container_identifier)
        {
            $tadc->container_identifier = 'isbn:' . $md['isbn13'][0];
        }
        if($tadc->type === 'journal' && @$md['issn'] && !@$tadc->container_identifier)
        {
            $tadc->container_identifier = 'issn:' . $md['issn'];
        }
        if(@$md['publisherStrings'] && !empty($md['publisherStrings']) && !@$tadc->publisher)
        {
            $tadc->publisher = $md['publisherStrings'][0];
        }
        if(@$md['publisher'] && !@$tadc->publisher)
        {
            $tadc->publisher = $md['publisher'];
        }
        if(@$md['editionDate'] && !@$tadc->publication_date)
        {
            $tadc->publication_date = $md['editionDate'];
        }
        if(@$md['sectionTitle'] && !@$tadc->section_title)
        {
            $tadc->section_title = $md['sectionTitle'];
        }
        if(@$md['sectionCreators'] && !empty($md['sectionCreators']) && !@$tadc->section_creator)
        {
            $tadc->section_creator = implode("; ", $md['sectionCreators']);
        }
        if(@$md['startPage'] && !@$tadc->start_page)
        {
            $tadc->start_page = $md['startPage'];
        }
        if(@$md['endPage'] && !@$tadc->end_page)
        {
            $tadc->end_page = $md['endPage'];
        }
        if(@$md['doi'] && !@$tadc->document_identifier)
        {
            $tadc->document_identifier = 'doi:' . $md['doi'];
        } elseif(@$md['pmid'] && !@$tadc->document_identifier)
        {
            $tadc->document_identifier = 'pmid:' . $md['pmid'];
        }
        if(@$md['volume'] && !$tadc->volume)
        {
            $tadc->volume = $md['volume'];
        }
        if(@$md['issue'] && !$tadc->issue)
        {
            $tadc->issue = $md['issue'];
        }
    }
    $tadc->name = tadc_build_title_string($tadc);
    $DB->update_record('tadc', $tadc);
    return $tadc->id;
}

/**
 * Updates an instance of the tadc in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $tadc An object from the form in mod_form.php
 * @param mod_tadc_mod_form $mform
 * @return boolean Success/Fail
 */
function tadc_update_instance(stdClass $tadc, mod_tadc_mod_form $mform = null) {
    global $DB;

    $tadc->timemodified = time();
    $tadc->id = $tadc->instance;
    if($tadc->container_identifier)
    {
        $tadc->container_identifier = 'isbn:' . $tadc->container_identifier;
    }

    # You may have to add extra stuff in here #

    return $DB->update_record('tadc', $tadc);
}

/**
 * Removes an instance of the tadc from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function tadc_delete_instance($id) {
    global $DB;

    if (! $tadc = $DB->get_record('tadc', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('tadc', array('id' => $tadc->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function tadc_user_outline($course, $user, $mod, $tadc) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $tadc the module instance record
 * @return void, is supposed to echp directly
 */
function tadc_user_complete($course, $user, $mod, $tadc) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in tadc activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function tadc_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link tadc_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function tadc_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see tadc_get_recent_mod_activity()}

 * @return void
 */
function tadc_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function tadc_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function tadc_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding tadc nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the tadc module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function tadc_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the tadc settings
 *
 * This function is called when the context for the page is a tadc module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $tadcnode {@link navigation_node}
 */
function tadc_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $tadcnode=null) {
}

function tadc_resource_to_referent($resource)
{
    $params = array();
    if(@$resource->section_title) { $params['rft.atitle'] = $resource->section_title; }
    if($resource->type == 'book')
    {
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        if(@$resource->container_title)
        {
            $params['rft.btitle'] = $resource->container_title;
        }
    } else {
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        if(@$resource->container_title)
        {
            $params['rft.jtitle'] = $resource->container_title;
        }
    }

    if(@$resource->container_identifier)
    {
        list($idType, $id) = explode(":", $resource->container_identifier, 2);
        $params['rft.' . strtolower($idType)] = $id;
    }
    if(@$resource->document_identifier)
    {
        list($idType, $id) = explode(":", $resource->document_identifier, 2);
        $params['rft.' . strtolower($idType)] = $id;
        $params['rft_id'] = 'info:' . $idType . '/' . $id;
    }
    $creators = array();
    if(@$resource->section_creator)
    {
        $creators[] = $resource->section_creator;
    }
    if(@$resource->container_creator)
    {
        $creators[] = $resource->container_creator;
    }
    if(!empty($creators))
    {
        $params['rft.au'] = implode("; ", $creators);
    }
    if(@$resource->needed_by)
    {
        $params['svc.needed_by'] = date_format_string($resource->needed_by, '%Y-%m-%d');
    }
    return $params;
}

function tadc_build_request($request, $tenant)
{
    global $CFG, $DB, $COURSE, $USER;

    $params = array_merge(array('url_ver'=>'Z39.88-2004', 'url_ctx_fmt'=>'info:ofi/fmt:kev:mtx:ctx'), tadc_resource_to_referent($request));
    $course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);
    $params['rfr_id'] = 'info:talisaspire/tadc:' . $tenant . ':moodle:' . $COURSE->id;

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

    $startDate = date_format_string($course->startdate, '%Y-%m-%d');
    $endDate = date_format_string($course->startdate + $enrolment->enrolperiod, '%Y-%m-%d');
    $params = array_merge($params, array(
        'rfe.code'=>$COURSE->shortname,
        'rfe.name'=>$COURSE->fullname,
        'rfe.sdate'=>$startDate,
        'rfe.edate'=>$endDate,
        'req.email'=>$USER->email,
        'rfe.size'=>$count,
        'req.name'=>$USER->firstname . ' ' . $USER->lastname
    ));
    if($params['rfe.size'] == 0) // or false, or null
    {
        $params['rfe.size'] = 1;
    }

    return $params;
}
function tadc_submit_request_form($request)
{
    $tadc = get_config('tadc');
    $params = tadc_build_request($request, $tadc->tenant_code);
    $params['svc.trackback'] = $tadc->trackback_endpoint . '&itemUri=' . $request->id;
    $params['svc.metadata'] = 'request';
    $client = new tadc($tadc->tadc_location . $tadc->tenant_code, $tadc->tadc_shared_secret);
    $response = $client->submit_request($params);
    return json_decode($response, true);
}

function tadc_build_title_string($tadc)
{
    $title = '';
    if(@$tadc->section_title)
    {
        $title .= $tadc->section_title;
    }
    if(@$tadc->section_title && (@$tadc->container_title || @$tadc->container_identifier))
    {
        $title .= ' from ';
    }
    if(@$tadc->container_title)
    {
        $title .= $tadc->container_title . ', ';
    } elseif(@$tadc->container_identifier)
    {
        $title .= preg_replace('/^(\w*:)/e', 'strtoupper("$0") . " "', $tadc->container_identifier) . ', ';
    }
    if(@$tadc->start_page && @$tadc->end_page)
    {
        $title .= 'pp. ' . $tadc->start_page . '-' . $tadc->end_page;
    } elseif(@$tadc->start_page)
    {
        $title .= 'p.' . $tadc->start_page;
    }
    return $title;
}