<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(dirname(__FILE__).'/lib.php');
//
//    // General settings
//
//    $settings->add(new admin_setting_configcheckbox('tadc/requiremodintro',
//        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
//
//
    $settings->add(new admin_setting_configtext('tadc/tenant_code',
        get_string('tenantshortcode', 'mod_tadc'), get_string('tenantshortcode_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/tadc_location',
        get_string('base_url', 'mod_tadc'), get_string('base_url_desc', 'mod_tadc'),
        'http://content.talisaspire.com/', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/api_key',
        get_string('api_key', 'mod_tadc'), get_string('api_key_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/tadc_shared_secret',
        get_string('shared_secret', 'mod_tadc'), get_string('shared_secret_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/trackback_endpoint',
        get_string('trackback_endpoint', 'mod_tadc'), get_string('trackback_endpoint_desc', 'mod_tadc'),
        '', PARAM_TEXT));
//
//    // Modedit defaults.
//
//    $settings->add(new admin_setting_heading('tadcmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

//    $settings->add(new admin_setting_configselect('book/numbering',
//        get_string('numbering', 'mod_book'), '', BOOK_NUM_NUMBERS, $options));

}
