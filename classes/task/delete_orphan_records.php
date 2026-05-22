<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_assess_type\task;

use core\task\scheduled_task;

/**
 * Scheduled task to delete orphaned assessment type records.
 *
 * @package    local_assess_type
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_orphan_records extends scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:deleteorphanrecords:name', 'local_assess_type');
    }

    /**
     * Run the scheduled task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $orphanids = [];

        // Find records where cmid references a deleted course module.
        $sql = "SELECT a.id
                FROM {local_assess_type} a
                LEFT JOIN {course_modules} cm ON cm.id = a.cmid
                WHERE a.cmid != 0 AND cm.id IS NULL";
        $orphanids = array_merge($orphanids, $DB->get_fieldset_sql($sql));

        // Find records where gradeitemid references a deleted grade item.
        $sql = "SELECT a.id
                FROM {local_assess_type} a
                LEFT JOIN {grade_items} gi ON gi.id = a.gradeitemid
                WHERE a.gradeitemid != 0 AND gi.id IS NULL";
        $orphanids = array_merge($orphanids, $DB->get_fieldset_sql($sql));

        if (empty($orphanids)) {
            mtrace('No orphaned assessment type records found.');
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($orphanids);
        $DB->delete_records_select('local_assess_type', "id $insql", $params);

        mtrace(count($orphanids) . ' orphaned assessment type record(s) deleted.');
    }
}
