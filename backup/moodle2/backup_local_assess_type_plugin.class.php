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
 * Backup assess_type
 */
class backup_local_assess_type_plugin extends backup_local_plugin {


    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(),
            ['id'],
            ['type']
        );
        $plugin->add_child($pluginwrapper);
        $pluginwrapper->set_source_sql(
            'SELECT id, type FROM {local_assess_type} WHERE cmid = :cmid AND type IS NOT NULL',
            ['cmid' => backup::VAR_PARENTID]
        );
        return $plugin;
    }
}
