<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(dirname(__FILE__).'/lib.php');

    $settings->add(new admin_setting_heading('tadc/server_details', get_string('server_details_heading', 'mod_tadc'), get_string('server_details_desc', 'mod_tadc')));
    $settings->add(new admin_setting_configtext('tadc/tenant_code',
        get_string('tenantshortcode', 'mod_tadc'), get_string('tenantshortcode_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/tadc_location',
        get_string('base_url', 'mod_tadc'), get_string('base_url_desc', 'mod_tadc'),
        'http://content.talisaspire.com/', PARAM_TEXT));

    $settings->add(new admin_setting_heading('tadc/moodle_course_details', get_string('moodle_course_details_heading', 'mod_tadc'), get_string('moodle_details_desc', 'mod_tadc')));

    $settings->add(new admin_setting_configselect('tadc/course_code_field',
        get_string('course_code_field', 'mod_tadc'), get_string('course_code_field_desc', 'mod_tadc'),
        'idnumber', array('idnumber'=>'idnumber','shortname'=>'shortname','fullname'=>'fullname')));

    $settings->add(new admin_setting_configtext('tadc/course_code_regex',
        get_string('course_code_regex', 'mod_tadc'), get_string('course_code_regex_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_heading('tadc/tadc_integration_details', get_string('tadc_integration_heading', 'mod_tadc'), get_string('tadc_integration_desc', 'mod_tadc')));
    $settings->add(new admin_setting_configtext('tadc/api_key',
        get_string('api_key', 'mod_tadc'), get_string('api_key_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('tadc/tadc_shared_secret',
        get_string('shared_secret', 'mod_tadc'), get_string('shared_secret_desc', 'mod_tadc'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_heading('tadc/tadc_services_details', get_string('tadc_services_heading', 'mod_tadc'), get_string('tadc_services_desc', 'mod_tadc')));

    $settings->add(new admin_setting_configcheckbox('tadc/allow_downloads',
        get_string('config_allow_downloads', 'mod_tadc'), get_string('config_allow_downloads_desc', 'mod_tadc'), 0));

    $settings->add(new admin_setting_configcheckbox('tadc/allow_requests',
        get_string('config_allow_requests', 'mod_tadc'), get_string('config_allow_requests_desc', 'mod_tadc'), 0));

}
