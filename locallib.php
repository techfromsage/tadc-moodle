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
require_once(dirname(__FILE__)."/lib.php");


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
    if(@$resource->start_page)
    {
        $params['rft.spage'] = $resource->start_page;
    }
    if(@$resource->end_page)
    {
        $params['rft.epage'] = $resource->end_page;
    }
    if(@$resource->needed_by)
    {
        $params['svc.neededby'] = date_format_string($resource->needed_by, '%Y-%m-%d');
    }
    return $params;
}

function tadc_build_request($request, $tenant)
{
    global $CFG, $DB, $COURSE, $USER;
    $ts = new DateTime();
    $params = array_merge(array('url_ver'=>'Z39.88-2004', 'url_ctx_fmt'=>'info:ofi/fmt:kev:mtx:ctx', 'url_tim'=>$ts->format(DateTime::ISO8601)), tadc_resource_to_referent($request));
    $course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);

    $params['rfr_id'] = new moodle_url('/mod/tadc/view.php', array('t'=>$request->id));
    $params['rfr.type'] = 'http://schemas.talis.com/tadc/v1/referrers/moodle/1';
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
    $client = new tadcclient($tadc->tadc_location . $tadc->tenant_code, $tadc->api_key, $tadc->tadc_shared_secret);
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
    return chop(trim($title),",");
}

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
        $requestMarkup .= ' in ';
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
        $requestMarkup .= 'p.' . $tadc->start_page;
    }

    return chop(trim($requestMarkup),",") . '.';
}

function tadc_set_resource_values_from_tadc_metadata(stdClass &$tadc, array $md)
{
    if(@$md['editionTitle'] && !@$tadc->container_title)
    {
        $tadc->container_title = $md['editionTitle'];
    }
    if(@$md['editionStatement'] && !@$tadc->edition)
    {
        $tadc->edition = $md['editionStatement'];
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

function tadc_set_resource_values_from_tadc_edition(stdClass &$tadc, array $md)
{
    if(@$md['title'] && !@$tadc->container_title)
    {
        $tadc->container_title = $md['title'];
    }
    if(@$md['creators'] && !empty($md['creators']) && !@$tadc->container_creator)
    {
        $tadc->container_creator = implode('; ', $md['creators']);
    }
    if(@$md['identifiers']['isbn13'] && @!empty($md['identifiers']['isbn13']) && !@$tadc->container_identifier)
    {
        $tadc->container_identifier = 'isbn:' . $md['identifiers']['isbn13'][0];
    }
    if(@$md['publisherStrings'] && !empty($md['publisherStrings']) && !@$tadc->publisher)
    {
        $tadc->publisher = $md['publisherStrings'][0];
    }
    if(@$md['date'] && !@$tadc->publication_date)
    {
        $tadc->publication_date = $md['date'];
    }
}

function tadc_create_new_tadc()
{
    $tadc = new stdClass();
    $tadc->id = null;
    $tadc->course_id = null;
    $tadc->type = null;
    $tadc->section_title = null;
    $tadc->section_creator = null;
    $tadc->start_page = null;
    $tadc->end_page = null;
    $tadc->container_title = null;
    $tadc->document_identifier = null;
    $tadc->container_identifier = null;
    $tadc->publication_date = null;
    $tadc->volume = null;
    $tadc->issue = null;
    $tadc->publisher = null;
    $tadc->needed_by = null;
    $tadc->tadc_id = null;
    $tadc->status_message = null;
    $tadc->request_status = null;
    $tadc->bundle_url = null;
    $tadc->name = null;
    $tadc->container_creator = null;
    $tadc->reason_code = null;
    $tadc->other_response_data = null;
    return $tadc;
}

function tadc_form_identifiers_to_resource_identifiers(stdClass &$tadc)
{
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
}

function tadc_update_resource_with_tadc_response(stdClass &$tadc, array $response)
{

    $other_response_data = array();
    $id = explode("/", $response['id']);
    $tadc->request_id = $id[count($id) - 1];
    $tadc->request_status = $response['status'];
    if(isset($response['message']))
    {
        $tadc->status_message = $response['message'];
    }

    if(isset($response['reason_code']))
    {
        $tadc->reason_code = $response['reason_code'];
    }
    foreach(array('url', 'editions', 'locallyHeldEditionIds', 'errors', 'duplicate_of', 'alternate_editions') as $key)
    {
        if(isset($response[$key]))
        {
            $other_response_data[$key] = $response[$key];
        }
    }
    if(!empty($other_response_data))
    {
        $tadc->other_response_data = json_encode($other_response_data);
    }

    if(isset($response['metadata']))
    {
        tadc_set_resource_values_from_tadc_metadata($tadc, $response['metadata']);
    }
}

function tadc_cm_info_dynamic(cm_info $cm) {
    global $DB;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if($cm->modname === 'tadc')
    {
        $tadc = $DB->get_record('tadc', array('id'=>$cm->instance));
        if($tadc->request_status !== 'LIVE')
        {
            if(!has_capability('mod/tadc:updateinstance', $context))
            {
                $cm->set_user_visible(false);
            }
        }
    }
}

class tadcclient {
    private $_conn;
    private $_base_url;
    private $_key;
    private $_secret;

    public function __construct($tadc_location, $api_key, $shared_secret)
    {
        $this->_base_url = $tadc_location;
        $this->_key = $api_key;
        $this->_secret = $shared_secret;
        $this->_conn = new \Curl(array('cache'=>false, 'debug'=>false));
    }

    public function submit_request($params)
    {
        $params['res.signature'] = $this->generate_hash($params);
        $params['res.api_key'] = $this->_key;
        return $this->_conn->post($this->_base_url . '/request/', $this->generate_query_string($params), array('httpheader'=>array('Accept: application/json')));
    }

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