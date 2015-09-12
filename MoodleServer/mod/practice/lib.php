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
 * Mandatory public API of practice module
 *
 * @package    mod_practice
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in practice module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function practice_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function practice_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function practice_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function practice_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function practice_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add practice instance.
 * @param object $data
 * @param object $mform
 * @return int new practice instance id
 */
function practice_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/practice/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalpractice = practice_fix_submitted_practice($data->externalpractice);

    $data->timemodified = time();
    $data->id = $DB->insert_record('practice', $data);

    return $data->id;
}

/**
 * Update practice instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function practice_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/practice/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalpractice = practice_fix_submitted_practice($data->externalpractice);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('practice', $data);

    return true;
}

/**
 * Delete practice instance.
 * @param int $id
 * @return bool true
 */
function practice_delete_instance($id) {
    global $DB;

    if (!$practice = $DB->get_record('practice', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('practice', array('id'=>$practice->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function practice_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/practice/locallib.php");

    if (!$practice = $DB->get_record('practice', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, externalpractice, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $practice->name;

    //note: there should be a way to differentiate links from normal resources
    $info->icon = practice_guess_icon($practice->externalpractice, 24);

    $display = practice_get_final_display_type($practice);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullpractice = "$CFG->wwwroot/mod/practice/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($practice->displayoptions) ? array() : unserialize($practice->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullpractice', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullpractice = "$CFG->wwwroot/mod/practice/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullpractice'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('practice', $practice, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function practice_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-practice-*'=>get_string('page-mod-practice-x', 'practice'));
    return $module_pagetype;
}

/**
 * Export practice resource contents
 *
 * @return array of file content
 */
function practice_export_contents($cm, $basepractice) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/practice/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $practicerecord = $DB->get_record('practice', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullpractice = str_replace('&amp;', '&', practice_get_full_practice($practicerecord, $cm, $course));
    $ispractice = clean_param($fullpractice, PARAM_practice);
    if (empty($ispractice)) {
        return null;
    }

    $practice = array();
    $practice['type'] = 'practice';
    $practice['filename']     = clean_param(format_string($practicerecord->name), PARAM_FILE);
    $practice['filepath']     = null;
    $practice['filesize']     = 0;
    $practice['filepractice']      = $fullpractice;
    $practice['timecreated']  = null;
    $practice['timemodified'] = $practicerecord->timemodified;
    $practice['sortorder']    = null;
    $practice['userid']       = null;
    $practice['author']       = null;
    $practice['license']      = null;
    $contents[] = $practice;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function practice_dndupload_register() {
    return array('types' => array(
                     array('identifier' => 'practice', 'message' => get_string('createpractice', 'practice'))
                 ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function practice_dndupload_handle($uploadinfo) {
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>'.$uploadinfo->displayname.'</p>';
    $data->introformat = FORMAT_HTML;
    $data->externalpractice = clean_param($uploadinfo->content, PARAM_practice);
    $data->timemodified = time();

    // Set the display options to the site defaults.
    $config = get_config('practice');
    $data->display = $config->display;
    $data->popupwidth = $config->popupwidth;
    $data->popupheight = $config->popupheight;
    $data->printintro = $config->printintro;

    return practice_add_instance($data, null);
}
