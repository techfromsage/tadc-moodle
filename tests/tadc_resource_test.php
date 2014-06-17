<?php

global $CFG;
require_once($CFG->dirroot . '/mod/tadc/locallib.php');

class mod_tadc_resource_test extends advanced_testcase {


    public function test_generate_html_citation()
    {
        $tadc = new stdClass();
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

    public function test_rewrite_course_code_for_tadc()
    {
        $this->resetAfterTest(true);
        set_config('course_code_regex', '^11\-22_%COURSE_CODE%(\-[A-Z]{3,4})?$', 'tadc');
        $this->assertEquals('FOO123', tadc_format_course_id_for_tadc('11-22_FOO123-TEST'));
        $this->assertEquals('FOO123', tadc_format_course_id_for_tadc('11-22_FOO123'));
        set_config('course_code_regex', '%COURSE_CODE%', 'tadc');
        $this->assertEquals('11-22_FOO123-TEST', tadc_format_course_id_for_tadc('11-22_FOO123-TEST'));
    }

    public function test_match_courses_from_tadc_code()
    {
        $this->resetAfterTest(true);
        set_config('course_code_regex', '^11\-22_%COURSE_CODE%(\-[A-Z]{3})?$', 'tadc');
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
        set_config('course_code_regex', '^[0-9]{2}\-[0-9]{2}_%COURSE_CODE%(\-[A-Z]{3})?$', 'tadc');
        $matching_courses[] = '11-23_ABC123';
        unset($not_matching_courses[1]);
        $courses = tadc_courses_from_tadc_course_id('ABC123');
        $this->assertEquals(3, count($courses));
    }

    public function test_generate_launch_url()
    {
        $this->assertEquals('http://example.com/foobar' . TADC_LTI_LAUNCH_PATH, tadc_generate_launch_url('http://example.com', 'foobar'));
        $this->assertEquals('http://example.com/foobaz' . TADC_LTI_LAUNCH_PATH, tadc_generate_launch_url('http://example.com/', 'foobaz'));
    }

    public function test_add_lti_properties()
    {
        $this->resetAfterTest(true);
        $year = date('Y').(date('y')+1);
        $course = $this->getDataGenerator()->create_course(array('idnumber'=>'TEST01_'.$year, 'fullname'=>'Test of Test', 'startdate'=>time()));
        /** @var mod_tadc_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tadc');
        $tadc = $generator->create_instance(array('course' => $course->id, 'name'=>'Course reading'));
        set_config('course_code_field', 'idnumber', 'tadc');
        set_config('tadc_location', 'https://example.com', 'tadc');
        set_config('tenant_code', 'foobar', 'tadc');
        set_config('course_code_regex', '^([A-Za-z0-9]{6})_[0-9]{6}$', 'tadc');
        set_config('api_key', 'foobarbaz', 'tadc');
        set_config('tadc_shared_secret', 'bazbarfoo', 'tadc');

        tadc_add_lti_properties($tadc);
        $this->assertEquals('https://example.com/foobar/lti/launch', $tadc->toolurl);
        $this->assertFalse($tadc->instructorchoiceacceptgrades);
        $this->assertTrue($tadc->instructorchoicesendname);
        $this->assertTrue($tadc->instructorchoicesendemailaddr);
        $this->assertNotNull($tadc->servicesalt);

        $this->assertRegExp("/^launch_identifier=\w*\n/", $tadc->instructorcustomparameters);
        $this->assertContains("course_code=TEST01\n", $tadc->instructorcustomparameters);
        $this->assertContains("course_name=Test of Test", $tadc->instructorcustomparameters);
        $this->assertContains("course_start=" . date("Y-m-d", $course->startdate), $tadc->instructorcustomparameters);
        $this->assertContains("source=" . TADC_SOURCE_URI, $tadc->instructorcustomparameters);
        $this->assertContains("trackback=" . urlencode(new moodle_url("/mod/tadc/trackback.php", array('t'=>$tadc->id, 'api_key'=>'foobarbaz'))), $tadc->instructorcustomparameters);
        $this->assertFalse($tadc->debuglaunch);
        $this->assertEquals('foobarbaz', $tadc->resourcekey);
        $this->assertEquals('bazbarfoo', $tadc->password);

    }    
}