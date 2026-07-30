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

namespace local_usertitles\form;

use local_usertitles\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for assigning a title to a user.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_form extends \moodleform {
    /**
     * Defines the form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $user = $this->_customdata['user'];
        $currenttitleid = (int) ($this->_customdata['currenttitleid'] ?? 0);

        $mform->addElement('hidden', 'userid', $user->id);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('static', 'userdisplay', get_string('user'), fullname($user));

        $options = [0 => get_string('notitle', 'local_usertitles')];
        foreach (manager::get_titles(false) as $title) {
            $options[$title->id] = $title->name . ' (' . $title->abbreviation . ')';
        }

        if ($currenttitleid && !array_key_exists($currenttitleid, $options)) {
            $current = manager::get_title($currenttitleid, IGNORE_MISSING);
            if ($current) {
                $label = $current->name . ' (' . $current->abbreviation . ')';
                $options[$current->id] = get_string('disabledcurrent', 'local_usertitles', $label);
            }
        }

        $mform->addElement('select', 'titleid', get_string('profiletitle', 'local_usertitles'), $options);
        $mform->setType('titleid', PARAM_INT);
        $mform->setDefault('titleid', $currenttitleid);

        $this->add_action_buttons();
    }

    /**
     * Validates submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $titleid = (int) ($data['titleid'] ?? 0);
        $currenttitleid = (int) ($this->_customdata['currenttitleid'] ?? 0);

        if ($titleid !== 0) {
            $title = manager::get_title($titleid, IGNORE_MISSING);
            $iscurrentdisabled = $title && !$title->enabled && $titleid === $currenttitleid;
            if (!$title || (!$title->enabled && !$iscurrentdisabled)) {
                $errors['titleid'] = get_string('errorinvalidtitle', 'local_usertitles');
            }
        }

        return $errors;
    }
}

