<?php
defined('MOODLE_INTERNAL') || die();
global $CFG;

if (!isset($plugin)) {
    // Avoid warning message in M2.5 and below.
    $plugin = new stdClass();
}

$plugin->version   = 2016061400;
$plugin->requires  = 2012120311; // See http://docs.moodle.org/dev/Moodle_Versions
$plugin->cron      = 0;
$plugin->component = 'mod_tadc';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '.0001';

if (isset($CFG->version))
{
    if($CFG->version < 2013111800) {
        // Used by Moodle 2.5 and below.
        $module->version = $plugin->version;
        $module->requires = $plugin->requires;
        $module->cron = $plugin->cron;
        $module->component = $plugin->component;
        $module->maturity = $plugin->maturity;
        $module->release = $plugin->release;
    }
}