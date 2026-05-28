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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

/**
 * Defines backup_local_assess_type_plugin class.
 *
 * @package    local_assess_type
 * @copyright  2025 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class backup_local_assess_type_plugin extends backup_local_plugin {
    /**
     * Define course plugin structure.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure(): backup_plugin_element {
        return $this->build_plugin_structure(['courseid' => backup::VAR_COURSEID]);
    }

    /**
     * Define module plugin structure for single-activity backup (used during duplication).
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        return $this->build_plugin_structure(['cmid' => backup::VAR_MODID]);
    }

    /**
     * Build the plugin element structure with the given source condition.
     *
     * @param array $sourcecondition Source table filter passed to set_source_table.
     * @return backup_plugin_element
     */
    private function build_plugin_structure(array $sourcecondition): backup_plugin_element {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $assesstypedata = new backup_nested_element('assess_type', ['id'], ['cmid', 'gradeitemid', 'type', 'locked']);
        $wrapper->add_child($assesstypedata);
        $plugin->add_child($wrapper);
        $assesstypedata->set_source_table('local_assess_type', $sourcecondition);
        return $plugin;
    }
}
