<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_tadc_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $OUTPUT;

        // What is the user requesting? A book or journal
        $types = array('book', 'journal');
        $typename = optional_param('type','unknown', PARAM_ALPHA);

        $update_id = optional_param('update', NAN, PARAM_INT);
        $mform =& $this->_form;

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
        }

        // filter typename
        if (!in_array($typename, $types)) {
            $typename = $types[0];
        }

        $mform->addElement('hidden', 'type', $typename);
        if($typename==='book')
        {
            $mform->addElement('html', '<a href="' . $_SERVER['REQUEST_URI'] . '&type=journal">' . get_string('tadcjournalrequestlink', 'tadc') . '</a>');
        } else {
            $mform->addElement('html', '<a href="' . $_SERVER['REQUEST_URI'] . '&type=book">' . get_string('tadcbookrequestlink', 'tadc') . '</a>');
        }


        $mform->addElement('date_selector', 'needed_by', get_string('tadcdaterequired', 'tadc'));
        $mform->setType('needed_by', PARAM_TEXT);

/// EXTRACT INFORMATION


        $mform->addElement('header', 'extract', get_string('tadc'.$typename.'sectionheader', 'tadc'));

        // extract title
        $mform->addElement('text', 'section_title', get_string('tadc'.$typename.'sectiontitle', 'tadc'), array('size'=>'64'));
        $mform->setType('section_title', PARAM_TEXT);

        // extract author
        $mform->addElement('text', 'section_creator', get_string('tadcsectioncreator', 'tadc'), array('size'=>'64'));
        $mform->setType('section_creator', PARAM_TEXT);

        $mform->addElement('text', 'start_page', get_string('tadcsectionstartpage', 'tadc'), array('size'=>4));
        $mform->setType('start_page', PARAM_TEXT);

        $mform->addElement('text', 'end_page', get_string('tadcsectionendpage', 'tadc'), array('size'=>4));
        $mform->setType('end_page', PARAM_TEXT);

        if ($typename==='journal') {

            $mform->addElement('text', 'doi', get_string('tadcdoi', 'tadc'), array('size'=>64));
            $mform->setType('doi', PARAM_TEXT);
            $mform->addElement('text', 'pmid', get_string('tadcpmid', 'tadc'), array('size'=>12));
            $mform->setType('pmid', PARAM_TEXT);
        }

/// BOOK/JOURNAL DETAILS

        $mform->addElement('header', 'book', get_string("tadc{$typename}header", 'tadc'));


        // title
        $mform->addElement('text', 'container_title', get_string('tadc'.$typename.'title', 'tadc'), array('size'=>'64'));
        $mform->setType('container_title', PARAM_TEXT);


        // author
        if ($typename==='book') {
            $mform->addElement('text', 'container_creator', get_string('tadccontainercreator', 'tadc'), array('size'=>'64'));
            $mform->setType('container_creator', PARAM_TEXT);
            //$mform->addRule('author', null, 'required', null, 'client');
            $mform->addElement('text', 'isbn', 'ISBN', array('size'=>'13'));
        } else {
            $mform->addElement('text', 'issn', 'ISSN', array('size'=>'9'));
        }

        // publisher
        $mform->addElement('text', 'publisher', get_string('tadcpublisher', 'tadc'), array('size'=>'64'));
        $mform->setType('publisher', PARAM_TEXT);

        // publish date
        $mform->addElement('text', 'publication_date', get_string('tadcdate', 'tadc'), array('size'=>'64'));
        $mform->setType('publication_date', PARAM_TEXT);
        //$mform->addRule('publishdate', null, 'required', null, 'client');


        // Volume or issue
        $mform->addElement('text', 'volume', get_string('tadcvolume', 'tadc'), array('size'=>'64'));
        $mform->setType('volume', PARAM_TEXT);
        $mform->addElement('text', 'issue', get_string('tadcissue', 'tadc'), array('size'=>'64'));


        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // add standard buttons, common to all modules
        $this->add_action_buttons(true, get_string('tadcrequestformsubmittext', 'tadc'), false);

    }


}

