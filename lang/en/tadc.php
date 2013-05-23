<?php
$string['modulename'] = 'Digitisation Request';
$string['modulename_help'] = 'Request digisations from books or journals and display them in your course.';
$string['modulenameplural'] = 'Digitization requests';
$string['userpreferences'] = 'User preferences';
$string['tenantshortcode'] = 'Tenant code';
$string['tenantshortcode_desc'] = 'Tenancy short code used in Talis Aspire (e.g. "broadminster")';
$string['needed_by'] = 'Needed by';
$string['booksectionheader'] = 'Chapter or section of a book';
$string['chaptertitle'] = 'Chapter title or number';
$string['startpage'] = 'Start page';
$string['endpage'] = 'End page';
$string['isbn'] = 'ISBN (10 or 13)';
$string['pluginadministration'] = '';
$string['pluginname'] = 'Course reading';
$string['tadc:addinstance'] = 'Add course reading';
$string['tadc:updateinstance'] = 'Update course reading';
$string['tadc:view'] = 'View course reading';
$string['daterequired'] = 'Needed by';
$string['bookheader'] = 'Book information';
$string['journalheader'] = 'Journal information';
$string['booksectionheader'] = 'Chapter or page range';
$string['other_request_info_header'] = 'Other request information';
$string['journalsectionheader'] = 'Article';
$string['journalsectiontitle'] = 'Article title';
$string['booksectiontitle'] = 'Chapter or section title';
$string['containercreator'] = 'Author';
$string['isbn'] = 'ISBN';
$string['issn'] = 'ISSN/eISSN';
$string['sectioncreator'] = 'Author(s)';
$string['sectionstartpage'] = 'Start page';
$string['sectionendpage'] = 'End page';
$string['journaltitle'] = 'Journal title';
$string['booktitle'] = 'Book title';
$string['publisher'] = 'Publisher/imprint';
$string['datepublished'] = 'Date published';
$string['volume'] = 'Volume';
$string['issue'] = 'Issue';
$string['doi'] = 'DOI';
$string['pmid'] = 'PubMed ID';
$string['journalrequestlink'] = 'Request a journal article';
$string['bookrequestlink'] = 'Request a chapter or section from a book';
$string['requestformsubmittext'] = 'Submit request';
$string['requestformresubmittext'] = 'Re-submit request with these values';
$string['base_url'] = 'TADC location';
$string['base_url_desc'] = 'URL of the Talis Aspire Digitised Content service (e.g. http://content.talisaspire.com/)';
$string['shared_secret'] = 'Secret';
$string['shared_secret_desc'] = 'Shared secret salt for verifying requests.  This also needs to be set in TADC';
$string['trackback_endpoint'] = 'Trackback location';
$string['trackback_endpoint_desc'] = 'URL of trackback endpoint so TADC can send status updates.';
$string['alternate_editions_mesg'] = 'The library holds the following editions, which may be available for digitisation:';
$string['edition'] = 'Edition';
$string['api_key'] = 'A valid TADC API key';
$string['api_key_desc'] = 'Generate this key in the TADC Settings/Manage access keys menu';
$string['course_code_field'] = 'Course code field';
$string['course_code_field_desc'] = 'The Moodle Course Field that corresponds to the TADC course code';

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
$string['AwaitingFeePayClearanceMessage'] = 'This resource is not available in the library, however the request will be reviewed to determine if a suitable copy can be obtained elsewhere.';
$string['AwaitingManuallyInitiatedRightsClearanceMessage'] = 'This request cannot be serviced under the terms of your institution\'s CLA site licence, however it will be reviewed to determine if copyright clearance can be acquired elsewhere.';
$string['UnableToDetermineSerialsHoldingsCoverageMessage'] = 'The system cannot determine if the library\'s serials holdings covers this request.';

// WITHDRAWN
$string['WithdrawnByAdminDiscretionMessage'] = 'The request was withdrawn by library staff.';

// Help messages:
$string['booksectiontitle_help'] = 'Book extract requests must contain a chapter or section title, a page range, or, preferably, both.';
$string['bookstartpage'] = $string['startpage'];
$string['bookstartpage_help'] = $string['booksectiontitle_help'];
$string['bookendpage'] = $string['endpage'];
$string['bookendpage_help'] = $string['booksectiontitle_help'];
$string['bookcontainertitle'] = $string['booktitle'];
$string['bookcontainertitle_help'] = 'Book extract requests must contain either a book title, author, and date published or an ISBN';
$string['containercreator_help'] = $string['bookcontainertitle_help'];
$string['isbn_help'] = $string['bookcontainertitle_help'];
$string['bookdatepublished'] = $string['datepublished'];
$string['bookdatepublished_help'] = $string['bookcontainertitle_help'];


$string['journalsectiontitle_help'] = 'Article requests must include a DOI/PMID, an article title, or a start page.';
$string['journalstartpage'] = $string['startpage'];
$string['journalstartpage_help'] = $string['journalsectiontitle_help'];
$string['journaldatepublished'] = $string['datepublished'];

$string['volume_help'] = 'Article requests must include either a DOI/PMID or volume, issue, and/or publication date';
$string['issue_help'] = $string['volume_help'];
$string['journaldatepublished_help'] = $string['volume_help'];

$string['journalcontainertitle'] = $string['journaltitle'];
$string['journalcontainertitle_help'] = 'Article requests must include a DOI/PMID, a journal title, or an ISSN.';
$string['issn_help'] = $string['journalcontainertitle_help'];
