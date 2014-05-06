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
require_once(dirname(__FILE__).'/locallib.php');

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
$context = context_module::instance($cm->id);

if($tadc->request_status === 'REJECTED' && $tadc->reason_code === 'InvalidRequest')
{
    redirect(new moodle_url('/course/modedit.php', array('update'=>$cm->id)));
}

add_to_log($course->id, 'tadc', 'view', "view.php?id={$cm->id}", $tadc->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/tadc/view.php', array('id' => $cm->id));

$PAGE->set_title(format_string($tadc->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

// Output the citation as HTML
$requestMarkup = '<div class="tadc-request-metadata">';

$requestMarkup .= tadc_generate_html_citation($tadc);

$requestMarkup .= '</div>';

// If the request is LIVE, show the player, if the user has permissions
if($tadc->request_status === 'LIVE')
{
    $tadc_cfg = get_config('tadc');

    if(has_capability('mod/tadc:view', $context))
    {
        $requestMarkup .= '<div class="tadc-bundle-viewer-container yui3-g">';
        $key = hash_hmac('sha256', '/' . $tadc_cfg->tenant_code . '/bundles/' . $tadc->bundle_url.http_build_query(array('userId'=>$USER->username)).date('Ymd'), $tadc_cfg->tadc_shared_secret);
        $requestMarkup .= '<div class="yui3-u-1-2"><a href="' . $tadc_cfg->tadc_location . $tadc_cfg->tenant_code . '/bundles/' . $tadc->bundle_url . '">Click here if content does not load below.</a></div>';
        if(has_capability('mod/tadc:download', $context) && $tadc_cfg->allow_downloads)
        {
            $requestMarkup .= '<div class="yui3-u-1-2 tadc-download-link"><a class="button" href="' . new moodle_url('/mod/tadc/download.php', array('id'=>$cm->id)) . '">Print/Download</a></div>';
        }
        $requestMarkup .= '</div><div class="yui3-g">';
        $requestMarkup .= '<div class="yui3-u-1"><iframe class="tadc-bundle-viewer" id="tadc-bundle-viewer" width="100%" height="500" frameborder="0" src="' . $tadc_cfg->tadc_location . $tadc_cfg->tenant_code . '/bundles/' . $tadc->bundle_url . '?api_key='. $tadc_cfg->api_key .'&signature=' . $key . '&userId=' . $USER->username . '"></iframe></div>';
        $requestMarkup .= '</div>';
        $PAGE->requires->js_init_call('M.mod_tadc.resize_iframe');

    }

} elseif($tadc->request_status) {
    // Otherwise show the current state of the request

    $requestMarkup .= '<div class="tadc-request-status yui3-g"><div class="yui3-u-1"><dl><dt>Status</dt><dd>' . $tadc->request_status . '</dd>';
    if($tadc->status_message)
    {
        $requestMarkup .= '<dt>Reason</dt><dd>' . $tadc->status_message . '</dd>';
    }
    $requestMarkup .= '</dl></div></div>';
    $requestMarkup .= '<div class="tadc-reason-code-message"><p>' . get_string($tadc->reason_code . 'Message', 'tadc'). '</p></div>';

    // If it's rejected, give the manager options to either submit alternatives or a referral request
    if($tadc->request_status === 'REJECTED' && has_capability('mod/tadc:updateinstance', $context))
    {
        $requestMarkup .= '<div class="tadc-rejection-options">';
        switch($tadc->reason_code)
        {
            // For requests with existing electronic copies, give the option to turn it into a URL
            case 'ElectronicCopyAvailable':
                $tadc_data = json_decode($tadc->other_response_data, true);

                foreach($tadc_data['url'] as $url)
                {
                    $requestMarkup .= '<div class="yui3-g tadc-alt-url-option">';
                    $requestMarkup .= '<div class="yui3-u-1-2"><a href="' . $url . '" target="_blank">Link to resource</a></div>';
                    $requestMarkup .= '<div class="yui3-u-1-2"><form method="POST" action="/course/modedit.php">';
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
                    $requestMarkup .= '</div></div>';
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
                    $requestMarkup .= '<div class="yui3-g"><div class="yui3-u-1">' . get_string('alternate_editions_mesg', 'tadc') . '</div></div>';
                    foreach(array_keys($tadc_data['alternate_editions']) as $format)
                    {
                        $requestMarkup .= '<div class="yui3-g"><div class="yui3-u-1 tadc-alt-edition-format-header">' . ucfirst($format) . '</div></div>';
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
                    $requestMarkup .= '<div class="yui3-g"><div class="yui3-u-1">' . get_string('alternate_editions_mesg', 'tadc') . '</div></div>';
                    foreach(array_keys($tadc_data['alternate_editions']) as $format)
                    {
                        $requestMarkup .= '<div class="yui3-g"><div class="yui3-u-1 tadc-alt-edition-format-header">' . ucfirst($format) . '</div></div>';
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
} else {
    // If there is no request_status, the user hasn't successfully submitted a request, yet
    // Request the launch content with an iframe tag instead of the standard moodle LTI object tag
    echo '<iframe id="contentframe" height="600px" width="100%" type="text/html" src="launch.php?id='.$cm->id.'" frameborder="0"></iframe>';

    //Output script to make the object tag be as large as possible
    $resize = '
        <script type="text/javascript">
        //<![CDATA[
            YUI().use("yui2-dom", function(Y) {
                //Take scrollbars off the outer document to prevent double scroll bar effect
                document.body.style.overflow = "hidden";

                var dom = Y.YUI2.util.Dom;
                var frame = document.getElementById("contentframe");

                var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.

                var lastHeight;

                var resize = function(){
                    var viewportHeight = dom.getViewportHeight();

                    if(lastHeight !== Math.min(dom.getDocumentHeight(), viewportHeight)){

                        frame.style.height = viewportHeight - dom.getY(frame) - padding + "px";

                        lastHeight = Math.min(dom.getDocumentHeight(), dom.getViewportHeight());
                    }
                };

                resize();

                setInterval(resize, 250);
            });
        //]]
        </script>
';

    echo $resize;
}
echo $OUTPUT->box($requestMarkup);
// Finish the page
echo $OUTPUT->footer();

/**
 * Generate the hidden form vars to resubmit a request with different values
 *
 * @param stdClass $cm
 * @param array $edition
 * @return string
 */
function tadc_generate_resubmit_form_from_tadc_edition(stdClass $cm, array $edition)
{
    $altEditionTadc = tadc_create_new_tadc();
    tadc_set_resource_values_from_tadc_edition($altEditionTadc, $edition);
    $form = '<div class="yui3-g tadc-alt-edition-option">';
    $form .= '<div class="yui3-u-1-2">' . tadc_generate_html_citation($altEditionTadc) . '</div>';
    $form .= '<div class="yui3-u-1-2"><form method="POST" action="/course/modedit.php">';
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
    $form .= '</form></div></div>';
    return $form;
}

