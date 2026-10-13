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
 * An admin setting for selecting configured lti tools.
 *
 * @package    local_assess_type
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @copyright  2022 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Sarah Cotton <sarah.cotton@catalyst-eu.net>
 */

namespace local_assess_type\settings;

use admin_setting_configselect_autocomplete;
use local_assess_type\config;

/**
 * An admin setting for selecting configured lti tools.
 */
class admin_setting_configlti extends admin_setting_configselect_autocomplete {
    /**
     * Load the available choices for the select box
     *
     * @return bool
     */
    public function load_choices(): bool {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/lib.php');

        if (is_array($this->choices)) {
            return true;
        }

        $choices = lti_get_lti_types();
        $allowedchoices = [];
        $countchoices = [];

        foreach ($choices as $choice) {
            $countchoices[$choice->id] = $choice->tooldomain . ' ' . self::display_version($choice->ltiversion);
        }

        $count = array_count_values($countchoices);

        foreach ($choices as $choice) {
            $name = $choice->tooldomain . ' ' . self::display_version($choice->ltiversion);
            $name = $choice->tooldomain . ' ' . self::display_version($choice->ltiversion) . ' (' . $count[$name] . ')';
            if (!in_array($name, $allowedchoices)) {
                $allowedchoices[$choice->id] = $name;
            } else {
                $oldkey = array_search($name, $allowedchoices);
                $newkey = $oldkey . '_' . $choice->id;
                $allowedchoices[$newkey] = $allowedchoices[$oldkey];
                unset($allowedchoices[$oldkey]);
            }
        }

        asort($allowedchoices);
        $this->choices = $allowedchoices;

        return true;
    }

    /**
     * Display the LTI version in human-readable format.
     *
     * @param string $ltiversion the LTI version     *
     * @return string
     */
    public function display_version(string $ltiversion): string {
        if ($ltiversion == 'LTI-1p0') {
            $ltiversion = 'LTI-1';
        } else if (str_contains($ltiversion, '1p3') || str_contains($ltiversion, '1.3')) {
            $ltiversion = 'LTI-3';
        } else {
            $ltiversion = 'No LTI version';
        }
        return $ltiversion;
    }

    /**
     * Return the HTML output for the field.
     *
     * @param string $data parent function parameter
     * @param string $query parent function parameter
     * @return string The HTML element
     */
    public function output_html($data, $query = ''): string {
        global $OUTPUT;

        if (!$this->load_choices() || empty($this->choices)) {
            return '';
        }

        $context = [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
        ];

        $template = 'core_admin/local/settings/autocomplete';
        $default = $this->get_defaultsetting();
        $options = [];

        $savedsetting = config::instance()->get_lti_types();

        foreach ($this->choices as $value => $name) {
            $selected = false;

            if (in_array($value, $savedsetting)) {
                $selected = true;
            }

            $options[] = [
                'value' => $value,
                'text' => $name,
                'selected' => $selected,
                'disabled' => false,
            ];
        }

        $context['options'] = $options;
        $context['tags'] = $this->tags;
        $context['placeholder'] = get_string('placeholder', 'local_assess_type');
        $context['casesensitive'] = $this->casesensitive;
        $context['multiple'] = true;
        $context['showsuggestions'] = $this->showsuggestions;

        $element = $OUTPUT->render_from_template($template, $context);

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $default, $query);
    }

    /**
     * Saves setting(s) provided through $data
     *
     * @param array $data parent function parameter
     * @return string
     */
    public function write_setting($data): string {
        if (!is_array($data)) {
            return ''; // Ignore it.
        }

        if (!$this->load_choices() || empty($this->choices)) {
            return '';
        }

        // Dummy value set in core/form_autocomplete_input template used as a partial.
        unset($data['xxxxx']);

        $save = [];
        foreach ($data as $value) {
            if (!array_key_exists($value, $this->choices)) {
                continue; // Ignore it.
            }
            $save[] = $value;
        }

        return ($this->config_write($this->name, implode(',', $save)) ? '' : get_string('errorsetting', 'admin'));
    }
}
