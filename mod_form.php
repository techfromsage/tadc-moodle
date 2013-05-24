<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/tadc/locallib.php');

class mod_tadc_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $OUTPUT, $PAGE;

        // What is the user requesting? A book or journal
        $types = array('book', 'journal');
        $typename = optional_param('type','unknown', PARAM_ALPHA);
        $invalidRequest = false;
        $update_id = optional_param('update', NAN, PARAM_INT);
        $mform =& $this->_form;
        $field_attributes = array();
        if (is_integer($update_id) && $update_id>0) {
            global $data;
            $typename = $data->type;
            if(@$data->container_identifier)
            {
                list($idType, $id) = explode(':', $data->container_identifier);
                switch($idType)
                {
                    case 'isbn':
                        $data->isbn = $id;
                        break;
                    case 'issn':
                        $data->issn = $id;
                        break;
                }
            }
            if(@$data->document_identifier)
            {
                list($idType, $id) = explode(':', $data->document_identifier);
                switch($idType)
                {
                    case 'doi':
                        $data->doi = $id;
                        break;
                    case 'pmid':
                        $data->pmid = $id;
                        break;
                }
            }
            if(@$data->request_status === 'REJECTED' && @$data->reason_code === 'InvalidRequest')
            {
                $mform->addElement('html', '<div class="tadc-invalid-request-message">' . get_string('InvalidRequestMessage', 'tadc') . '</div>');
                $mform->addElement('hidden', 'resubmit', true);
                $buttonText = get_string('requestformresubmittext', 'tadc');
                $errors = json_decode($data->other_response_data, true);
                $fieldMap = array(
                    'rft.atitle'=>'section_title','rft.spage'=>'start_page','rft.epage'=>'end_page',
                    'rft.doi'=>'doi','rft.isbn'=>'isbn','rft.btitle'=>'container_title','rft.issn'=>'issn','rft.au'=>'container_creator',
                    'rft.volume'=>'volume','rft.issue'=>'issue','rft.date'=>'publication_date'
                );
                foreach($errors['errors'] as $error)
                {
                    foreach($error['fields'] as $field)
                    {
                        if(!is_array($field))
                        {
                            $field = array($field);
                        }
                        foreach($field as $f)
                        {
                            if(@$fieldMap[$f])
                            {
                                $field_attributes[$fieldMap[$f]] = array('class'=>'tadc-warn');
                            }
                        }
                    }
                }
            }
            $mform->setShowAdvanced(true);
        }

        // If we're resubmitting the request with new values, populate our $data object with these
        if(optional_param('tadc_resubmit', false, PARAM_BOOL))
        {
            $dummyTadc = tadc_create_new_tadc();
            foreach($dummyTadc as $key=>$val)
            {
                if($form_val = optional_param('tadc_'.$key, null, PARAM_TEXT))
                {
                    $data->$key = $form_val;
                }
            }
            if(@$data->container_identifier)
            {
                list($idType, $id) = explode(':', $data->container_identifier);
                switch($idType)
                {
                    case 'isbn':
                        $data->isbn = $id;
                        break;
                    case 'issn':
                        $data->issn = $id;
                        break;
                }
            }
            if(@$data->document_identifier)
            {
                list($idType, $id) = explode(':', $data->document_identifier);
                switch($idType)
                {
                    case 'doi':
                        $data->doi = $id;
                        break;
                    case 'pmid':
                        $data->pmid = $id;
                        break;
                }
            }
            $mform->addElement('hidden', 'resubmit', true);
            $buttonText = get_string('requestformresubmittext', 'tadc');
            $mform->setShowAdvanced(true);
        }
        // filter typename
        if (!in_array($typename, $types)) {
            $typename = $types[0];
        }

        $mform->addElement('hidden', 'type', $typename);

        if(!isset($buttonText))
        {
            $opts = array('add'=>'tadc');
            if($course = optional_param('course', NULL, PARAM_INT))
            {
                $opts['course'] = $course;
            }
            if($section = optional_param('section', NULL, PARAM_INT))
            {
                $opts['section'] = $section;
            }
            if($return = optional_param('return', NULL, PARAM_INT))
            {
                $opts['return'] = $return;
            }
            if($sr = optional_param('sr', NULL, PARAM_INT))
            {
                $opts['sr'] = $sr;
            }
            $opts['type'] = ($typename === 'book') ? 'journal' : 'book';
            $PAGE->requires->js_init_call('M.mod_tadc.form_init');
            $mform->addElement('html', '<div class="yui3-g tadc-format-link"><div class="yui3-u-1"><a class="yui3-button" id="tadc-format-link" href="' . new moodle_url('/course/modedit', $opts) . '">' . get_string($opts['type'] . 'requestlink', 'tadc') . '</a></div></div>');

        }

/// EXTRACT INFORMATION


        $mform->addElement('header', 'extract', get_string($typename.'sectionheader', 'tadc'));
        // extract title
        $mform->addElement('text', 'section_title', get_string($typename.'sectiontitle', 'tadc'), $this->generate_attributes(@$field_attributes['section_title'], array('size'=>'64')));
        $mform->setType('section_title', PARAM_TEXT);

        // extract author
        $mform->addElement('text', 'section_creator', get_string('sectioncreator', 'tadc'), array('size'=>'64'));
        $mform->setType('section_creator', PARAM_TEXT);

        $mform->addElement('text', 'start_page', get_string('sectionstartpage', 'tadc'), $this->generate_attributes(@$field_attributes['section_title'], array('size'=>'4')));
        $mform->setType('start_page', PARAM_TEXT);

        $mform->addElement('text', 'end_page', get_string('sectionendpage', 'tadc'), $this->generate_attributes(@$field_attributes['section_title'], array('size'=>'4')));
        $mform->setType('end_page', PARAM_TEXT);

        if ($typename==='journal') {
            $mform->setAdvanced('section_title');
            $mform->setAdvanced('section_creator');
            $mform->setAdvanced('start_page');
            $mform->setAdvanced('end_page');
            $mform->addHelpButton('section_title', 'journalsectiontitle', 'tadc');
            $mform->addHelpButton('start_page', 'journalstartpage', 'tadc');
            $mform->addElement('text', 'doi', get_string('doi', 'tadc'), array('size'=>64));
            $mform->setType('doi', PARAM_TEXT);
            $mform->addElement('text', 'pmid', get_string('pmid', 'tadc'), array('size'=>12));
            $mform->setType('pmid', PARAM_TEXT);
        } else {
            $mform->addHelpButton('section_title', 'booksectiontitle', 'tadc');
            $mform->addHelpButton('start_page', 'bookstartpage', 'tadc');
            $mform->addHelpButton('end_page', 'bookendpage', 'tadc');
        }

/// BOOK/JOURNAL DETAILS

        $mform->addElement('header', 'containerheader', get_string("{$typename}header", 'tadc'));

        // title
        $mform->addElement('text', 'container_title', get_string($typename.'title', 'tadc'), array('size'=>'64'));
        $mform->setType('container_title', PARAM_TEXT);
        $mform->setAdvanced('container_title');

        // author
        if ($typename==='book') {
            $mform->addElement('text', 'edition', get_string('edition', 'tadc'), array('size'=>'64'));
            $mform->setAdvanced('edition');
            $mform->addElement('text', 'container_creator', get_string('containercreator', 'tadc'), array('size'=>'64'));
            $mform->setType('container_creator', PARAM_TEXT);
            $mform->setAdvanced('container_creator');
            //$mform->addRule('author', null, 'required', null, 'client');
            $mform->addElement('text', 'isbn', get_string('isbn', 'tadc'), array('size'=>'13'));
            $mform->addHelpButton('container_title', 'bookcontainertitle', 'tadc');
            $mform->addHelpButton('container_creator', 'containercreator', 'tadc');
            $mform->addHelpButton('isbn', 'isbn', 'tadc');
        } else {
            $mform->addElement('text', 'issn', 'ISSN', array('size'=>'9'));
            $mform->setAdvanced('issn');
            // Volume or issue
            $mform->addElement('text', 'volume', get_string('volume', 'tadc'), array('size'=>'64'));
            $mform->setType('volume', PARAM_TEXT);
            $mform->setAdvanced('volume');
            $mform->addElement('text', 'issue', get_string('issue', 'tadc'), array('size'=>'64'));
            $mform->setType('issue', PARAM_TEXT);
            $mform->setAdvanced('issue');
            $mform->addHelpButton('container_title', 'journalcontainertitle', 'tadc');
            $mform->addHelpButton('issn', 'issn', 'tadc');
            $mform->addHelpButton('volume', 'volume', 'tadc');
            $mform->addHelpButton('issue', 'issue', 'tadc');
        }

        // publish date
        $mform->addElement('text', 'publication_date', get_string('datepublished', 'tadc'), array('size'=>'64'));
        $mform->setType('publication_date', PARAM_TEXT);
        $mform->addHelpButton('publication_date', $typename.'datepublished', 'tadc');
        $mform->setAdvanced('publication_date');

        // publisher
        $mform->addElement('text', 'publisher', get_string('publisher', 'tadc'), array('size'=>'64'));
        $mform->setType('publisher', PARAM_TEXT);
        $mform->setAdvanced('publisher');


        //$mform->addRule('publishdate', null, 'required', null, 'client');


        $mform->addElement('header', 'request_info', get_string("other_request_info_header", 'tadc'));

        $mform->addElement('date_selector', 'needed_by', get_string('daterequired', 'tadc'));
        $mform->setType('needed_by', PARAM_TEXT);

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        $mform->setDefault('visible', '0');

        if(!isset($buttonText))
        {
            $buttonText = get_string('requestformsubmittext', 'tadc');
        }

        // add standard buttons, common to all modules
        $this->add_action_buttons(true, $buttonText, false);

    }

    function generate_attributes($args1, $args2)
    {
        if(!$args1)
        {
            $args1 = array();
        }
        return array_merge($args1, $args2);
    }


}

