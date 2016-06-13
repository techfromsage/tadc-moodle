<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/tadc/locallib.php');

class mod_tadc_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $tadc = $this->current;
        if(isset($tadc->citation))
        {
            $mform->addElement('header', 'requestdetails', get_string('request_details', 'tadc'));
            $mform->addElement('html', '<div class="tadc_citation">'.format_text($tadc->citation, $tadc->citationformat) . '</div>');
        }
        $mform->addElement('header', 'general', get_string('generalheader', 'tadc'));
        $mform->addElement('text', 'name', get_string('activity_name', 'tadc'));
        $mform->setDefault('name', get_string('default_activity_name', 'tadc'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        if($CFG->version >= 2015111600 ) { // greater or in Moodle 3.0.
            $this->standard_intro_elements(false);
        } else {
            $this->add_intro_editor(false);
        }

        $mform->setAdvanced('introeditor');

        $mform->setAdvanced('showdescription');

        $this->standard_coursemodule_elements();

        if(isset($tadc->id) && !empty($tadc->id))
        {
            $this->add_action_buttons();
        } else {
            $this->add_action_buttons(true, get_string('save_and_continue', 'tadc'), false);
        }

    }
}

