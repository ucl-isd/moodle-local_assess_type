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
 * Lib.
 *
 * @package    local_ass_type
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stuart Lamour <s.lamour@ucl.ac.uk>
 */

 defined('MOODLE_INTERNAL') || die();

 /**
 * Check if an activity is sits mapped.
 *
 */
function sitsmapped($cmid) {
    global $DB;

    $dbman = $DB->get_manager();
    $table = 'local_sitsgradepush_mapping';
    if ($dbman->table_exists($table)) {
        if ($DB->get_record($table, array('sourceid' => $cmid), 'id')) {
            return true;
        }
    }
    return false;
}

/**
 * Check if an activity can be summative.
 *
 */
function canbesummative($modtype) {
    // Activites which can be marked summative.
    $modarray = [
        'assign',
        'quiz',
        'workshop',
        'turnitintooltwo'
    ];

    if (in_array($modtype, $modarray)) {
        return true;
    }
    return false;
}

/**
 * Add Formative or Summative select options to mods.
 *
 */
function theme_ucl_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB, $PAGE, $CFG;

    $cm = $formwrapper->get_current();
    // Check list of mods where this is enabled.
    if (!canbesummative($cm->modulename)) {
        return; // Exit if not enabled.
    }

    // Flag if new cm.
    $newcm = true;
    if ($cmid = $cm->coursemodule) {
        $newcm = false;
    }
    // Flag if sits mapped.
    $sitsmapped = false;
    if ($cmid) {
        $sitsmapped = sitsmapped($cmid);
    }

    // Mform element.
    $options = [];
    $options[''] = 'Select an option';
    $options['0'] = 'Formative - does not contribute to course mark';
    $options['1'] = 'Summative - counts towards course mark and is mapped in SITS';
    $attributes = [];
    $attributes['required'] = 'required';

    // Disable changes when sits mapped.
    if ($sitsmapped) {
        $attributes['disabled'] = 'disabled';
    }
    $select = $mform->createElement('select', 'type', 'Formative or summative?', $options, $attributes);

    // Set to summative when sits mapped.
    if ($sitsmapped) {
        $select->setSelected(1);
    }

    // Set existing option from db (when not sits mapped or new).
    if (!$sitsmapped && $cmid) {
        $table = 'ass_type';
        if ($record = $DB->get_record($table, array('cmid' => $cmid), 'type')) {
            $select->setSelected($record->type);
        }
    }

    // Link to edit when cm exists.
    // TODO - lang string.
    if ($cmid) {
        $link .= '<br>
        <a href="' . $CFG->wwwroot. '/local/sitsgradepush/dashboard.php?id=' .$cm->course. '" target="_blank"
        >Edit activity SITS link
        </a>';
    }

    $info = $mform->createElement('html',
    '<div class="col-md-9 offset-md-3 pb-3">
    Summative activities count towards the course mark in SITS and are flagged to learners as mandatory.'
    . $link .'</div>');

    // Add form elements to the dom.
    // TODO - check best place in the form to add?
    $mform->insertElementBefore($select, 'introeditor');
    $mform->insertElementBefore($info, 'introeditor');
}

/**
 * Save Formative or Summative select options.
 *
 */
function theme_ucl_coursemodule_edit_post_actions($data, $course) {
    global $DB, $PAGE;
    $table = 'ass_type';

    // Record for update/insert.
    $r = new \stdClass();
    $r->type = $data->type;
    $r->cmid = $PAGE->cm->id;
    $r->courseid = $course->id;

    // If record exists
    if ( $record = $DB->get_record($table, array('cmid' => $r->cmid), 'id, type') ) {
        // If record has changed.
        if ($record->type != $r->type) {
            $r->id = $record->id;
            $DB->update_record(
                $table,
                $r,
                $bulk = false
            );
        }
    } else {
        $DB->insert_record(
            $table,
            $r,
            $returnid = false,
            $bulk = false
        );
    }
    return $data;
}
