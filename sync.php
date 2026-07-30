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
 * Bulk alternate name synchronization actions.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_usertitles\manager;

$action = required_param('action', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_usertitles_manage');
require_capability('local/usertitles:managetitles', context_system::instance());
require_sesskey();

$returnurl = new moodle_url('/local/usertitles/index.php');

if ($action === 'sync') {
    redirect($returnurl, get_string('syncdisablednotice', 'local_usertitles'));
}

if ($action !== 'clear') {
    throw new moodle_exception('invalidaction');
}

if (!$confirm) {
    $yesurl = new moodle_url('/local/usertitles/sync.php', [
        'action' => 'clear',
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    $PAGE->set_url(new moodle_url('/local/usertitles/sync.php', ['action' => 'clear']));
    $PAGE->set_title(get_string('clearallsync', 'local_usertitles'));
    $PAGE->set_heading(get_string('clearallsync', 'local_usertitles'));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('clearallsync', 'local_usertitles'));
    echo $OUTPUT->confirm(
        get_string('clearallsyncconfirm', 'local_usertitles'),
        $yesurl,
        $returnurl
    );
    echo $OUTPUT->footer();
    exit;
}

$changed = manager::clear_all_sync();
redirect(
    $returnurl,
    get_string('clearedcount', 'local_usertitles', $changed),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
