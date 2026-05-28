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

namespace local_assess_type;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * PHPUnit tests for local_assess_type observer.
 *
 * @package    local_assess_type
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
final class observer_test extends \advanced_testcase {
    /**
     * Test setup.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test deleting a course module removes the assess_type record.
     *
     * @covers \local_assess_type\observer::course_module_deleted
     */
    public function test_deleting_module_removes_assess_type_record(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        assess_type::update_type($course->id, assess_type::ASSESS_TYPE_SUMMATIVE, $quiz->cmid);

        // Verify assess type record exists for the quiz.
        $this->assertTrue($DB->record_exists('local_assess_type', ['cmid' => $quiz->cmid]));

        course_delete_module($quiz->cmid);

        // Verify assess type record is deleted.
        $this->assertFalse($DB->record_exists('local_assess_type', ['cmid' => $quiz->cmid]));
    }

    /**
     * Test deleting a manual grade item removes the assess_type record.
     *
     * @covers \local_assess_type\observer::grade_item_deleted
     */
    public function test_deleting_grade_item_removes_assess_type_record(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $course->id,
            'itemtype' => 'manual',
            'itemname' => 'Test Grade Item',
        ]);
        assess_type::update_type($course->id, assess_type::ASSESS_TYPE_SUMMATIVE, 0, $gradeitem->id);

        // Verify assess type record exists for the grade item.
        $this->assertTrue($DB->record_exists('local_assess_type', ['gradeitemid' => $gradeitem->id]));

        $item = \grade_item::fetch(['id' => $gradeitem->id]);
        $item->delete('test');

        // Verify assess type record is deleted.
        $this->assertFalse($DB->record_exists('local_assess_type', ['gradeitemid' => $gradeitem->id]));
    }
}
