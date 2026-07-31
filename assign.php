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
 * Assigns a title to a user.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_usertitles\form\assignment_form;
use local_usertitles\manager;

$userid = optional_param('userid', $USER->id, PARAM_INT);

require_login();
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$usercontext = context_user::instance($user->id);

if (!local_usertitles_can_edit_user_title($user)) {
    require_capability('local/usertitles:assignusertitles', $usercontext);
}

$url = new moodle_url('/local/usertitles/assign.php', ['userid' => $user->id]);
$pagetitle = get_string('usertitlefor', 'local_usertitles', fullname($user));

$PAGE->set_context($usercontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$assignment = manager::get_user_assignment((int) $user->id);
$currenttitleid = $assignment ? (int) $assignment->titleid : 0;
$form = new assignment_form($url, [
    'user' => $user,
    'currenttitleid' => $currenttitleid,
]);
$form->set_data([
    'userid' => $user->id,
    'titleid' => $currenttitleid,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/user/profile.php', ['id' => $user->id]));
}

if ($data = $form->get_data()) {
    manager::set_user_title((int) $user->id, (int) $data->titleid);
    $message = $data->titleid
        ? get_string('assignmentsaved', 'local_usertitles')
        : get_string('assignmentremoved', 'local_usertitles');
    redirect(
        new moodle_url('/user/profile.php', ['id' => $user->id]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
