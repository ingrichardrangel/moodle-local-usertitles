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
 * Installation steps for the User titles plugin.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Seeds the initial Professor title.
 *
 * @return void
 */
function xmldb_local_usertitles_install(): void {
    global $DB;

    if ($DB->record_exists('local_usertitles_title', ['abbreviation' => 'Prof.'])) {
        return;
    }

    $now = time();
    $title = (object) [
        'name' => 'Professor',
        'abbreviation' => 'Prof.',
        'enabled' => 1,
        'sortorder' => 10,
        'timecreated' => $now,
        'timemodified' => $now,
    ];
    $DB->insert_record('local_usertitles_title', $title);
}
