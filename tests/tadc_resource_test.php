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
}