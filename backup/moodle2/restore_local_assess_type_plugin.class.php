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
     * Save the assessment type data to cache during course restore.
     *
     * @param mixed $data Assessment type data.
     */
    public function process_assess_type_course(mixed $data): void {
        $this->cache_data($this->get_cache_key(), $data);
    }

    /**
     * Save the assessment type data to cache during module restore.
     *
     * @param mixed $data Assessment type data.
     */
    public function process_assess_type_module(mixed $data): void {
        $this->cache_data($this->get_cache_key(true), $data);
    }

    /**
     * Process the assessment type data after course restoration.
     */
    public function after_restore_course(): void {
        $this->restore_records($this->get_cache_key());
    }

    /**
     * Process the assessment type data after module restoration (used during duplication).
     */
    public function after_restore_module(): void {
        $this->restore_records($this->get_cache_key(true));
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
     * Define module plugin structure for single-activity restore (used during duplication).
     *
     * @return array Plugin structure paths.
     */
    protected function define_module_plugin_structure(): array {
        return [new restore_path_element('assess_type_module', $this->get_pathfor('/assess_type'))];
    }

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
     * @param bool $module Whether the key is for a module-level restore.
     * @return string Cache key.
     */
    private function get_cache_key(bool $module = false): string {
        $prefix = $module ? 'restore_data_module_' : 'restore_data_';
        return $prefix . $this->task->get_courseid();
    }

    /**
     * Appends data to a cache key's array.
     *
     * @param string $key Cache key.
     * @param mixed $data Data to append.
     */
    private function cache_data(string $key, mixed $data): void {
        $cache = $this->get_cache();
        $dataarray = $cache->get($key) ?: [];
        $dataarray[] = (object)$data;
        $cache->set($key, $dataarray);
    }

    /**
     * Writes cached assessment type records to the database using the given cache key.
     *
     * @param string $cachekey Cache key to read from.
     */
    private function restore_records(string $cachekey): void {
        global $DB;
        $courseid = $this->task->get_courseid();
        $dataarray = $this->get_cache()->get($cachekey);

        if (empty($dataarray) || !is_array($dataarray)) {
            return;
        }

        $fieldmappings = ['cmid' => 'course_module', 'gradeitemid' => 'grade_item'];

        foreach ($dataarray as $data) {
            $data->courseid = $courseid;
            $data->locked = 0;

            foreach ($fieldmappings as $field => $mappingtype) {
                if (!empty($data->$field) && $newid = $this->get_mappingid($mappingtype, $data->$field)) {
                    $data->$field = $newid;
                    if (!$DB->record_exists('local_assess_type', [$field => $newid, 'courseid' => $courseid])) {
                        $DB->insert_record('local_assess_type', $data);
                    }
                    break;
                }
            }
        }

        $this->get_cache()->delete($cachekey);
    }
}
