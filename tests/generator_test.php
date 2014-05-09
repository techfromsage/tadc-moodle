
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPUnit data generator tests
 *
 * @package    mod_tadc
 * @category   phpunit
 * @copyright  Copyright (c) 2014 Talis Education Ltd. (http://www.talis.com)
 * @author     Ross Singer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit data generator testcase
 *
 * @package    mod_tadc
 * @category   phpunit
 * @copyright  Copyright (c) 2014 Talis Education Ltd. (http://www.talis.com)
 * @author     Ross Singer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tadc_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('tadc'));

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_tadc_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tadc');
        $this->assertInstanceOf('mod_tadc_generator', $generator);
        $this->assertEquals('tadc', $generator->get_modulename());

        $request = $generator->create_instance(array('course' => $course->id, 'name'=>'Course reading'));
        $records = $DB->get_records('tadc', array('course' => $course->id));
        $this->assertEquals(1, count($records));
        $record = current($records);
        $this->assertEquals($request->id, $record->id);
        $this->assertEquals('Course reading', $record->name);

        $request = $generator->create_instance(array('course' => $course->id, 'name'=>'Course reading 2', 'tadc_id'=>1, 'request_status'=>'REJECTED', 'reason_code'=>'InvalidRequest'));
        $record = $DB->get_record('tadc', array('id'=>$request->id));
        $this->assertEquals('Course reading 2', $record->name);
        $this->assertEquals(1, $record->tadc_id);
        $this->assertEquals('REJECTED', $record->request_status);
        $request = $generator->create_instance(array('course' => $course->id, 'name'=>'Course reading 3', 'tadc_id'=>2, 'request_status'=>'LIVE', 'bundle_url'=>'foobar', 'citation'=>"<p>foobaz</p>", 'citationformat'=>FORMAT_HTML));
        $this->assertEquals(3, $DB->count_records('tadc'));
        $record = $DB->get_record('tadc', array('id'=>$request->id));
        $this->assertEquals('Course reading 3', $record->name);
        $this->assertEquals(2, $record->tadc_id);
        $this->assertEquals('LIVE', $record->request_status);
        $this->assertEquals('foobar', $record->bundle_url);
        $this->assertEquals('<p>foobaz</p>', $record->citation);
        $this->assertEquals(FORMAT_HTML, $record->citationformat);

        $cm = get_coursemodule_from_instance('tadc', $request->id);
        $this->assertEquals($request->id, $cm->instance);
        $this->assertEquals('tadc', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($request->cmid, $context->instanceid);

    }
}
