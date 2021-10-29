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
 * ACE block.
 *
 * @package     block_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ace/locallib.php');

/**
 * ACE block class.
 *
 * @package     block_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ace extends block_base {

    /**
     * Initialise class variables.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ace');
    }

    /**
     * Defines if the block supports multiple instances on a single page.
     * True results in per instance configuration.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $USER, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $type = $this->config->graphtype ?? 'student';

        switch ($type) {
            case 'student':
                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                }

                $graph = local_ace_student_graph($USER->id, 0, false);
                if ($graph === '') {
                    // Display graph image when there are no analytics.
                    $url = new moodle_url('/local/ace/user.php', array('id' => $USER->id));
                    $title = get_string('viewyourdashboard', 'block_ace');
                    $attributes = array(
                        'src' => $OUTPUT->image_url('graph', 'block_ace'),
                        'alt' => $title,
                        'class' => 'graphimage',
                    );
                    $text = html_writer::link($url, html_writer::empty_tag('img', $attributes));
                } else {
                    $text = html_writer::div($graph, 'usergraph');
                }
                $url = new moodle_url('/local/ace/user.php', array('id' => $USER->id));
                $title = get_string('viewyourdashboard', 'block_ace');
                $text .= html_writer::link($url, $title, array('class' => 'textlink'));
                break;
            case 'course':
                if ($this->page->course->id == SITEID) {
                    return $this->content;
                }
                $coursecontext = context_course::instance($this->page->course->id);
                if (!has_capability('local/ace:view', $coursecontext)) {
                    return $this->content;
                }

                $graph = local_ace_course_graph($this->page->course->id);
                $text = html_writer::div($graph, 'teachergraph');
                break;
            case 'studentwithtabs':
                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                }

                $courseid = optional_param('course', 0, PARAM_INT);
                $text = local_ace_student_full_graph($USER->id, $courseid);
                break;
            case 'teachercourse':
                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                }
                $text = local_ace_teacher_course_graph($USER->id);
                break;
            default:
                $text = '';
                break;
        }

        $this->content->text = $text;

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {
        $this->title = get_string('pluginname', 'block_ace');
    }
}
