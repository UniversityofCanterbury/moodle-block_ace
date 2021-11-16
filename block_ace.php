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
     * Has site level config.
     *
     * @return bool
     */
    public function has_config() {
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
        $text = '';

        switch ($type) {
            case 'student':
                $userid = $this->get_userid_from_contextid();

                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                } else if ($userid != $USER->id && !has_capability('local/ace:view', $this->page->context)) {
                    return $this->content;
                }

                $graph = local_ace_student_graph($userid, 0, false);
                $url = new moodle_url(get_config('block_ace', 'studentdashboardurl'));
                if ($graph === '') {
                    // Display static image when there are no analytics.
                    $title = get_string('viewyourdashboard', 'block_ace');
                    $attributes = array(
                        'src' => $OUTPUT->image_url('graph', 'block_ace'),
                        'alt' => $title,
                        'class' => 'graphimage',
                    );
                    $text = html_writer::link($url, html_writer::empty_tag('img', $attributes));
                } else {
                    $hiddenpref = get_user_preferences('block_ace_student_hidden_graph', false);
                    if ($hiddenpref) {
                        // Show static image when hidden.
                        $title = get_string('viewyourdashboard', 'block_ace');
                        $attributes = array(
                            'src' => $OUTPUT->image_url('graph', 'block_ace'),
                            'alt' => $title,
                            'class' => 'graphimage',
                        );
                        $text = html_writer::empty_tag('img', $attributes);
                        $switchtitle = get_string('switchtolivegraph', 'block_ace');
                    } else {
                        // Show live graph.
                        $text = html_writer::div($graph, 'usergraph');
                        $switchtitle = get_string('switchtostaticimage', 'block_ace');
                    }

                    $text .= html_writer::link('#', $switchtitle, ['class' => 'textlink', 'id' => 'block_ace-switch-graph']);
                    // Convert boolean to string to pass into script.
                    $hiddenpref = !$hiddenpref ? 'true' : 'false';
                    user_preference_allow_ajax_update('block_ace_student_hidden_graph', PARAM_BOOL);
                    $script = <<<EOF
                        document.querySelector('#block_ace-switch-graph').addEventListener('click', () => {
                            M.util.set_user_preference("block_ace_student_hidden_graph", {$hiddenpref});
                        });
EOF;
                    $text .= html_writer::script($script);
                }

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
                $userid = $this->get_userid_from_contextid();

                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                } else if ($userid != $USER->id && !has_capability('local/ace:view', $this->page->context)) {
                    return $this->content;
                }

                $courseid = optional_param('course', 0, PARAM_INT);
                $text = local_ace_student_full_graph($userid, $courseid);
                break;
            case 'teachercourse':
                if (!has_capability('local/ace:viewown', $this->page->context)) {
                    return $this->content;
                }
                $text = local_ace_teacher_course_graph($USER->id);
                break;
            case 'activity':
                if (!has_capability('local/ace:view', $this->page->context)) {
                    return $this->content;
                }
                $text = local_ace_course_module_engagement_graph($this->page->context->instanceid);
                break;
            default:
                break;
        }

        $this->content->text = $text;

        return $this->content;
    }

    /**
     * Returns the user ID from the contextid url parameter.
     * Defaults to current logged-in user if contextid is not available.
     *
     * @return int User ID
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_userid_from_contextid(): int {
        global $USER, $DB;

        $userid = $USER->id;

        $contextid = optional_param('contextid', 0, PARAM_INT);
        if ($contextid != 0) {
            $context = context::instance_by_id($contextid, IGNORE_MISSING);
            if ($context != null && $context->contextlevel == CONTEXT_USER) {
                $userid = $DB->get_record('user', array('id' => $context->instanceid))->id;
            }
        }

        return $userid;
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
