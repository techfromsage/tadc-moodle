<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/tadc/locallib.php');

class mod_tadc_mod_form extends moodleform_mod {

    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header', 'general', get_string('generalheader', 'tadc'));
        $mform->addElement('text', 'name', get_string('section_title', 'tadc'));
        $mform->setDefault('name', get_string('default_section_title', 'tadc'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        $this->add_intro_editor(false);
        $mform->setAdvanced('introeditor');

        // Display the label to the right of the checkbox so it looks better & matches rest of the form
        $coursedesc = $mform->getElement('showdescription');
        if(!empty($coursedesc)){
            $coursedesc->setText(' ' . $coursedesc->getLabel());
            $coursedesc->setLabel('&nbsp');
        }
        $mform->setAdvanced('showdescription');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons(true, get_string('save_and_continue', 'tadc'), false);

    }
}

