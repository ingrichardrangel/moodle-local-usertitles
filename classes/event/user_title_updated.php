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
 * Event triggered when a user's title assignment changes.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_title_updated extends \core\event\base {
    /**
     * Initializes the event.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_usertitles_assignment';
    }

    /**
     * Returns the localized event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventusertitleupdated', 'local_usertitles');
    }

    /**
     * Returns the event description.
     *
     * @return string
     */
    public function get_description(): string {
        $titleid = $this->other['titleid'];
        return "The user with id '{$this->userid}' changed the title for user with id " .
            "'{$this->relateduserid}' to title id '{$titleid}'.";
    }

    /**
     * Returns the assignment page.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/usertitles/assign.php', ['userid' => $this->relateduserid]);
    }

    /**
     * Defines mappings for data stored in the other property.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            'titleid' => ['db' => 'local_usertitles_title', 'restore' => 'local_usertitles_title'],
        ];
    }
}

