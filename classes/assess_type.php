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
 * Assessment type class.
 *
 * @package    local_assess_type
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */
class assess_type {

    /**
     * Return assess type int - helper for other functions.
     *
     * @param int $cmid
     * @return int|null
     */
    public static function get_type_int(int $cmid): ?int {
        global $DB;
        if ($r = $DB->get_record('local_assess_type', ['cmid' => $cmid])) {
            return $r->type;
        }
        return null;
    }

    /**
     * Return the assess type name - summative, formative or other.
     *
     * @param int $cmid
     * @return string|null
     */
    public static function get_type_name(int $cmid): ?string {
        if ($typeint = self::get_type_int($cmid)) {
            switch ($typeint) {
                case '0':
                    return get_string('formative', 'local_assess_type');
                case '1':
                    return get_string('summative', 'local_assess_type');
                case '2':
                    return get_string('dummy', 'local_assess_type');
                default:
                    return "Not set"; // This should never happen.
            }
        }
        return null;
    }

    /**
     * Return if assess type is summative.
     *
     * @param int $cmid
     * @return bool
     */
    public static function is_summative(int $cmid): bool {
        if (self::get_type_int($cmid) == '1') {
            return true;
        }
        return false;
    }

    /**
     * Update the assess type.
     *
     * @param int $cmid - The mod id.
     * @param int $courseid - The course id.
     * @param int $type - formative/summative/dummy.
     */
    public static function update_type(int $cmid, int $courseid, int $type) {
        global $DB;
        $table = 'local_assess_type';

        // Record to write.
        $r = new \stdClass();
        $r->type = $type;
        $r->cmid = $cmid;
        $r->courseid = $courseid;

        // If record exists.
        if ($record = $DB->get_record($table, ['cmid' => $cmid], 'id, type')) {
            // If record has changed.
            if ($record->type != $type) {
                $r->id = $record->id;
                // Update record.
                $DB->update_record(
                    $table,
                    $r,
                    $bulk = false
                );
            }
        } else {
            // New record.
            $DB->insert_record(
                $table,
                $r,
                $returnid = false,
                $bulk = false
            );
        }
    }

}
