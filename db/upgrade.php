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

/**
 * Upgrade steps for the User titles plugin.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Performs plugin upgrades.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_local_usertitles_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026073002) {
        set_config('syncalternatename', 0, 'local_usertitles');

        $recordset = $DB->get_recordset_select(
            'local_usertitles_assignment',
            'syncedvalue IS NOT NULL',
            [],
            'id ASC',
            'id, userid, syncedvalue'
        );
        foreach ($recordset as $assignment) {
            $alternatename = $DB->get_field('user', 'alternatename', ['id' => $assignment->userid]);
            if ($alternatename !== false && (string) $alternatename === (string) $assignment->syncedvalue) {
                $DB->set_field('user', 'alternatename', '', ['id' => $assignment->userid]);
            }
            $DB->set_field(
                'local_usertitles_assignment',
                'syncedvalue',
                null,
                ['id' => $assignment->id]
            );
        }
        $recordset->close();

        upgrade_plugin_savepoint(true, 2026073002, 'local', 'usertitles');
    }

    return true;
}
