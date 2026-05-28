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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/assess_type/backup/moodle2/restore_local_assess_type_plugin.class.php');

/**
 * Unit tests for restore_local_assess_type_plugin class.
 *
 * @package    local_assess_type
 * @copyright  2025 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
final class restore_local_assess_type_plugin_test extends \advanced_testcase {
    /**
     * Test setup.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Helper method to create a backup file.
     *
     * @param object $course Course object.
     * @return \stored_file Backup file.
     */
    protected function create_backup_file($course): \stored_file {
        make_backup_temp_directory($course->id);
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $bc->destroy();
        return $results['backup_destination'];
    }

    /**
     * Test backup and restore of a course with both assignment and grade item assess types.
     *
     * @covers \restore_local_assess_type_plugin::process_assess_type_course
     * @covers \restore_local_assess_type_plugin::after_restore_course
     *
     * @return void
     */
    public function test_backup_restore_mixed_assess_types(): void {
        global $DB, $CFG, $USER;

        // Create source course with assignment and grade item.
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Test Assignment']);
        $gradeitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $course->id, 'itemtype' => 'manual',
            'itemname' => 'Manual Grade Item', 'grademax' => 100,
        ]);

        assess_type::update_type($course->id, assess_type::ASSESS_TYPE_SUMMATIVE, $assign->cmid, 0, 1);
        assess_type::update_type($course->id, assess_type::ASSESS_TYPE_FORMATIVE, 0, $gradeitem->id);

        // Backup and restore to new course.
        $backupfile = $this->create_backup_file($course);
        $destcourse = $this->getDataGenerator()->create_course();
        $backuppath = $CFG->tempdir . '/backup/test_restore';
        $backupfile->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backuppath);

        $rc = new \restore_controller(
            'test_restore',
            $destcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();

        // Get restored items.
        $restoredassign = $DB->get_record('assign', ['course' => $destcourse->id, 'name' => 'Test Assignment']);
        $restoredgradeitem = $DB->get_record('grade_items', ['courseid' => $destcourse->id, 'itemname' => 'Manual Grade Item']);
        $this->assertNotEmpty($restoredassign);
        $this->assertNotEmpty($restoredgradeitem);

        // Get restored assignment course module id.
        $cm = get_coursemodule_from_instance('assign', $restoredassign->id, $destcourse->id);
        $this->assertNotEmpty($cm);

        // Verify assessment type exists for the restored assignment.
        $assigntype = $DB->get_record('local_assess_type', ['courseid' => $destcourse->id, 'cmid' => $cm->id, 'gradeitemid' => 0]);
        $this->assertNotEmpty($assigntype);
        $this->assertEquals(1, $assigntype->type);
        $this->assertEquals(0, $assigntype->locked);

        // Verify assessment type exists for the restored grade item.
        $gradeitemtype = $DB->get_record(
            'local_assess_type',
            ['courseid' => $destcourse->id, 'cmid' => 0, 'gradeitemid' => $restoredgradeitem->id]
        );
        $this->assertNotEmpty($gradeitemtype);
        $this->assertEquals(0, $gradeitemtype->type);
        $this->assertEquals(0, $gradeitemtype->locked);

        // Clean up.
        $rc->destroy();
        $backupfile->delete();
        remove_dir($backuppath);
    }

    /**
     * Data provider for test_duplicate_module_copies_assess_type.
     *
     * @return array
     */
    public static function duplicate_module_assess_type_provider(): array {
        return [
            'summative is copied'  => [assess_type::ASSESS_TYPE_SUMMATIVE, 0, assess_type::ASSESS_TYPE_SUMMATIVE, 0],
            'formative is copied'  => [assess_type::ASSESS_TYPE_FORMATIVE, 0, assess_type::ASSESS_TYPE_FORMATIVE, 0],
            'locked flag is reset' => [assess_type::ASSESS_TYPE_SUMMATIVE, 1, assess_type::ASSESS_TYPE_SUMMATIVE, 0],
        ];
    }

    /**
     * Test duplicating an activity copies the assess type and resets locked to 0.
     *
     * @dataProvider duplicate_module_assess_type_provider
     * @covers \restore_local_assess_type_plugin::process_assess_type_module
     * @covers \restore_local_assess_type_plugin::after_restore_module
     * @param int $originaltype Original assessment type value.
     * @param int $originallocked Original locked value.
     * @param int $expectedtype Expected type on the duplicate.
     * @param int $expectedlocked Expected locked value on the duplicate.
     */
    public function test_duplicate_module_copies_assess_type(
        int $originaltype,
        int $originallocked,
        int $expectedtype,
        int $expectedlocked
    ): void {
        global $DB;
        [$course, $assign] = $this->create_course_with_assign();
        assess_type::update_type($course->id, $originaltype, $assign->cmid, 0, $originallocked);

        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($assign->cmid));

        $record = $DB->get_record('local_assess_type', ['cmid' => $newcm->id]);
        $this->assertNotEmpty($record);
        $this->assertEquals($expectedtype, (int)$record->type);
        $this->assertEquals($expectedlocked, (int)$record->locked);
    }

    /**
     * Test duplicating an activity with no assess_type record creates no record for the duplicate.
     *
     * @covers \restore_local_assess_type_plugin::after_restore_module
     */
    public function test_duplicate_module_with_no_assess_type_creates_no_record(): void {
        global $DB;
        [$course, $assign] = $this->create_course_with_assign();

        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($assign->cmid));

        $this->assertFalse($DB->record_exists('local_assess_type', ['cmid' => $newcm->id]));
    }

    /**
     * Create a course with an assignment module.
     *
     * @return array Array containing the course object and the assign module object.
     */
    private function create_course_with_assign(): array {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Test Assignment']);

        return [$course, $assign];
    }
}
