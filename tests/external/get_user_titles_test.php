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

namespace local_usertitles\external;

use local_usertitles\manager;

/**
 * Tests for the visual title external service.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_usertitles\external\get_user_titles
 */
final class get_user_titles_test extends \advanced_testcase {
    /**
     * Tests that the service returns only assigned titles.
     *
     * @return void
     */
    public function test_execute_returns_assigned_titles(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $titleduser = $this->getDataGenerator()->create_user([
            'firstname' => 'Richard',
            'lastname' => 'Rangel',
        ]);
        $plainuser = $this->getDataGenerator()->create_user();
        $title = manager::create_title((object) [
            'name' => 'Visual Professor',
            'abbreviation' => 'Visual Prof.',
            'enabled' => 1,
            'sortorder' => 10,
        ]);
        manager::set_user_title((int) $titleduser->id, (int) $title->id);

        $result = get_user_titles::execute([
            (int) $titleduser->id,
            (int) $plainuser->id,
        ]);

        $this->assertSame([
            [
                'userid' => (int) $titleduser->id,
                'abbreviation' => 'Visual Prof.',
                'fullname' => fullname($titleduser),
            ],
        ], $result);
    }
}
