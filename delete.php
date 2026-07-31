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

/**
 * Deletes a title after confirmation.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_usertitles\manager;

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_usertitles_manage');
require_capability('local/usertitles:managetitles', context_system::instance());

$title = manager::get_title($id);
$counts = manager::get_assignment_counts();
$url = new moodle_url('/local/usertitles/delete.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_title(get_string('deletetitle', 'local_usertitles'));
$PAGE->set_heading(get_string('deletetitle', 'local_usertitles'));

if ($confirm) {
    require_sesskey();
    manager::delete_title($id);
    redirect(
        new moodle_url('/local/usertitles/index.php'),
        get_string('titledeleted', 'local_usertitles'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$message = get_string('confirmdelete', 'local_usertitles', (object) [
    'name' => $title->name,
    'abbreviation' => $title->abbreviation,
    'count' => (int) ($counts[$title->id] ?? 0),
]);
$yesurl = new moodle_url('/local/usertitles/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletetitle', 'local_usertitles'));
echo $OUTPUT->confirm($message, $yesurl, new moodle_url('/local/usertitles/index.php'));
echo $OUTPUT->footer();
