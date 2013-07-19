<?php

global $CFG;
require_once($CFG->dirroot . '/mod/tadc/locallib.php');

class mod_tadc_resource_test extends advanced_testcase {
    public function test_create_new_tadc_object()
    {
        $tadc = tadc_create_new_tadc();
        $this->assertObjectHasAttribute('id', $tadc);
        $this->assertObjectHasAttribute('course', $tadc);
        $this->assertObjectHasAttribute('section_title', $tadc);
        $this->assertObjectHasAttribute('section_creator', $tadc);
        $this->assertObjectHasAttribute('start_page', $tadc);
        $this->assertObjectHasAttribute('end_page', $tadc);
        $this->assertObjectHasAttribute('container_title', $tadc);
        $this->assertObjectHasAttribute('document_identifier', $tadc);
        $this->assertObjectHasAttribute('container_identifier', $tadc);
        $this->assertObjectHasAttribute('publication_date', $tadc);
        $this->assertObjectHasAttribute('volume', $tadc);
        $this->assertObjectHasAttribute('issue', $tadc);
        $this->assertObjectHasAttribute('publisher', $tadc);
        $this->assertObjectHasAttribute('needed_by', $tadc);
        $this->assertObjectHasAttribute('tadc_id', $tadc);
        $this->assertObjectHasAttribute('status_message', $tadc);
        $this->assertObjectHasAttribute('request_status', $tadc);
        $this->assertObjectHasAttribute('bundle_url', $tadc);
        $this->assertObjectHasAttribute('name', $tadc);
        $this->assertObjectHasAttribute('container_creator', $tadc);
        $this->assertObjectHasAttribute('reason_code', $tadc);
        $this->assertObjectHasAttribute('other_response_data', $tadc);
        $this->assertObjectHasAttribute('type', $tadc);
    }

    public function test_journal_resource_to_referent()
    {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $tadc = tadc_create_new_tadc();
        $tadc->id = 1234;
        $tadc->course = $course->id;
        $tadc->type = 'journal';
        $tadc->section_title = 'Article title';
        $tadc->section_creator = 'Bar, Foo';
        $tadc->start_page = '12';
        $tadc->end_page = '20';
        $tadc->volume = '23';
        $tadc->issue = '2';
        $tadc->document_identifier = 'doi:10.0.1/12345';
        $tadc->container_title = 'Journal title';
        $tadc->container_identifier = 'issn:1234-5678';
        $tadc->publication_date = '2013';
        $tadc->publisher = 'FooBar Press';
        $now = time();
        $tadc->needed_by = $now;
        $params = tadc_resource_to_referent($tadc);
        $this->assertArrayHasKey('rft.atitle', $params);
        $this->assertEquals('Article title', $params['rft.atitle']);
        $this->assertArrayHasKey('rft_val_fmt', $params);
        $this->assertEquals('info:ofi/fmt:kev:mtx:journal', $params['rft_val_fmt']);
        $this->assertArrayHasKey('rft.au', $params);
        $this->assertEquals('Bar, Foo', $params['rft.au']);
        $this->assertArrayHasKey('rft.spage', $params);
        $this->assertEquals('12', $params['rft.spage']);
        $this->assertArrayHasKey('rft.epage', $params);
        $this->assertEquals('20', $params['rft.epage']);
        $this->assertArrayHasKey('rft.jtitle', $params);
        $this->assertEquals('Journal title', $params['rft.jtitle']);
        $this->assertArrayHasKey('rft.issn', $params);
        $this->assertEquals('1234-5678', $params['rft.issn']);
        $this->assertArrayHasKey('rft.date', $params);
        $this->assertEquals('2013', $params['rft.date']);
        $this->assertArrayHasKey('rft.pub', $params);
        $this->assertEquals('FooBar Press', $params['rft.pub']);
        $this->assertArrayHasKey('svc.neededby', $params);
        $this->assertEquals(strftime('%Y-%m-%d', $now), $params['svc.neededby']);
        $this->assertArrayHasKey('rft.volume', $params);
        $this->assertEquals('23', $params['rft.volume']);
        $this->assertArrayHasKey('rft.issue', $params);
        $this->assertEquals('2', $params['rft.issue']);
        $this->assertArrayHasKey('rft_id', $params);
        $this->assertEquals('info:doi/10.0.1/12345', $params['rft_id']);
        $this->assertArrayHasKey('rft.doi', $params);
        $this->assertEquals('10.0.1/12345', $params['rft.doi']);
    }

    public function test_build_request()
    {
        global $COURSE;
        $this->resetAfterTest(true);
        set_config('course_code_field', 'idnumber', 'tadc');
        $now = time();
        $COURSE->idnumber = 'TEST101';
        $COURSE->startdate = $now;
        $user1 = $this->getDataGenerator()->create_user(array('email'=>'user1@example.com', 'username'=>'user1', 'firstname'=>'User', 'lastname'=>'One'));
        $this->setUser($user1);
        $tadc = tadc_create_new_tadc();
        $tadc->id = 1234;
        $tadc->course = $COURSE->id;
        $tadc->type = 'book';
        $tadc->section_title = 'Section title';
        $tadc->section_creator = 'Bar, Foo';
        $tadc->start_page = '1';
        $tadc->end_page = '12';
        $tadc->container_title = 'Container title';
        $tadc->container_identifier = 'isbn:1234567890';
        $tadc->publication_date = '2013';
        $tadc->publisher = 'FooBar Press';
        $tadc->needed_by = $now;
        $tadc->course_start = $now;
        $tadc->course_end = $now;
        $tadc->container_creator = 'Baz, Bar';
        $params = tadc_build_request($tadc);
        $this->assertArrayHasKey('url_ver', $params);
        $this->assertEquals('Z39.88-2004', $params['url_ver']);
        $this->assertArrayHasKey('url_ctx_fmt', $params);
        $this->assertEquals('info:ofi/fmt:kev:mtx:ctx', $params['url_ctx_fmt']);
        $this->assertArrayHasKey('url_tim', $params);
        $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $params['url_tim']);
        $this->assertArrayHasKey('rft.atitle', $params);
        $this->assertEquals('Section title', $params['rft.atitle']);
        $this->assertArrayHasKey('rft_val_fmt', $params);
        $this->assertEquals('info:ofi/fmt:kev:mtx:book', $params['rft_val_fmt']);
        $this->assertArrayHasKey('rft.au', $params);
        $this->assertEquals('Bar, Foo; Baz, Bar', $params['rft.au']);
        $this->assertArrayHasKey('rft.spage', $params);
        $this->assertEquals('1', $params['rft.spage']);
        $this->assertArrayHasKey('rft.epage', $params);
        $this->assertEquals('12', $params['rft.epage']);
        $this->assertArrayHasKey('rft.btitle', $params);
        $this->assertEquals('Container title', $params['rft.btitle']);
        $this->assertArrayHasKey('rft.isbn', $params);
        $this->assertEquals('1234567890', $params['rft.isbn']);
        $this->assertArrayHasKey('rft.date', $params);
        $this->assertEquals('2013', $params['rft.date']);
        $this->assertArrayHasKey('rft.pub', $params);
        $this->assertEquals('FooBar Press', $params['rft.pub']);
        $this->assertArrayHasKey('svc.neededby', $params);
        $this->assertEquals(strftime('%Y-%m-%d', $now), $params['svc.neededby']);
        $this->assertArrayHasKey('rfr_id', $params);
        $this->assertEquals('http://www.example.com/moodle/mod/tadc/view.php?t=1234', $params['rfr_id']);
        $this->assertArrayHasKey('rfr.type', $params);
        $this->assertEquals('http://schemas.talis.com/tadc/v1/referrers/moodle/1', $params['rfr.type']);
        $this->assertArrayHasKey('rfe.code', $params);
        $this->assertEquals('TEST101', $params['rfe.code']);
        $this->assertArrayHasKey('rfe.name', $params);
        $this->assertEquals('PHPUnit test site', $params['rfe.name']);
        $this->assertArrayHasKey('rfe.size', $params);
        $this->assertEquals(2, $params['rfe.size']);
        $this->assertArrayHasKey('rfe.sdate', $params);
        $this->assertEquals(strftime('%Y-%m-%d', $now), $params['rfe.sdate']);
        $this->assertArrayHasKey('rfe.edate', $params);
        $this->assertEquals(strftime('%Y-%m-%d', $now), $params['rfe.edate']);
        $this->assertArrayHasKey('req.email', $params);
        $this->assertEquals('user1@example.com', $params['req.email']);
        $this->assertArrayHasKey('req.name', $params);
        $this->assertEquals('User One', $params['req.name']);
    }

    public function test_build_title_string()
    {
        $tadc = tadc_create_new_tadc();
        $this->assertEquals('', tadc_build_title_string($tadc));
        $tadc->section_title = 'Section title';
        $this->assertEquals('Section title', tadc_build_title_string($tadc));
        $tadc->container_title = 'Container title';
        $tadc->container_identifier = 'isbn:1234567890';
        $this->assertEquals('Section title from Container title', tadc_build_title_string($tadc));
        $tadc->start_page = '1';
        $this->assertEquals('Section title from Container title, p. 1', tadc_build_title_string($tadc));
        $tadc->end_page = '10';
        $this->assertEquals('Section title from Container title, pp. 1-10', tadc_build_title_string($tadc));
        $tadc->container_title = NULL;
        $this->assertEquals('Section title from ISBN: 1234567890, pp. 1-10', tadc_build_title_string($tadc));
    }

    public function test_generate_html_citation()
    {
        $tadc = tadc_create_new_tadc();
        $tadc->type = 'book';
        $this->assertEquals('.', tadc_generate_html_citation($tadc));
        $tadc->section_title = 'Section title';
        $this->assertEquals("'Section title'.", tadc_generate_html_citation($tadc));
        $tadc->container_creator = 'Bar, Foo';
        $this->assertEquals("Bar, Foo 'Section title'.", tadc_generate_html_citation($tadc));
        $tadc->section_creator = 'Baz, Bar';
        $this->assertEquals("Baz, Bar 'Section title' Bar, Foo.", tadc_generate_html_citation($tadc));
        $tadc->publication_date = '2013';
        $this->assertEquals("Baz, Bar 2013 'Section title' Bar, Foo.", tadc_generate_html_citation($tadc));
        $tadc->container_identifier = 'isbn:1234567890';
        $this->assertEquals("Baz, Bar 2013 'Section title' in <em>ISBN: 1234567890</em>, Bar, Foo.", tadc_generate_html_citation($tadc));
        $tadc->container_title = 'Container title';
        $this->assertEquals("Baz, Bar 2013 'Section title' in <em>Container title</em>, Bar, Foo.", tadc_generate_html_citation($tadc));
        $tadc->publisher = 'Publisher Press';
        $this->assertEquals("Baz, Bar 2013 'Section title' in <em>Container title</em>, Bar, Foo Publisher Press.", tadc_generate_html_citation($tadc));
        $tadc->start_page = '1';
        $this->assertEquals("Baz, Bar 2013 'Section title' in <em>Container title</em>, Bar, Foo Publisher Press p. 1.", tadc_generate_html_citation($tadc));
        $tadc->end_page = '10';
        $this->assertEquals("Baz, Bar 2013 'Section title' in <em>Container title</em>, Bar, Foo Publisher Press pp. 1-10.", tadc_generate_html_citation($tadc));
        $tadc->type = 'journal';
        $this->assertEquals("Baz, Bar 2013 'Section title' <em>Container title</em>, Bar, Foo pp. 1-10.", tadc_generate_html_citation($tadc));
        $tadc->container_creator = NULL;
        $this->assertEquals("Baz, Bar 2013 'Section title' <em>Container title</em>, pp. 1-10.", tadc_generate_html_citation($tadc));
    }

    public function test_set_resource_values_from_request_response()
    {
        $tadc = tadc_create_new_tadc();
        $response = '{"type":"Article","sectionCreators":["K\u00d6HLER, G."],"sectionTitle":"Continuous cultures of fused cells secreting antibody of predefined specificity","startPage":"495","endPage":"515","editionTitle":"Nature","volume":"256","issue":"5517","editionStatement":"v. 256 (5517) ","editionDate":"1975-08","doi":"10.1038\/256495a0","pmid":null,"publisher":"Macmillan Journals.","work":{"id":"51a4d614246bdc4ec9000022","identifiers":{"eissn":["1476-4687"],"issn":["0028-0836"]},"issns":["0028-0836","1476-4687"],"title":"Nature","publisher":"Macmillan Journals."},"language":null,"publicationCountry":"enk","journalShortTitles":[],"issn":"0028-0836","eissn":"1476-4687","edition":{"id":"51a4d616246bdc46c900002d","volume":"256","issue":"5517","date":"1975-08"}}';
        tadc_set_resource_values_from_tadc_metadata($tadc, json_decode($response, true));
        $this->assertEquals('KÖHLER, G.', $tadc->section_creator);
        $this->assertEquals('Continuous cultures of fused cells secreting antibody of predefined specificity', $tadc->section_title);
        $this->assertEquals('Nature', $tadc->container_title);
        $this->assertEquals('issn:0028-0836', $tadc->container_identifier);
        $this->assertEquals('doi:10.1038/256495a0', $tadc->document_identifier);
        $this->assertEquals('495', $tadc->start_page);
        $this->assertEquals('515', $tadc->end_page);
        $this->assertEquals('journal', $tadc->type);
        $this->assertEquals('256', $tadc->volume);
        $this->assertEquals('5517', $tadc->issue);
        $this->assertEquals('1975-08', $tadc->publication_date);
        $this->assertEquals('Macmillan Journals.', $tadc->publisher);
        $tadc = tadc_create_new_tadc();
        $response = '{"type":"BookSection","sectionTitle":null,"sectionCreators":["ed. by J.E. Flower."],"startPage":"17","endPage":"25","editionTitle":"France today","editionCreators":["Flower, J. E."],"editionStatement":"8th ed.","editionDate":"1997","isbn13":["9780340630938"],"isbn10":["0340630930"],"identifiers":{"isbn10":["0340630930"],"isbn13":["9780340630938"]},"publisherStrings":["Hodder & Stoughton"],"publicationCountry":"enk","language":"eng","numberOfPages":248}';
        tadc_set_resource_values_from_tadc_metadata($tadc, json_decode($response, true));
        $this->assertEquals('book', $tadc->type);
        $this->assertEquals('ed. by J.E. Flower.', $tadc->section_creator);
        $this->assertNull($tadc->section_title);
        $this->assertEquals('17', $tadc->start_page);
        $this->assertEquals('25', $tadc->end_page);
        $this->assertEquals('France today', $tadc->container_title);
        $this->assertEquals('Flower, J. E.', $tadc->container_creator);
        $this->assertEquals('8th ed.', $tadc->edition);
        $this->assertEquals('1997', $tadc->publication_date);
        $this->assertEquals('isbn:9780340630938', $tadc->container_identifier);
        $this->assertNull($tadc->document_identifier);
        $this->assertEquals('Hodder & Stoughton', $tadc->publisher);
    }

    public function test_set_resource_values_from_editions_response()
    {
        $tadc = tadc_create_new_tadc();
        $response = '{"type":null,"identifiers":{"isbn13":["9780203340189"],"isbn10":["0203340183"]},"title":"France, 1814-1940","creators":["J.P.T. Bury ; with a new introduction by Robert Tombs."],"editionStatement":"6th ed.","normalizedEditionStatement":6,"language":"eng","date":"2003","country":null,"formats":["BA"],"publisherStrings":["Routledge"],"numberOfPages":null,"dataSource":"oclc","id":"isbn:9780203340189"}';
        tadc_set_resource_values_from_tadc_edition($tadc, json_decode($response, true));
        $this->assertEquals('book', $tadc->type);
        $this->assertEquals('isbn:9780203340189', $tadc->container_identifier);
        $this->assertEquals('France, 1814-1940', $tadc->container_title);
        $this->assertEquals('J.P.T. Bury ; with a new introduction by Robert Tombs.', $tadc->container_creator);
        $this->assertEquals('6th ed.', $tadc->edition);
        $this->assertEquals('2003', $tadc->publication_date);
        $this->assertEquals('Routledge', $tadc->publisher);
    }

    public function test_form_identifiers_to_resource_identifiers()
    {
        $tadc = tadc_create_new_tadc();
        $tadc->isbn = '1234567890';
        tadc_form_identifiers_to_resource_identifiers($tadc);
        $this->assertEquals('isbn:1234567890', $tadc->container_identifier);
        $tadc = tadc_create_new_tadc();
        $tadc->issn = '1234-5678';
        tadc_form_identifiers_to_resource_identifiers($tadc);
        $this->assertEquals('issn:1234-5678', $tadc->container_identifier);
        $tadc = tadc_create_new_tadc();
        $tadc->pmid = '1234567890';
        tadc_form_identifiers_to_resource_identifiers($tadc);
        $this->assertEquals('pmid:1234567890', $tadc->document_identifier);
        $tadc->doi = '10.0.1/1234567890';
        tadc_form_identifiers_to_resource_identifiers($tadc);
        $this->assertEquals('doi:10.0.1/1234567890', $tadc->document_identifier);
    }

    public function test_update_resource_with_tadc_response_data()
    {
        $tadc = tadc_create_new_tadc();
        $response = '{"status":"REJECTED","id":"http:\/\/drp.dev:8080\/life\/request\/4","reason_code":"ElectronicCopyAvailable","url":["http:\/\/lib.myilibrary.com?id=264907&entityid=https:\/\/shibsles.brunel.ac.uk\/idp\/shibboleth"],"request":"url_ver=Z39.88-2004&url_ctx_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Actx&ctx_ver=Z39.88-2004&ctx_enc=info%3Aofi%2Fenc%3AUTF-8&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&rft.genre=bookitem&rft.au=Luke+Welling%2C+Laura+Thomson.&rft.btitle=PHP+and+MySQL+Web+development&rft.date=2009&rft.isbn=9786612649073&rft.atitle=Chapter+11&rft.pub=Addison-Wesley&rfe.code=TEST-101-1369757179&rfe.name=Tests+in+a+Testing+Environment&rfe.size=23&rfe.sdate=2013-04-28&rfe.edate=2013-06-28&req.name=Joe+Bloggs&req.email=jbloggs%40example.org","metadata":{"type":"BookSection","sectionTitle":"Chapter 11","sectionCreators":["Luke Welling, Laura Thomson."],"startPage":null,"endPage":null,"editionTitle":"PHP and MySQL Web development","editionCreators":["Welling, Luke","Thomson, Laura.","MyiLibrary."],"editionStatement":"4th ed.","editionDate":"2008","isbn13":["9786612649073","9780672329166"],"isbn10":["6612649070","0672329166"],"identifiers":{"isbn13":["9786612649073","9780672329166"],"isbn10":["6612649070","0672329166"]},"publisherStrings":["Addison-Wesley"],"publicationCountry":"nju","language":"eng","numberOfPages":968}}';
        tadc_update_resource_with_tadc_response($tadc, json_decode($response, true));
        $this->assertEquals('REJECTED', $tadc->request_status);
        $this->assertEquals('4', $tadc->tadc_id);
        $this->assertEquals('ElectronicCopyAvailable', $tadc->reason_code);
        $this->assertEquals('{"url":["http:\/\/lib.myilibrary.com?id=264907&entityid=https:\/\/shibsles.brunel.ac.uk\/idp\/shibboleth"]}', $tadc->other_response_data);
        $this->assertEquals('book', $tadc->type);
        $this->assertEquals('Chapter 11', $tadc->section_title);
        $this->assertEquals('Luke Welling, Laura Thomson.', $tadc->section_creator);
        $this->assertEquals('PHP and MySQL Web development', $tadc->container_title);
        $this->assertEquals('Welling, Luke; Thomson, Laura.; MyiLibrary.', $tadc->container_creator);
        $this->assertEquals('4th ed.', $tadc->edition);
        $this->assertEquals('2008', $tadc->publication_date);
        $this->assertEquals('isbn:9786612649073', $tadc->container_identifier);
    }

    public function test_rewrite_course_code_for_tadc()
    {
        $this->resetAfterTest(true);
        set_config('course_code_format', '^11\-22_%COURSE_CODE%(\-[A-Z]{3,4})?$', 'tadc');
        $this->assertEquals('FOO123', tadc_format_course_id_for_tadc('11-22_FOO123-TEST'));
        $this->assertEquals('FOO123', tadc_format_course_id_for_tadc('11-22_FOO123'));
        set_config('course_code_format', '%COURSE_CODE%', 'tadc');
        $this->assertEquals('11-22_FOO123-TEST', tadc_format_course_id_for_tadc('11-22_FOO123-TEST'));
    }

    public function test_match_courses_from_tadc_code()
    {
        $this->resetAfterTest(true);
        set_config('course_code_format', '^11\-22_%COURSE_CODE%(\-[A-Z]{3})?$', 'tadc');
        $matching_courses = array('11-22_ABC123', '11-22_ABC123-FOO');
        $not_matching_courses = array('11-22_ABC123-FOOBAR', '11-23_ABC123', '11-22_ABC567');
        foreach($matching_courses as $course)
        {
            $this->getDataGenerator()->create_course(array('idnumber'=>$course));
        }
        foreach($not_matching_courses as $course)
        {
            $this->getDataGenerator()->create_course(array('idnumber'=>$course));
        }

        $courses = tadc_courses_from_tadc_course_id('ABC123');
        $this->assertEquals(2, count($courses));
        foreach($courses as $course)
        {
            $this->assertTrue(in_array($course->idnumber, $matching_courses));
            $this->assertFalse(in_array($course->idnumber, $not_matching_courses));
        }
        set_config('course_code_format', '^[0-9]{2}\-[0-9]{2}_%COURSE_CODE%(\-[A-Z]{3})?$', 'tadc');
        $matching_courses[] = '11-23_ABC123';
        unset($not_matching_courses[1]);
        $courses = tadc_courses_from_tadc_course_id('ABC123');
        $this->assertEquals(3, count($courses));
    }
}