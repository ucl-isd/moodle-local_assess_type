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
 * Version.
 *
 * @package    local_assess_type
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */

 /**
 * Database:
 *
 * assess_type(id, courseid, cmid, type)
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Restore assess_type
 */
class restore_local_assess_type_plugin extends restore_local_plugin {

    protected function define_module_plugin_structure() {
        $paths = array();
        // restore_path_element needs postfix of 'process_*', path in xml.
        $pathname = 'plugin_local_assess_type_module';
        $paths[] = new restore_path_element($pathname, "/module/$pathname");
        return $paths;
    }

    public function process_plugin_local_assess_type_module($data) {
        global $DB;
        $table = 'local_assess_type';
        // Record for update/insert.
        $r = new \stdClass();
        $r->type = $data['type'];
        $r->cmid = $this->task->get_moduleid();
        $r->courseid =  $this->task->get_courseid();
        $DB->insert_record($table, $r);
    }
}