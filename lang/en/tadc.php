<?php
$string['modulename'] = 'Digitisation Request';
$string['modulename_help'] = 'Request digisations from books or journals and display them in your course.';
$string['modulenameplural'] = 'Digitization requests';
$string['userpreferences'] = 'User preferences';
$string['tenantshortcode'] = 'Tenant code';
$string['tenantshortcode_desc'] = 'Tenancy short code used in Talis Aspire (e.g. "broadminster")';

$string['pluginadministration'] = '';
$string['pluginname'] = 'Course reading';
$string['tadc:addinstance'] = 'Add course reading';
$string['tadc:updateinstance'] = 'Update course reading';
$string['tadc:view'] = 'View course reading';
$string['tadc:download'] = 'Download course reading';


$string['generalheader'] = 'General';
$string['activity_name'] = 'Title';
$string['default_activity_name'] = 'Course reading';
$string['save_and_continue'] = "Request digitisation";
$string['request_details'] = "Request details";

/**
 * Admin settings
 */
$string['base_url'] = 'TADC location';
$string['base_url_desc'] = 'URL of the Talis Aspire Digitised Content service (e.g. http://content.talisaspire.com/)';
$string['shared_secret'] = 'Secret';
$string['shared_secret_desc'] = 'Shared secret salt for verifying requests.  This also needs to be set in TADC';
$string['trackback_endpoint'] = 'Trackback location';
$string['trackback_endpoint_desc'] = 'URL of trackback endpoint so TADC can send status updates.';
$string['api_key'] = 'A valid TADC API key';
$string['api_key_desc'] = 'Generate this key in the TADC Settings/Integrations menu';
$string['course_code_field'] = 'Course code field';
$string['course_code_field_desc'] = 'The Moodle Course Field that corresponds to the TADC course code';
$string['config_allow_requests'] = 'Allow TADC requests from Moodle';
$string['config_allow_requests_desc'] = 'Requests can be sent to TADC from Moodle and a digitisation resource will be created within the course';
$string['config_allow_downloads'] = 'Allow TADC downloads from Moodle';
$string['config_allow_downloads_desc'] = 'Moodle can be used to specify enrollment in a course, and the bundle player will point the user to Moodle to download the document';
$string['course_code_regex'] = 'Course ID regex';
$string['course_code_regex_desc'] = 'Since course IDs may be stored differently in Moodle than TADC, this setting lets you configure how to translate between the two: it accepts regexes, but must contain "%COURSE_CODE%" as the part of the string that matches with how the course code appears in TADC.  Example: `^20\\[0-9\]{2}-\\[0-9\]{2}_%COURSE_CODE%\\-(FOO|BAR|BAZ)$`';

$string['server_details_heading'] = 'Server details';
$string['server_details_desc'] = 'Host name and tenant code used for Talis Aspire Digitised Content';

$string['moodle_course_details_heading'] = 'Moodle course details';
$string['moodle_details_desc'] = 'Where courses are defined in Moodle and how to map them Talis Aspire Digitised Content';

$string['tadc_integration_heading'] = 'Access key';
$string['tadc_integration_desc'] = 'To use Digitised Content from within Moodle, an access key/shared secret must be generated from with TADC.  To do this, log into Talis Aspire Digitised Content, and from the "Admin" menu, select "Integrations" and create a new access key';

$string['tadc_services_heading'] = 'Talis Aspire Digitised Content Services';
$string['tadc_services_desc'] = 'Specify the TADC services you wish to enable from Moodle';

/*
* Messages for API reason codes:
*/

// REJECTED
$string['ElectronicCopyAvailableMessage'] = 'According to the library catalogue, there is an existing electronic copy already available. Use this instead of making an additional digital copy.';
$string['NewerEditionAvailableMessage'] = 'A newer edition is available. Digital copies must be made from the newest edition, unless there is a pedagogical reason to use an earlier edition';
$string['NotHeldByLibraryMessage'] = 'This resource is not held by the library';
$string['RequestNotPermissibleUnderRROLicenceMessage'] = 'This request cannot be serviced under the terms of your RRO license';
$string['InvalidRequestMessage'] = 'The request was either incomplete or had invalid values.';
$string['CopyLimitExceededMessage'] = 'This request exceeded the copy limits for the work/course.';
$string['DuplicateRequestMessage'] = 'This request is a duplicate of an already submitted request (same course, time period and requested resource)';
$string['FeePayClearanceRejectedMessage'] = 'After review, it was determined that library cannot or will not source this request from external sources.';
$string['RejectedByAdminDiscretionMessage'] = 'Upon review, this request was rejected by library staff.';
$string['RightsClearanceRejectedMessage'] = 'Your digitisation request has been rejected because the item was unavailable under the terms of the CLA site licence. The library attempted to acquire copying rights elsewhere, but unfortunately on this occasion was unable to do so.';

// REFERRED
$string['CopyPendingMessage'] = 'This request requires a copy to be uploaded. On uploading the document the system will attach a coversheet and make it available for students.';
$string['UserInitiatedReferralMessage'] = 'Your referral request will be reviewed by the library before being made available to students.';
$string['ConciergeInitiatedReferralMessage'] = 'The system could not automatically determine whether or not the request could be cleared - a librarian will manually process the request.';
$string['UnableToParseLatestEditionMessage'] = 'The system cannot determine whether or not there is a newer edition available - a librarian will manually process the request.';
$string['UnableToIdentifyResourceMessage'] = 'The system cannot identify the requested resource based on the data supplied - a librarian will manually process the request.';
$string['AwaitingFeePayClearanceApprovalMessage'] = 'This resource is not available in the library, however the request will be reviewed to determine if a suitable copy can be obtained elsewhere.';
$string['AwaitingManuallyInitiatedRightsClearanceMessage'] = 'This request cannot be serviced under the terms of your institution\'s CLA site licence, however it will be reviewed to determine if copyright clearance can be acquired elsewhere.';
$string['UnableToDetermineSerialsHoldingsCoverageMessage'] = 'The system cannot determine if the library\'s serials holdings covers this request.';
$string['AwaitingSourceAcquisitionMessage'] = 'This resource is not available in the library, however the request will be reviewed to determine if a suitable copy can be obtained elsewhere.';
// WITHDRAWN
$string['WithdrawnByAdminDiscretionMessage'] = 'The request was withdrawn by library staff.';

// SUCCESS
$string['QueuedForPackingMessage'] = 'The request is successful. The request has been sent to the packer to attach a coversheet to the document.';


/**
 * Errors
 */

$string['notauthorizedfordownload'] = 'Sorry, you must be enrolled in a course associated with this digitisation to download it.';

$string['return_to_course'] = 'Click <a href="{$a->link}" target="_top">here</a> to return to the course.';

$string['notadc'] = "No digitisations requested for this course";