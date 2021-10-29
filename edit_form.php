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
 * Form for editing ACE block instances
 *
 * @package     block_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ace_edit_form extends block_edit_form {
    /**
     * Adds the specific settings from this block to the general block editing form.
     *
     * @param object $mform
     * @throws coding_exception
     */
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // These types relate to function names in local_ace locallib.php.
        // E.g. student maps to local_ace_student_graph().
        $types = [
            'student' => get_string('student', 'block_ace'),
            'course' => get_string('course', 'block_ace'),
            'studentwithtabs' => get_string('studentwithtabs', 'block_ace'),
            'teachercourse' => get_string('teachercourse', 'block_ace'),
        ];

        $mform->addElement('select', 'config_graphtype', get_string('graphtype', 'block_ace'), $types);
        $mform->setDefault('config_graphtype', 'student');
    }
}
