<?php
$services = array(
    'trackback' => array(                                                //the name of the web service
        'functions' => array ('tadc_trackback'), //web service functions of this service
        'requiredcapability' => 'mod/tadc:updateinstance',                //if set, the web service user need this capability to access
        //any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 1,                                             //if enabled, the Moodle administrator must link some user to this service
    //into the administration
    'enabled'=>1,                                                       //if enabled, the service can be reachable on a default installation
)
  );

$functions = array(
    'tadc_trackback' => array(         //web service function name
        'classname'   => 'local_tadc_external',  //class containing the external function
        'methodname'  => 'trackback',          //external function name
        'classpath'   => 'mod/tadc/externallib.php',  //file containing the class/external function
        'description' => 'TADC Trackback Service',    //human readable description of the web service function
        'type'        => 'read, write',                  //database rights of the web service function (read, write)
    ),
);