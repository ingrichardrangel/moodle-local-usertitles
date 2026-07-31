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

namespace local_usertitles;

use core\hook\output\before_standard_head_html_generation;

/**
 * Output hook callbacks for visual title display.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Loads the visual title module on authenticated Moodle pages.
     *
     * @param before_standard_head_html_generation $hook Output hook.
     * @return void
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $PAGE, $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $enabled = get_config('local_usertitles', 'enablevisualtitles');
        if ($enabled === '0') {
            return;
        }

        $PAGE->requires->js_call_amd(
            'local_usertitles/display_titles',
            'init',
            [(int) $USER->id]
        );
    }
}
