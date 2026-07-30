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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing a title.
 *
 * @package   local_usertitles
 * @copyright 2026 Richard Rangel
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class title_form extends \moodleform {
    /**
     * Defines the form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'name',
            get_string('titlename', 'local_usertitles'),
            ['maxlength' => 100, 'size' => 40]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'titlename', 'local_usertitles');

        $mform->addElement(
            'text',
            'abbreviation',
            get_string('titleabbreviation', 'local_usertitles'),
            ['maxlength' => 50, 'size' => 20]
        );
        $mform->setType('abbreviation', PARAM_TEXT);
        $mform->addRule('abbreviation', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('abbreviation', 'titleabbreviation', 'local_usertitles');

        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('titleenabled', 'local_usertitles')
        );
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'titleenabled', 'local_usertitles');

        $mform->addElement(
            'text',
            'sortorder',
            get_string('sortorder', 'local_usertitles'),
            ['maxlength' => 10, 'size' => 10]
        );
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 10);

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
        global $DB;

        $errors = parent::validation($data, $files);
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $abbreviation = trim((string) ($data['abbreviation'] ?? ''));

        if ($name === '') {
            $errors['name'] = get_string('errornamerequired', 'local_usertitles');
        }
        if ($abbreviation === '') {
            $errors['abbreviation'] = get_string('required');
        } else if ($DB->record_exists_select(
            'local_usertitles_title',
            'abbreviation = :abbreviation AND id <> :id',
            ['abbreviation' => $abbreviation, 'id' => $id]
        )) {
            $errors['abbreviation'] = get_string('errorabbreviationexists', 'local_usertitles');
        }
        if ((int) ($data['sortorder'] ?? 0) < 0) {
            $errors['sortorder'] = get_string('errorsortorder', 'local_usertitles');
        }

        return $errors;
    }
}

