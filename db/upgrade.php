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

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     local_assess_type
 * @category    upgrade
 * @copyright   2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Alex Yeung <k.yeung@ucl.ac.uk>
 */

/**
 * Execute local_assess_type upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_assess_type_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024091300) {
        $table = new xmldb_table('local_assess_type');

        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'courseid');
        // Launch change of nullability for field cmid.
        $dbman->change_field_notnull($table, $field);
        // Launch change of default for field cmid.
        $dbman->change_field_default($table, $field);

        $field = new xmldb_field('gradeitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'cmid');
        // Launch change of nullability for field gradeitemid.
        $dbman->change_field_notnull($table, $field);
        // Launch change of default for field gradeitemid.
        $dbman->change_field_default($table, $field);

        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'gradeitemid');
        // Launch change of nullability for field type.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('locked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'type');
        // Launch change of nullability for field locked.
        $dbman->change_field_notnull($table, $field);
        // Launch change of default for field locked.
        $dbman->change_field_default($table, $field);

        // Assess_type savepoint reached.
        upgrade_plugin_savepoint(true, 2024091300, 'local', 'assess_type');
    }

    if ($oldversion < 2026051900) {
        $table = new xmldb_table('local_assess_type');

        $index = new xmldb_index('cmid', XMLDB_INDEX_NOTUNIQUE, ['cmid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('gradeitemid', XMLDB_INDEX_NOTUNIQUE, ['gradeitemid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Local_assess_type savepoint reached.
        upgrade_plugin_savepoint(true, 2026051900, 'local', 'assess_type');
    }

    return true;
}
