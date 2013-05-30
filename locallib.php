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

$_tadc_client = null;
/**
 * Maps a TADC digisation request resource (record) into an OpenURL array
 *
 * @param $resource
 * @return array
 */
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
    if(@$resource->publication_date)
    {
        $params['rft.date'] = $resource->publication_date;
    }
    if(@$resource->publisher)
    {
        $params['rft.pub'] = $resource->publisher;
    }
    if(@$resource->volume)
    {
        $params['rft.volume'] = $resource->volume;
    }
    if(@$resource->issue)
    {
        $params['rft.issue'] = $resource->issue;
    }
    if(@$resource->needed_by)
    {
        $params['svc.neededby'] = date_format_string($resource->needed_by, '%Y-%m-%d');
    }
    return $params;
}

/**
 * @param stdClass $request
 * @param string $tenant
 * @return array
 */
function tadc_build_request($request)
{
    global $CONTEXT, $DB, $COURSE, $USER;
    $course_code_field = get_config('tadc', 'course_code_field');
    $ts = new DateTime();
    $params = array_merge(array('url_ver'=>'Z39.88-2004', 'url_ctx_fmt'=>'info:ofi/fmt:kev:mtx:ctx', 'url_tim'=>$ts->format(DateTime::ISO8601)), tadc_resource_to_referent($request));
    $rfr_id = new moodle_url('/mod/tadc/view.php', array('t'=>$request->id));
    $params['rfr_id'] = $rfr_id->out();
    $params['rfr.type'] = 'http://schemas.talis.com/tadc/v1/referrers/moodle/1';

    $context = context_course::instance($COURSE->id);
    $enrolled_users = get_enrolled_users($context);
    // flail about to get some semblance of course end date
    $courseLength = 0;
    foreach($enrolled_users as $user)
    {
        if($endTimeStamp = enrol_get_enrolment_end($COURSE->id, $user->id))
        {
            if($endTimeStamp > $courseLength)
            {
                $courseLength = $endTimeStamp;
            }
        }
    }

    if($courseLength == 0)
    {
        $enrolment = $DB->get_record('enrol', array('courseid'=>$COURSE->id, 'enrol'=>'manual'),'*');
        if($enrolment)
        {
            $courseLength = $enrolment->enrolperiod;
        }
    }

    $startDate = date_format_string($COURSE->startdate, '%Y-%m-%d');
    $endDate = date_format_string($COURSE->startdate + $courseLength, '%Y-%m-%d');
    $params = array_merge($params, array(
        'rfe.code'=>$COURSE->$course_code_field,
        'rfe.name'=>$COURSE->fullname,
        'rfe.sdate'=>$startDate,
        'rfe.edate'=>$endDate,
        'req.email'=>$USER->email,
        'rfe.size'=>count($enrolled_users),
        'req.name'=>$USER->firstname . ' ' . $USER->lastname
    ));
    if($params['rfe.size'] == 0) // or false, or null
    {
        $params['rfe.size'] = 1;
    }

    return $params;
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
 * Builds the 'name' property for a digitisation request
 *
 * @param stdClass $tadc
 * @return string
 */
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
        $title .= 'p. ' . $tadc->start_page;
    }
    return chop(trim($title),",");
}

/**
 * Generate an HTML citation for the digitisation request
 *
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

/**
 * Take a TADC submission response and applies the returned metadata to the digitisation request resource
 *
 * @param stdClass $tadc
 * @param array $md
 */
function tadc_set_resource_values_from_tadc_metadata(stdClass &$tadc, array $md)
{
    if(@$md['type'] === 'Article' && !@$tadc->type)
    {
        $tadc->type = 'journal';
    }
    if(@$md['type'] !== 'Article' && !@$tadc->type)
    {
        $tadc->type = 'book';
    }
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

/**
 * Take a TADC submission response edition block and applies the returned metadata to the digitisation request resource
 *
 * @param stdClass $tadc
 * @param array $md
 */
function tadc_set_resource_values_from_tadc_edition(stdClass &$tadc, array $md)
{
    // If we're working with an 'edition', it has to be a book it's referring to
    if(!@$tadc->type) { $tadc->type = 'book'; }
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
    if(@$md['editionStatement'] && !@$tadc->edition)
    {
        $tadc->edition = $md['editionStatement'];
    }
    if(@$md['date'] && !@$tadc->publication_date)
    {
        $tadc->publication_date = $md['date'];
    }
}

/**
 * Generates an empty TADC object
 *
 * @return stdClass
 */
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

/**
 * Converts the identifiers in the form (doi, ISBN, etc.) to how they are stored in the database
 *
 * @param stdClass $tadc
 */
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

/**
 * Updates a TADC request resource with the values returned from the TADC submission request
 *
 * @param stdClass $tadc
 * @param array $response
 */
function tadc_update_resource_with_tadc_response(stdClass &$tadc, array $response)
{

    $other_response_data = array();
    $id = explode("/", $response['id']);
    $tadc->tadc_id = $id[count($id) - 1];
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