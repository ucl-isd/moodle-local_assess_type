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

use core_cache\request_cache;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

/**
 * Defines restore_local_assess_type_plugin class.
 *
 * @package    local_assess_type
 * @copyright  2025 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class restore_local_assess_type_plugin extends restore_local_plugin {

    /**
     * Gets the cache instance for storing restore data.
     *
     * @return request_cache Cache instance.
     */
    private function get_cache(): request_cache {
        return \cache::make_from_params(\cache_store::MODE_REQUEST, 'local_assess_type', 'restore_data');
    }

    /**
     * Gets the cache key for the current course.
     *
     * @return string Cache key.
     */
    private function get_cache_key(): string {
        return 'restore_data_' . $this->task->get_courseid();
    }

    /**
     * Define course plugin structure.
     *
     * @return array Plugin structure paths.
     */
    protected function define_course_plugin_structure(): array {
        return [new restore_path_element('assess_type_course', $this->get_pathfor('/assess_type'))];
    }

    /**
     * Save the assessment type data to cache.
     *
     * @param mixed $data Assessment type data.
     */
    public function process_assess_type_course(mixed $data): void {
        $cache = $this->get_cache();
        $key = $this->get_cache_key();
        $dataarray = $cache->get($key) ?: [];
        $dataarray[] = (object)$data;
        $cache->set($key, $dataarray);
    }

    /**
     * Process the assessment type data after course restoration.
     */
    public function after_restore_course(): void {
        global $DB;
        $courseid = $this->task->get_courseid();
        $dataarray = $this->get_cache()->get($this->get_cache_key());

        if (empty($dataarray) || !is_array($dataarray)) {
            return;
        }

        // Process each assessment type record.
        foreach ($dataarray as $data) {
            $data->courseid = $courseid;
            $data->locked = 0;

            // Map course module id if present.
            if (!empty($data->cmid) && $newcmid = $this->get_mappingid('course_module', $data->cmid)) {
                $data->cmid = $newcmid;
                $DB->insert_record('local_assess_type', $data);
            } else if (!empty($data->gradeitemid) && $newgradeitemid = $this->get_mappingid('grade_item', $data->gradeitemid)) {
                $data->gradeitemid = $newgradeitemid;
                $DB->insert_record('local_assess_type', $data);
            }
        }
    }
}
