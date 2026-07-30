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
 * Creates or edits a title.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_usertitles\form\title_form;
use local_usertitles\manager;

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_usertitles_manage');
require_capability('local/usertitles:managetitles', context_system::instance());

$title = $id ? manager::get_title($id) : null;
$pagetitle = $title
    ? get_string('edittitle', 'local_usertitles')
    : get_string('addtitle', 'local_usertitles');
$url = new moodle_url('/local/usertitles/edit.php', $id ? ['id' => $id] : []);

$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$form = new title_form($url);
if ($title) {
    $form->set_data($title);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/usertitles/index.php'));
}

if ($data = $form->get_data()) {
    if (empty($data->id)) {
        manager::create_title($data);
    } else {
        manager::update_title($data);
    }
    redirect(
        new moodle_url('/local/usertitles/index.php'),
        get_string('titlesaved', 'local_usertitles'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
