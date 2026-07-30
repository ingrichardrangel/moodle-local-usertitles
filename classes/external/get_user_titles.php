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

namespace local_usertitles\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Returns title abbreviations for visual display.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_titles extends external_api {
    /**
     * Describes the input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Moodle user id')
            ),
        ]);
    }

    /**
     * Returns assigned title abbreviations.
     *
     * Titles are intended as public display metadata. Only active,
     * authenticated Moodle sessions can call this AJAX-only service.
     *
     * @param array $userids Moodle user identifiers.
     * @return array
     */
    public static function execute(array $userids): array {
        global $DB;

        ['userids' => $userids] = self::validate_parameters(
            self::execute_parameters(),
            ['userids' => $userids]
        );

        require_login();
        self::validate_context(\context_system::instance());

        $userids = array_values(array_unique(array_map('intval', $userids)));
        if (!$userids) {
            return [];
        }
        if (count($userids) > 200) {
            throw new \invalid_parameter_exception('A maximum of 200 user ids may be requested.');
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
        $sql = "SELECT a.userid, t.abbreviation,
                       u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {local_usertitles_assignment} a
                  JOIN {local_usertitles_title} t ON t.id = a.titleid
                  JOIN {user} u ON u.id = a.userid
                 WHERE a.userid {$insql}
                   AND u.deleted = 0";

        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $result[] = [
                'userid' => (int) $record->userid,
                'abbreviation' => (string) $record->abbreviation,
                'fullname' => fullname($record),
            ];
        }
        return $result;
    }

    /**
     * Describes the returned data.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'Moodle user id'),
                'abbreviation' => new external_value(PARAM_TEXT, 'Assigned title abbreviation'),
                'fullname' => new external_value(PARAM_TEXT, 'Moodle formatted full name'),
            ])
        );
    }
}
