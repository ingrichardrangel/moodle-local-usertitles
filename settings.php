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
 * Administration settings for the User titles plugin.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settingspage = new admin_settingpage(
        'local_usertitles_settings',
        get_string('settings', 'local_usertitles')
    );

    $settingspage->add(new admin_setting_configcheckbox(
        'local_usertitles/allowselfselection',
        get_string('allowselfselection', 'local_usertitles'),
        get_string('allowselfselection_help', 'local_usertitles'),
        0
    ));

    $settingspage->add(new admin_setting_configcheckbox(
        'local_usertitles/enablevisualtitles',
        get_string('enablevisualtitles', 'local_usertitles'),
        get_string('enablevisualtitles_help', 'local_usertitles'),
        1
    ));

    $ADMIN->add('accounts', $settingspage);
    $ADMIN->add('accounts', new admin_externalpage(
        'local_usertitles_manage',
        get_string('managetitles', 'local_usertitles'),
        new moodle_url('/local/usertitles/index.php'),
        'local/usertitles:managetitles'
    ));
}
