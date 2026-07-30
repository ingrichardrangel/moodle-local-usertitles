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
 * Title catalogue management page.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core\output\notification;
use local_usertitles\manager;

admin_externalpage_setup('local_usertitles_manage');
require_capability('local/usertitles:managetitles', context_system::instance());

$PAGE->set_url(new moodle_url('/local/usertitles/index.php'));
$PAGE->set_title(get_string('managetitles', 'local_usertitles'));
$PAGE->set_heading(get_string('manageheading', 'local_usertitles'));

$titles = manager::get_titles(true);
$counts = manager::get_assignment_counts();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageheading', 'local_usertitles'));

$topactions = [];
$topactions[] = $OUTPUT->single_button(
    new moodle_url('/local/usertitles/edit.php'),
    get_string('addtitle', 'local_usertitles'),
    'get'
);
$topactions[] = $OUTPUT->single_button(
    new moodle_url('/admin/settings.php', ['section' => 'local_usertitles_settings']),
    get_string('viewsettings', 'local_usertitles'),
    'get'
);
echo html_writer::div(implode('', $topactions), 'd-flex flex-wrap gap-2 mb-3');

if (!$titles) {
    echo $OUTPUT->notification(get_string('notitles', 'local_usertitles'), notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('name', 'local_usertitles'),
    get_string('titleabbreviation', 'local_usertitles'),
    get_string('status', 'local_usertitles'),
    get_string('sortorder', 'local_usertitles'),
    get_string('assignedusers', 'local_usertitles'),
    get_string('actions', 'local_usertitles'),
];

foreach ($titles as $title) {
    $statusclass = $title->enabled ? 'badge bg-success' : 'badge bg-secondary';
    $statustext = $title->enabled
        ? get_string('enabled', 'local_usertitles')
        : get_string('disabled', 'local_usertitles');

    $editurl = new moodle_url('/local/usertitles/edit.php', ['id' => $title->id]);
    $deleteurl = new moodle_url('/local/usertitles/delete.php', ['id' => $title->id]);
    $actions = $OUTPUT->action_icon(
        $editurl,
        new pix_icon('t/edit', get_string('edittitle', 'local_usertitles'))
    );
    $actions .= $OUTPUT->action_icon(
        $deleteurl,
        new pix_icon('t/delete', get_string('deletetitle', 'local_usertitles'))
    );

    $table->data[] = [
        format_string($title->name),
        s($title->abbreviation),
        html_writer::span($statustext, $statusclass),
        (int) $title->sortorder,
        (int) ($counts[$title->id] ?? 0),
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
