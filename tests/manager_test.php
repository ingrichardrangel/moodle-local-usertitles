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

namespace local_usertitles;

/**
 * Tests for the title manager.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager_test extends \advanced_testcase {
    /**
     * Tests title creation, assignment, and name formatting.
     *
     * @return void
     */
    public function test_create_assign_and_format_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $title = manager::create_title((object) [
            'name' => 'Associate Professor',
            'abbreviation' => 'Assoc. Prof.',
            'enabled' => 1,
            'sortorder' => 20,
        ]);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Jane',
            'lastname' => 'Smith',
        ]);

        manager::set_user_title((int) $user->id, (int) $title->id);

        $this->assertSame('Assoc. Prof. Jane Smith', manager::format_name((int) $user->id));
        $this->assertSame('Jane Smith', manager::format_name((int) $user->id, false));
    }

    /**
     * Tests that title assignment never changes a blank alternate name.
     *
     * @return void
     */
    public function test_assignment_preserves_blank_alternate_name(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('syncalternatename', 1, 'local_usertitles');

        $title = manager::create_title((object) [
            'name' => 'Test Professor',
            'abbreviation' => 'Test Prof.',
            'enabled' => 1,
            'sortorder' => 30,
        ]);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Alex',
            'lastname' => 'Taylor',
            'alternatename' => '',
        ]);

        manager::set_user_title((int) $user->id, (int) $title->id);
        $this->assertSame('', $DB->get_field('user', 'alternatename', ['id' => $user->id]));

        manager::set_user_title((int) $user->id, 0);
        $this->assertSame('', $DB->get_field('user', 'alternatename', ['id' => $user->id]));
    }

    /**
     * Tests that title assignment preserves an independent alternate name.
     *
     * @return void
     */
    public function test_assignment_preserves_existing_alternate_name(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('syncalternatename', 1, 'local_usertitles');

        $title = manager::create_title((object) [
            'name' => 'Visiting Professor',
            'abbreviation' => 'Vis. Prof.',
            'enabled' => 1,
            'sortorder' => 40,
        ]);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Morgan',
            'lastname' => 'Lee',
            'alternatename' => 'Mo',
        ]);

        manager::set_user_title((int) $user->id, (int) $title->id);

        $this->assertSame('Mo', $DB->get_field('user', 'alternatename', ['id' => $user->id]));
        $assignment = manager::get_user_assignment((int) $user->id);
        $this->assertNull($assignment->syncedvalue);
    }

    /**
     * Tests that deleting a title removes its assignments.
     *
     * @return void
     */
    public function test_delete_title_removes_assignments(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $title = manager::create_title((object) [
            'name' => 'Guest Professor',
            'abbreviation' => 'Guest Prof.',
            'enabled' => 1,
            'sortorder' => 50,
        ]);
        $user = $this->getDataGenerator()->create_user();
        manager::set_user_title((int) $user->id, (int) $title->id);

        manager::delete_title((int) $title->id);

        $this->assertFalse($DB->record_exists('local_usertitles_title', ['id' => $title->id]));
        $this->assertFalse($DB->record_exists('local_usertitles_assignment', ['userid' => $user->id]));
    }
}
