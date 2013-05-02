<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_tadc_mod_form extends moodleform_mod {

    function definition() {
//        $mform = $this->_form;
//
//        $this->standard_coursemodule_elements();
//
////-------------------------------------------------------------------------------
//        // buttons
////        $this->standard_hidden_coursemodule_elements();
//        $this->add_action_buttons(true, 'Proceed to digitisation request form', false);

        global $CFG, $OUTPUT;

        // What is the user requesting? A book or journal
        $types = array('book', 'journal');
        $typename = optional_param('type','unknown', PARAM_ALPHA);

        // Get all existing notes if this already exists
        $update_id = optional_param('update', NAN, PARAM_INT);

        $notes_html = "";
//        if (is_integer($update_id) && $update_id>0) {
//            try {
//                $instance = cla_get_cla_from_module_id($update_id);
//                $typename = $instance->type;
//
//                $notes = cla_get_request_notes($instance->id);
//
//                if (!empty($notes)) {
//                    $notes_html .= '<div class="fitem">';
//                    $notes_html .= '<div class="fitemtitle">Previous notes</div>';
//                    $notes_html .= '<div class="felement">';
//                    foreach($notes as $note)  {
//                        $notes_html .= cla_view_htmlnote($note);
//                    }
//                    $notes_html .= "</div></div>";
//                }
//
//            } catch (Exception $e) {
//                debugging($e->getMessage(), DEBUG_MINIMAL);
//                $notes_html = "";
//            }
//        }

        // filter typename
        if (!in_array($typename, $types)) {
            $typename = $types[0];
        }

        $mform =& $this->_form;

//        // general mod settings
//        $mform->addElement('header', 'general', get_string('general', 'form'));
//        $mform->addElement('hidden', 'type', null, null, $typename);
//        $mform->addElement('hidden', 'file', null, null, '');
//
//
//        // name
//        $mform->addElement('text', 'name', get_string('claname', 'tadc'), array('size'=>'64'));
//        $mform->setType('name', PARAM_TEXT);
//        $mform->addRule('name', null, 'required', null, 'client');
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

//        // extract region from
//        $mform->addElement('text', 'extractregionfrom', get_string('claextractregionfrom', 'tadc'), array('size'=>'64'));
//        $mform->setType('extractregionfrom', PARAM_TEXT);
//
//        // extract region to
//        $mform->addElement('text', 'extractregionto', get_string('claextractregionto', 'tadc'), array('size'=>'64'));
//        $mform->setType('extractregionto', PARAM_TEXT);

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
        $mform->addElement('text', 'title', get_string('tadc'.$typename.'title', 'tadc'), array('size'=>'64'));
        $mform->setType('title', PARAM_TEXT);

//        if ($typename==='book') {
//            $libLink = get_string("claresourcesearchpath", 'tadc');
//            $libLinkTitle = get_string("claresourcesearchtitle", 'tadc');
//        } else {
//            $libLink = get_string("clajournalsearchpath", 'tadc');
//            $libLinkTitle = get_string("clajournalsearchtitle", 'tadc');
//        }

        //Not ideal, but no jquery support in moodleforms at present
//        $script = "<script type='text/javascript'>
//            function libSearch(){
//                var title = document.getElementById('id_title');
//                var title_search = title.value;
//
//                if(title_search != ''){
//                    window.open('{$libLink}' + title_search);
//                }
//            }
//        </script><div style='clear:both; margin-left: 30%; padding-left: 20px;'><a href='#' onclick='libSearch()' ><img src='{$CFG->wwwroot}/mod/cla/pix/icons/search.gif'> Search {$libLinkTitle}</a></div>";

        //$mform->addElement('html', $script);

        // author
        if ($typename==='book') {
            $mform->addElement('text', 'author', get_string('tadccontainercreator', 'tadc'), array('size'=>'64'));
            $mform->setType('author', PARAM_TEXT);
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


/*
        // notes
        $mform->addElement('header', 'notes', get_string('clanotes', 'tadc'));
        if (strlen($notes_html)>0) {
            $mform->addElement('html', $notes_html);
        }
        $mform->addElement('textarea', 'note', get_string('claaddnewnote', 'tadc'), array('cols'=>'56', 'rows'=>'8'));
        $mform->setType('notes', PARAM_RAW);
*/
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // except visible as we'll always create hidden
        $mform->removeElement('visible');
        //This now has to be set to 0.... as it cannot be null
        $mform->addElement('hidden', 'visible', 1);
        $mform->setType('visible', PARAM_INT);

        // add standard buttons, common to all modules
        $this->add_action_buttons(true, get_string('tadcrequestformsubmittext', 'tadc'), false);

    }


}

