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

/**
 * PHPUnit tests for local_assess_type.
 *
 * @package    local_assess_type
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Leon Stringer <leon.stringer@ucl.ac.uk>
 */
final class assess_type_test extends \advanced_testcase {
    /**
     * Test strings returned by assess_type::get_type_name() are the expected
     * values.
     * @covers \local_assess_type\assess_type::get_type_name
     * @dataProvider assess_type_strings
     * @param int $assesstype ASSESS_TYPE_FORMATIVE, ASSESS_TYPE_SUMMATIVE,
     * etc.
     * @param string $name "Formative", "Summative", etc.
     */
    public function test_get_type_name(int $assesstype, string $name): void {
        $this->resetAfterTest(true);

        // Make a course.
        $course = $this->getDataGenerator()->create_course();

        // Make a quiz and add it to the course.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'questionsperpage' => 0,
            'grade' => 100.0, 'sumgrades' => 2, 'preferredbehaviour' => 'immediatefeedback']);

        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        assess_type::update_type($course->id, $assesstype, $cm->id);
        $this->assertEquals(assess_type::get_type_name($cm->id), $name);
    }

    /**
     * Data provider.
     */
    public static function assess_type_strings(): array {
        return [
            'formative' => [
                'assesstype' => assess_type::ASSESS_TYPE_FORMATIVE,
                'name' => get_string('formative', 'local_assess_type'),
            ],
            'summative' => [
                'assesstype' => assess_type::ASSESS_TYPE_SUMMATIVE,
                'name' => get_string('summative', 'local_assess_type'),
            ],
            'dummy' => [
                'assesstype' => assess_type::ASSESS_TYPE_DUMMY,
                'name' => get_string('dummy', 'local_assess_type'),
            ],
        ];
    }

    /**
     * Bulk lookups retain the course and assessment-type filters.
     *
     * @covers \local_assess_type\assess_type::get_assess_type_records_by_courseids
     */
    public function test_get_assess_type_records_by_courseids(): void {
        global $DB;

        $this->resetAfterTest();
        $firstcourse = $this->getDataGenerator()->create_course();
        $secondcourse = $this->getDataGenerator()->create_course();
        $thirdcourse = $this->getDataGenerator()->create_course();

        $firstid = $DB->insert_record('local_assess_type', (object)[
            'courseid' => $firstcourse->id,
            'cmid' => 1,
            'gradeitemid' => 0,
            'type' => assess_type::ASSESS_TYPE_SUMMATIVE,
            'locked' => 0,
        ]);
        $secondid = $DB->insert_record('local_assess_type', (object)[
            'courseid' => $secondcourse->id,
            'cmid' => 2,
            'gradeitemid' => 0,
            'type' => assess_type::ASSESS_TYPE_SUMMATIVE,
            'locked' => 0,
        ]);
        foreach ([$firstcourse, $thirdcourse] as $course) {
            $DB->insert_record('local_assess_type', (object)[
                'courseid' => $course->id,
                'cmid' => 3,
                'gradeitemid' => 0,
                'type' => assess_type::ASSESS_TYPE_FORMATIVE,
                'locked' => 0,
            ]);
        }

        $records = assess_type::get_assess_type_records_by_courseids(
            [$firstcourse->id, $secondcourse->id],
            assess_type::ASSESS_TYPE_SUMMATIVE
        );
        $this->assertEqualsCanonicalizing([$firstid], array_keys($records[$firstcourse->id]));
        $this->assertEqualsCanonicalizing([$secondid], array_keys($records[$secondcourse->id]));
        $this->assertSame([], assess_type::get_assess_type_records_by_courseids([]));
    }

    /**
     * Existing sites receive the new course/type lookup index on upgrade.
     *
     * @coversNothing
     */
    public function test_course_type_index_upgrade(): void {
        global $CFG, $DB;

        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/assess_type/db/upgrade.php');
        $this->resetAfterTest();

        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_assess_type');
        $index = new \xmldb_index('courseid-type', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'type']);
        $currentversion = (int)get_config('local_assess_type', 'version');
        $oldversion = 2026051900;
        $this->assertTrue($dbman->table_exists($table));
        $hadindex = $dbman->index_exists($table, $index);

        if ($hadindex) {
            $dbman->drop_index($table, $index);
        }
        set_config('version', $oldversion, 'local_assess_type');
        try {
            $this->assertFalse($dbman->index_exists($table, $index));
            $this->assertTrue(xmldb_local_assess_type_upgrade($oldversion));
            $this->assertTrue($dbman->index_exists($table, $index));
            $this->assertSame(2026100600, (int)get_config('local_assess_type', 'version'));
        } finally {
            if ($hadindex && !$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            } else if (!$hadindex && $dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
            set_config('version', $currentversion, 'local_assess_type');
        }
    }
}
