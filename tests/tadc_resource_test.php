<?php

global $CFG;
require_once($CFG->dirroot . '/mod/tadc/locallib.php');

class mod_tadc_resource_test extends advanced_testcase {
    public function test_create_new_tadc_object()
    {
        $tadc = tadc_create_new_tadc();
        $this->assertObjectHasAttribute('id', $tadc);
        $this->assertObjectHasAttribute('course_id', $tadc);
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
        $tadc->course_id = $course->id;
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
        $tadc->course_id = $COURSE->id;
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
    }
}