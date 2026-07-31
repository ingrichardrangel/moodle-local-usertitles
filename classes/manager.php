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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_usertitles;

use context_system;
use context_user;
use local_usertitles\event\title_created;
use local_usertitles\event\title_deleted;
use local_usertitles\event\title_updated;
use local_usertitles\event\user_title_updated;

/**
 * Business logic for titles and user assignments.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var string Synchronization updated the user. */
    public const SYNC_UPDATED = 'updated';

    /** @var string Synchronization was already current. */
    public const SYNC_UNCHANGED = 'unchanged';

    /** @var string Synchronization found an independently managed alternate name. */
    public const SYNC_CONFLICT = 'conflict';

    /** @var string Synchronization could not find required data. */
    public const SYNC_MISSING = 'missing';

    /**
     * Returns titles ordered for display.
     *
     * @param bool $includedisabled Whether inactive titles should be included.
     * @return array
     */
    public static function get_titles(bool $includedisabled = true): array {
        global $DB;

        $conditions = $includedisabled ? [] : ['enabled' => 1];
        return $DB->get_records(
            'local_usertitles_title',
            $conditions,
            'sortorder ASC, name ASC, id ASC'
        );
    }

    /**
     * Returns one title.
     *
     * @param int $titleid Title identifier.
     * @param int $strictness MUST_EXIST, IGNORE_MISSING, or IGNORE_MULTIPLE.
     * @return \stdClass|false
     */
    public static function get_title(int $titleid, int $strictness = MUST_EXIST) {
        global $DB;

        return $DB->get_record('local_usertitles_title', ['id' => $titleid], '*', $strictness);
    }

    /**
     * Creates a title.
     *
     * @param \stdClass $data Validated title data.
     * @return \stdClass
     */
    public static function create_title(\stdClass $data): \stdClass {
        global $DB;

        $record = self::prepare_title_record($data);
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->id = $DB->insert_record('local_usertitles_title', $record);

        title_created::create([
            'context' => context_system::instance(),
            'objectid' => $record->id,
        ])->trigger();

        return self::get_title($record->id);
    }

    /**
     * Updates a title.
     *
     * @param \stdClass $data Validated title data containing an id.
     * @return \stdClass
     */
    public static function update_title(\stdClass $data): \stdClass {
        global $DB;

        $current = self::get_title((int) $data->id);
        $record = self::prepare_title_record($data);
        $record->id = $current->id;
        $record->timemodified = time();

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('local_usertitles_title', $record);
        $transaction->allow_commit();

        title_updated::create([
            'context' => context_system::instance(),
            'objectid' => $record->id,
        ])->trigger();

        return self::get_title($record->id);
    }

    /**
     * Deletes a title and its assignments.
     *
     * Alternate names are cleared only if they still contain the exact value
     * previously written by this plugin.
     *
     * @param int $titleid Title identifier.
     * @return void
     */
    public static function delete_title(int $titleid): void {
        global $DB;

        $title = self::get_title($titleid);
        $transaction = $DB->start_delegated_transaction();
        $assignments = $DB->get_records('local_usertitles_assignment', ['titleid' => $titleid]);

        foreach ($assignments as $assignment) {
            self::clear_user_sync((int) $assignment->userid);
        }

        $DB->delete_records('local_usertitles_assignment', ['titleid' => $titleid]);
        $DB->delete_records('local_usertitles_title', ['id' => $titleid]);
        $transaction->allow_commit();

        title_deleted::create([
            'context' => context_system::instance(),
            'objectid' => $title->id,
            'other' => [
                'name' => $title->name,
                'abbreviation' => $title->abbreviation,
            ],
        ])->trigger();
    }

    /**
     * Counts assignments for each title.
     *
     * @return array An array keyed by title id.
     */
    public static function get_assignment_counts(): array {
        global $DB;

        $sql = "SELECT titleid, COUNT(1) AS usercount
                  FROM {local_usertitles_assignment}
              GROUP BY titleid";
        return $DB->get_records_sql_menu($sql);
    }

    /**
     * Returns the assignment record for a user.
     *
     * @param int $userid User identifier.
     * @return \stdClass|false
     */
    public static function get_user_assignment(int $userid) {
        global $DB;

        return $DB->get_record('local_usertitles_assignment', ['userid' => $userid]);
    }

    /**
     * Returns the title assigned to a user, including assignment metadata.
     *
     * @param int $userid User identifier.
     * @return \stdClass|false
     */
    public static function get_user_title(int $userid) {
        global $DB;

        $sql = "SELECT t.id, t.name, t.abbreviation, t.enabled, t.sortorder,
                       t.timecreated, t.timemodified,
                       a.id AS assignmentid, a.syncedvalue
                  FROM {local_usertitles_title} t
                  JOIN {local_usertitles_assignment} a ON a.titleid = t.id
                 WHERE a.userid = :userid";
        return $DB->get_record_sql($sql, ['userid' => $userid]);
    }

    /**
     * Assigns or removes a title.
     *
     * @param int $userid User identifier.
     * @param int $titleid Title identifier, or zero to remove the assignment.
     * @param bool $triggerevent Whether to trigger an audit event.
     * @return \stdClass|null The resulting assignment or null.
     */
    public static function set_user_title(int $userid, int $titleid, bool $triggerevent = true): ?\stdClass {
        global $DB;

        $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', MUST_EXIST);
        $assignment = self::get_user_assignment($userid);
        if ($titleid !== 0) {
            $title = self::get_title($titleid);
            $iscurrentdisabled = !$title->enabled
                && $assignment
                && (int) $assignment->titleid === $titleid;
            if (!$title->enabled && !$iscurrentdisabled) {
                throw new \moodle_exception('errorinvalidtitle', 'local_usertitles');
            }
        }
        if ($titleid === 0 && !$assignment) {
            return null;
        }

        $transaction = $DB->start_delegated_transaction();
        $eventobjectid = $assignment ? (int) $assignment->id : null;

        if ($assignment) {
            self::clear_user_sync($userid);
        }

        if ($titleid === 0) {
            if ($assignment) {
                $DB->delete_records('local_usertitles_assignment', ['id' => $assignment->id]);
            }
            $transaction->allow_commit();

            if ($triggerevent) {
                self::trigger_user_title_event($userid, 0, $eventobjectid);
            }
            return null;
        }

        $now = time();
        if ($assignment) {
            $assignment->titleid = $titleid;
            $assignment->syncedvalue = null;
            $assignment->timemodified = $now;
            $DB->update_record('local_usertitles_assignment', $assignment);
        } else {
            $assignment = (object) [
                'userid' => $userid,
                'titleid' => $titleid,
                'syncedvalue' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $assignment->id = $DB->insert_record('local_usertitles_assignment', $assignment);
            $eventobjectid = (int) $assignment->id;
        }

        $transaction->allow_commit();

        if ($triggerevent) {
            self::trigger_user_title_event($userid, $titleid, $eventobjectid);
        }

        return self::get_user_assignment($userid);
    }

    /**
     * Deletes a user's assignment without requiring an active Moodle account.
     *
     * This method is intended for privacy and account-cleanup operations.
     *
     * @param int $userid User identifier.
     * @return void
     */
    public static function delete_user_assignment(int $userid): void {
        global $DB;

        $assignment = self::get_user_assignment($userid);
        if (!$assignment) {
            return;
        }

        self::clear_user_sync($userid);
        $DB->delete_records('local_usertitles_assignment', ['id' => $assignment->id]);
    }

    /**
     * Formats a user's name with the assigned title.
     *
     * This method is the public integration point for other plugins.
     *
     * @param \stdClass|int $user User record or user identifier.
     * @param bool $includetitle Whether to include the assigned title.
     * @return string
     */
    public static function format_name($user, bool $includetitle = true): string {
        $user = self::resolve_user($user);
        $basename = self::base_fullname($user);

        if (!$includetitle) {
            return $basename;
        }

        $title = self::get_user_title((int) $user->id);
        if (!$title) {
            return $basename;
        }

        return trim($title->abbreviation . ' ' . $basename);
    }

    /**
     * Synchronizes one assignment to Moodle's alternate name field.
     *
     * @param int $userid User identifier.
     * @param bool $force Ignore the plugin setting when true.
     * @return string One of the SYNC_* constants.
     */
    public static function sync_user(int $userid, bool $force = false): string {
        global $DB;

        if (!$force && !get_config('local_usertitles', 'syncalternatename')) {
            return self::SYNC_UNCHANGED;
        }

        $assignment = self::get_user_assignment($userid);
        $title = $assignment ? self::get_title((int) $assignment->titleid, IGNORE_MISSING) : false;
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$assignment || !$title || !$user) {
            return self::SYNC_MISSING;
        }

        $currentvalue = (string) $user->alternatename;
        $syncedvalue = (string) ($assignment->syncedvalue ?? '');
        $newvalue = (string) $title->abbreviation;

        if ($currentvalue === $newvalue && $syncedvalue === $newvalue) {
            return self::SYNC_UNCHANGED;
        }

        $canwrite = $currentvalue === '' || ($syncedvalue !== '' && $currentvalue === $syncedvalue);
        if (!$canwrite) {
            if ($syncedvalue !== '') {
                $DB->set_field('local_usertitles_assignment', 'syncedvalue', null, ['id' => $assignment->id]);
            }
            return self::SYNC_CONFLICT;
        }

        $user->alternatename = $newvalue;
        self::update_moodle_user($user);
        $DB->set_field('local_usertitles_assignment', 'syncedvalue', $newvalue, ['id' => $assignment->id]);
        return self::SYNC_UPDATED;
    }

    /**
     * Clears one synchronized alternate name value.
     *
     * @param int $userid User identifier.
     * @return bool True if the Moodle user record was changed.
     */
    public static function clear_user_sync(int $userid): bool {
        global $DB;

        $assignment = self::get_user_assignment($userid);
        if (!$assignment || empty($assignment->syncedvalue)) {
            return false;
        }

        $changed = false;
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if ($user && (string) $user->alternatename === (string) $assignment->syncedvalue) {
            $user->alternatename = '';
            self::update_moodle_user($user);
            $changed = true;
        }

        $DB->set_field('local_usertitles_assignment', 'syncedvalue', null, ['id' => $assignment->id]);
        return $changed;
    }

    /**
     * Synchronizes all assignments.
     *
     * @return array Counts keyed by SYNC_* status.
     */
    public static function sync_all(): array {
        global $DB;

        $counts = self::empty_sync_counts();
        $recordset = $DB->get_recordset('local_usertitles_assignment', null, 'id ASC', 'userid');
        foreach ($recordset as $assignment) {
            $status = self::sync_user((int) $assignment->userid, true);
            $counts[$status]++;
        }
        $recordset->close();

        return $counts;
    }

    /**
     * Clears all values synchronized by the plugin.
     *
     * @return int Number of Moodle user records changed.
     */
    public static function clear_all_sync(): int {
        global $DB;

        $changed = 0;
        $recordset = $DB->get_recordset_select(
            'local_usertitles_assignment',
            'syncedvalue IS NOT NULL',
            [],
            'id ASC',
            'userid'
        );
        foreach ($recordset as $assignment) {
            if (self::clear_user_sync((int) $assignment->userid)) {
                $changed++;
            }
        }
        $recordset->close();

        return $changed;
    }

    /**
     * Prepares and validates a title database record.
     *
     * @param \stdClass $data Input data.
     * @return \stdClass
     */
    private static function prepare_title_record(\stdClass $data): \stdClass {
        global $DB;

        $name = trim(clean_param($data->name ?? '', PARAM_TEXT));
        $abbreviation = trim(clean_param($data->abbreviation ?? '', PARAM_TEXT));
        $id = isset($data->id) ? (int) $data->id : 0;

        if ($name === '' || $abbreviation === '') {
            throw new \invalid_parameter_exception('Title name and abbreviation are required.');
        }

        $params = ['abbreviation' => $abbreviation, 'id' => $id];
        $exists = $DB->record_exists_select(
            'local_usertitles_title',
            'abbreviation = :abbreviation AND id <> :id',
            $params
        );
        if ($exists) {
            throw new \moodle_exception('errorabbreviationexists', 'local_usertitles');
        }

        return (object) [
            'name' => $name,
            'abbreviation' => $abbreviation,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'sortorder' => max(0, (int) ($data->sortorder ?? 10)),
        ];
    }

    /**
     * Resolves a complete Moodle user record.
     *
     * @param \stdClass|int $user User record or user identifier.
     * @return \stdClass
     */
    private static function resolve_user($user): \stdClass {
        global $DB;

        if (is_int($user) || (is_string($user) && ctype_digit($user))) {
            return $DB->get_record('user', ['id' => (int) $user, 'deleted' => 0], '*', MUST_EXIST);
        }

        if (!is_object($user) || empty($user->id)) {
            throw new \invalid_parameter_exception('A valid user record or user id is required.');
        }

        if (!property_exists($user, 'firstname') || !property_exists($user, 'lastname')) {
            return $DB->get_record('user', ['id' => (int) $user->id, 'deleted' => 0], '*', MUST_EXIST);
        }

        return $user;
    }

    /**
     * Returns a full name without the plugin-managed alternate name value.
     *
     * @param \stdClass $user Complete user record.
     * @return string
     */
    private static function base_fullname(\stdClass $user): string {
        $baseuser = clone $user;
        $baseuser->alternatename = '';
        $name = trim(fullname($baseuser));

        if ($name === '') {
            $name = trim(($baseuser->firstname ?? '') . ' ' . ($baseuser->lastname ?? ''));
        }
        return $name;
    }

    /**
     * Returns an empty synchronization result.
     *
     * @return array
     */
    private static function empty_sync_counts(): array {
        return [
            self::SYNC_UPDATED => 0,
            self::SYNC_UNCHANGED => 0,
            self::SYNC_CONFLICT => 0,
            self::SYNC_MISSING => 0,
        ];
    }

    /**
     * Updates a Moodle user through the public user API.
     *
     * @param \stdClass $user Complete user record.
     * @return void
     */
    private static function update_moodle_user(\stdClass $user): void {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        \user_update_user($user, false, true);
    }

    /**
     * Triggers the user assignment audit event.
     *
     * @param int $userid Related user identifier.
     * @param int $titleid New title identifier, or zero.
     * @param int|null $objectid Assignment identifier.
     * @return void
     */
    private static function trigger_user_title_event(int $userid, int $titleid, ?int $objectid): void {
        $data = [
            'context' => context_user::instance($userid),
            'relateduserid' => $userid,
            'other' => ['titleid' => $titleid],
        ];
        if ($objectid !== null) {
            $data['objectid'] = $objectid;
        }
        user_title_updated::create($data)->trigger();
    }
}
