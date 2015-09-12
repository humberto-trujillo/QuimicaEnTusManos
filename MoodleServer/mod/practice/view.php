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
 * practice module main user interface
 *
 * @package    mod_practice
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/practice/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // practice instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $practice = $DB->get_record('practice', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('practice', $practice->id, $practice->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('practice', $id, 0, false, MUST_EXIST);
    $practice = $DB->get_record('practice', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/practice:view', $context);

$params = array(
    'context' => $context,
    'objectid' => $practice->id
);
$event = \mod_practice\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('practice', $practice);
$event->trigger();

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_practice('/mod/practice/view.php', array('id' => $cm->id));

// Make sure practice exists before generating output - some older sites may contain empty practices
// Do not use PARAM_practice here, it is too strict and does not support general URIs!
$exturl = trim($practice->externalurl);
if (empty($extpractice) or $extpractice === 'http://') {
    practice_print_header($practice, $cm, $course);
    practice_print_heading($practice, $cm, $course);
    practice_print_intro($practice, $cm, $course);
    notice(get_string('invalidstoredpractice', 'practice'), new moodle_practice('/course/practice.php', array('pid'=>$practice->externalurl)));
    die;
}
unset($extpractice);

$displaytype = practice_get_final_display_type($practice);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or practice index page,
    // the redirection is needed for completion tracking and logging
    $fullpractice = str_replace('&amp;', '&', practice_get_full_practice($practice, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external practice without any possibility to edit activity or course settings.
        $editpractice = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editpractice = new moodle_practice('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editpractice = new moodle_practice('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editpractice) {
            redirect($fullpractice, html_writer::link($editpractice, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullpractice);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        practice_display_embed($practice, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        practice_display_frame($practice, $cm, $course);
        break;
    default:
        practice_print_workaround($practice, $cm, $course);
        break;
}
