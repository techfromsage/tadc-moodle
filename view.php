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
 * Prints a particular instance of tadc
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage tadc
 * @copyright  2013 Talis Education Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace tadc with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$t  = optional_param('t', 0, PARAM_INT);  // tadc instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('tadc', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $tadc  = $DB->get_record('tadc', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($t) {
    $tadc  = $DB->get_record('tadc', array('id' => $t), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $tadc->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('tadc', $tadc->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
//$context = context_course::instance($cm->id);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if($tadc->request_status === 'REJECTED' && $tadc->reason_code === 'InvalidRequest')
{
    redirect(new moodle_url('/course/modedit.php', array('update'=>$cm->id)));
}
$title = tadc_build_title_string($tadc);
add_to_log($course->id, 'tadc', 'view', "view.php?id={$cm->id}", $title, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/tadc/view.php', array('id' => $cm->id));

$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);


// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('tadc-'.$somevar);

// Output starts here
echo $OUTPUT->header();

//if ($tadc->section_title) { // Conditions to show the intro can change to look for own settings or whatever
//    echo $OUTPUT->box(format_module_intro('tadc', $tadc, $cm->id), 'generalbox mod_introbox', 'tadcintro');
//}

// Replace the following lines with you own code
$requestMarkup = '<div class="tadc-request-metadata">';

$requestMarkup .= tadc_generate_html_citation($tadc);

$requestMarkup .= '</div>';

if($tadc->request_status === 'LIVE')
{
    $tadc_cfg = get_config('tadc');
    $requestMarkup .= '<div class="tadc-bundle-viewer-container">';
    $requestMarkup .= '<iframe class="tadc-bundle-viewer" id="tadc-bundle-viewer" width="100%" height="500" frameborder="0" src="' . $tadc_cfg->tadc_location . $tadc_cfg->tenant_code . '/bundles/' . $tadc->bundle_url .'"></iframe>';
    $requestMarkup .= '</div>';
} elseif($tadc->request_status) {
    $requestMarkup .= '<div class="tadc-request-status"><dl><dt>Status</dt><dd>' . $tadc->request_status . '</dd>';
    if($tadc->status_message)
    {
        $requestMarkup .= '<dt>Reason</dt><dd>' . $tadc->status_message . '</dd>';
    }
    $requestMarkup .= '</dl></div>';
    $requestMarkup .= '<div class="tadc-reason-code-message"><p>' . get_string($tadc->reason_code . 'Message', 'tadc'). '</p></div>';

    if($tadc->request_status === 'REJECTED')
    {
        $requestMarkup .= '<div class="tadc-rejection-options">';
        switch($tadc->reason_code)
        {
            case 'ElectronicCopyAvailable':
                $tadc_data = json_decode($tadc->other_response_data, true);

                foreach($tadc_data['url'] as $url)
                {
                    $requestMarkup .= '<p><a href="' . $url . '" target="_blank">Link to resource</a></p>';
                    $requestMarkup .= '<p><form method="POST" action="/course/modedit.php">';
                    $requestMarkup .= '<input type="hidden" name="add" value="url" />';
                    $requestMarkup .= '<input type="hidden" name="update" value="0" />';
                    $requestMarkup .= '<input type="hidden" name="modulename" value="url" />';
                    $requestMarkup .= '<input type="hidden" name="instance" value="" />';
                    $requestMarkup .= '<input type="hidden" name="course" value="' . $course->id . '" />';
                    $requestMarkup .= '<input type="hidden" name="name" value="' . tadc_build_title_string($tadc) . '" />';
                    $requestMarkup .= '<input type="hidden" name="introeditor[text]" value="' . tadc_generate_html_citation($tadc) . '" />';
                    $requestMarkup .= '<input type="hidden" name="introeditor[format]" value="1" />';
                    $requestMarkup .= '<input type="hidden" name="introeditor[itemid]" value="' . file_get_unused_draft_itemid() .'" />';
                    $requestMarkup .= '<input type="hidden" name="section" value="' . $DB->get_field('course_sections', 'section', array('id'=>$cm->section)). '" />';
                    $requestMarkup .= '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
                    $requestMarkup .= '<input type="hidden" name="externalurl" value="' . $url . '" />';
                    $requestMarkup .= '<input type="hidden" name="_qf__mod_url_mod_form" value="1" />';
                    $requestMarkup .= '<input type="hidden" name="cmidnumber" value="" />';
                    $requestMarkup .= '<input type="submit" value="Create URL resource and add to course" />';
                    $requestMarkup .= '</form>';
                }
                break;
            case 'NewerEditionAvailable':
                $tadc_data = json_decode($tadc->other_response_data, true);
                if(@$tadc_data['locallyHeldEditionIds'])
                {
                    foreach($tadc_data['locallyHeldEditionIds'] as $localId)
                    {
                        $requestMarkup .= tadc_generate_resubmit_form_from_tadc_edition($cm, $tadc_data['editions'][$localId]);
                    }
                }
                break;
            case 'NotHeldByLibrary':
                $tadc_data = json_decode($tadc->other_response_data, true);
                if(@$tadc_data['alternate_editions'])
                {
                    $requestMarkup .= '<p>' . get_string('alternate_editions_mesg', 'tadc') . '</p>';
                    foreach(array_keys($tadc_data['alternate_editions']) as $format)
                    {
                        $requestMarkup .= '<p><strong>' . ucfirst($format) . '</strong></p>';
                        foreach($tadc_data['alternate_editions'][$format] as $edition)
                        {
                            $requestMarkup .= tadc_generate_resubmit_form_from_tadc_edition($cm, $edition);
                        }
                    }
                }
                break;

            case 'RequestNotPermissibleUnderRROLicence':
                $tadc_data = json_decode($tadc->other_response_data, true);
                if(@$tadc_data['alternate_editions'])
                {
                    $requestMarkup .= '<p>' . get_string('alternate_editions_mesg', 'tadc') . '</p>';
                    foreach(array_keys($tadc_data['alternate_editions']) as $format)
                    {
                        $requestMarkup .= '<p><strong>' . ucfirst($format) . '</strong></p>';
                        foreach($tadc_data['alternate_editions'][$format] as $edition)
                        {
                            $requestMarkup .= tadc_generate_resubmit_form_from_tadc_edition($cm, $edition);
                        }
                    }
                }
                break;
        }
        $requestMarkup .= '</div>';
        $requestMarkup .= '<div class="tadc-referral-form"><form method="post" action="refer.php">';
        $requestMarkup .= '<h3>Proceed with request anyway</h3>';
        $requestMarkup .= '<input type="hidden" name="id" value="'. $cm->id . '" />';
        $requestMarkup .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $requestMarkup .= '<label for="referralMessage">Reason</label><br />';

        $requestMarkup .= '<textarea id="referralMessage" name="referralMessage" rows="2" cols="50"></textarea>';
        $requestMarkup .= '<p><input type="submit" value="Submit referral request" /></p>';
        $requestMarkup .= '</form></div>';
    }
}
echo $OUTPUT->box($requestMarkup);
// Finish the page
echo $OUTPUT->footer();

function tadc_generate_resubmit_form_from_tadc_edition(stdClass $cm, array $edition)
{
    $altEditionTadc = tadc_create_new_tadc();
    tadc_set_resource_values_from_tadc_edition($altEditionTadc, $edition);
    $form = '<p>' . tadc_generate_html_citation($altEditionTadc) . '</p>';
    $form .= '<p><form method="POST" action="/course/modedit.php">';
    $form .= '<input type="hidden" name="update" value="'. $cm->id . '" />';
    $form .= '<input type="hidden" name="tadc_resubmit" value="true" />';
    foreach($altEditionTadc as $key=>$value)
    {
        if(!$value)
        {
            continue;
        }
        $form .= '<input type="hidden" name="tadc_'. $key . '" value="' . $value . '" />';
    }
    $form .= '<input type="submit" value="Resubmit with this edition" />';
    $form .= '</form>';
    return $form;
}

