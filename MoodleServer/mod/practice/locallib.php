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
 * Private practice module utility functions
 *
 * @package    mod_practice
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/practice/lib.php");

/**
 * This methods does weak practice validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $practice
 * @return bool true is seems valid, false if definitely not valid practice
 */
function practice_appears_valid_practice($practice) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $practice)) {
        // note: this is not exact validation, we look for severely malformed practices only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $practice);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $practice);
    }
}

/**
 * Fix common practice problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $practice
 * @return string
 */
function practice_fix_submitted_practice($practice) {
    // note: empty practices are prevented in form validation
    $practice = trim($practice);

    // remove encoded entities - we want the raw URI here
    $practice = html_entity_decode($practice, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $practice) and !preg_match('|^/|', $practice)) {
        // invalid URI, try to fix it by making it normal practice,
        // please note relative practices are not allowed, /xx/yy links are ok
        $practice = 'http://'.$practice;
    }

    return $practice;
}

/**
 * Return full practice with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $practice
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string practice with & encoded as &amp;
 */
function practice_get_full_practice($practice, $cm, $course, $config=null) {

    $parameters = empty($practice->parameters) ? array() : unserialize($practice->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullpractice = html_entity_decode($practice->externalpractice, ENT_QUOTES, 'UTF-8');

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullpractice) or preg_match('|^/|', $fullpractice)) {
        // encode extra chars in practices - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullpractice = preg_replace_callback("/[^$allowed]/", 'practice_filter_callback', $fullpractice);
    } else {
        // encode special chars only
        $fullpractice = str_replace('"', '%22', $fullpractice);
        $fullpractice = str_replace('\'', '%27', $fullpractice);
        $fullpractice = str_replace(' ', '%20', $fullpractice);
        $fullpractice = str_replace('<', '%3C', $fullpractice);
        $fullpractice = str_replace('>', '%3E', $fullpractice);
    }

    // add variable practice parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('practice');
        }
        $paramvalues = practice_get_variable_values($practice, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawpracticeencode($parse).'='.rawpracticeencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullpractice, 'teamspeak://') === 0) {
                $fullpractice = $fullpractice.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullpractice, '?') === false) ? '?' : '&';
                $fullpractice = $fullpractice.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullpractice = str_replace('&', '&amp;', $fullpractice);

    return $fullpractice;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function practice_filter_callback($matches) {
    return rawpracticeencode($matches[0]);
}

/**
 * Print practice header.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @return void
 */
function practice_print_header($practice, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$practice->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($practice);
    echo $OUTPUT->header();
}

/**
 * Print practice heading.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function practice_print_heading($practice, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($practice->name), 2);
}

/**
 * Print practice introduction.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function practice_print_intro($practice, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($practice->displayoptions) ? array() : unserialize($practice->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($practice->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'practiceintro');
            echo format_module_intro('practice', $practice, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display practice frames.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function practice_display_frame($practice, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        practice_print_header($practice, $cm, $course);
        practice_print_heading($practice, $cm, $course);
        practice_print_intro($practice, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('practice');
        $context = context_module::instance($cm->id);
        $extepractice = practice_get_full_practice($practice, $cm, $course, $config);
        $navpractice = "$CFG->wwwroot/mod/practice/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($practice->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','practice'));
        $contentframetitle = s(format_string($practice->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navpractice" title="$modulename"/>
    <frame src="$extepractice" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print practice info and link.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function practice_print_workaround($practice, $cm, $course) {
    global $OUTPUT;

    practice_print_header($practice, $cm, $course);
    practice_print_heading($practice, $cm, $course, true);
    practice_print_intro($practice, $cm, $course, true);

    $fullpractice = practice_get_full_practice($practice, $cm, $course);

    $display = practice_get_final_display_type($practice);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullpractice = addslashes_js($fullpractice);
        $options = empty($practice->displayoptions) ? array() : unserialize($practice->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullpractice', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="practiceworkaround">';
    print_string('clicktoopen', 'practice', "<a href=\"$fullpractice\" $extra>$fullpractice</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded practice file.
 * @param object $practice
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function practice_display_embed($practice, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = resourcelib_guess_practice_mimetype($practice->externalpractice);
    $fullpractice  = practice_get_full_practice($practice, $cm, $course);
    $title    = $practice->name;

    $link = html_writer::tag('a', $fullpractice, array('href'=>str_replace('&amp;', '&', $fullpractice)));
    $clicktoopen = get_string('clicktoopen', 'practice', $link);
    $moodlepractice = new moodle_practice($fullpractice);

    $extension = resourcelib_get_extension($practice->externalpractice);

    $mediarenderer = $PAGE->get_renderer('core', 'media');
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullpractice, $title);

    } else if ($mediarenderer->can_embed_practice($moodlepractice, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediarenderer->embed_practice($moodlepractice, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullpractice, $title, $clicktoopen, $mimetype);
    }

    practice_print_header($practice, $cm, $course);
    practice_print_heading($practice, $cm, $course);

    echo $code;

    practice_print_intro($practice, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $practice
 * @return int display type constant
 */
function practice_get_final_display_type($practice) {
    global $CFG;

    if ($practice->display != RESOURCELIB_DISPLAY_AUTO) {
        return $practice->display;
    }

    // detect links to local moodle pages
    if (strpos($practice->externalpractice, $CFG->wwwroot) === 0) {
        if (strpos($practice->externalpractice, 'file.php') === false and strpos($practice->externalpractice, '.php') !== false ) {
            // most probably our moodle page with navigation
            return RESOURCELIB_DISPLAY_OPEN;
        }
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = resourcelib_guess_practice_mimetype($practice->externalpractice);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to practice
 * @param object $config practice module config options
 * @return array array describing opt groups
 */
function practice_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'practice'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'practice')] = array(
        'practiceinstance'     => 'id',
        'practicecmid'         => 'cmid',
        'practicename'         => get_string('name'),
        'practiceidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverpractice'       => get_string('serverpractice', 'practice'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone').' 1',
        'userphone2'      => get_string('phone2').' 2',
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userpractice'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to practice
 * @param object $practice module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function practice_get_variable_values($practice, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverpractice'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'practiceinstance'     => $practice->id,
        'practicecmid'         => $cm->id,
        'practicename'         => format_string($practice->name),
        'practiceidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userpractice']         = $USER->practice;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = practice_get_encrypted_parameter($practice, $config);
    }

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $practice
 * @param object $config
 * @return string
 */
function practice_get_encrypted_parameter($practice, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($practice, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general practice
 * @param $fullpractice
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function practice_guess_icon($fullpractice, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullpractice, '/') < 3 or substr($fullpractice, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullpractice, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
