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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace local_usertitles\event;

/**
 * Event triggered when a title is updated.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class title_updated extends \core\event\base {
    /**
     * Initializes the event.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_usertitles_title';
    }

    /**
     * Returns the localized event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventtitleupdated', 'local_usertitles');
    }

    /**
     * Returns the event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' updated the user title with id '{$this->objectid}'.";
    }

    /**
     * Returns the related page.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/usertitles/edit.php', ['id' => $this->objectid]);
    }
}

