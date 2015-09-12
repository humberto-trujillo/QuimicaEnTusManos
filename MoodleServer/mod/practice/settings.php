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
 * practice module admin settings and defaults
 *
 * @package    mod_practice
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('practice/framesize',
        get_string('framesize', 'practice'), get_string('configframesize', 'practice'), 130, PARAM_INT));
    $settings->add(new admin_setting_configpasswordunmask('practice/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'practice'), ''));
    $settings->add(new admin_setting_configcheckbox('practice/rolesinparams',
        get_string('rolesinparams', 'practice'), get_string('configrolesinparams', 'practice'), false));
    $settings->add(new admin_setting_configmultiselect('practice/displayoptions',
        get_string('displayoptions', 'practice'), get_string('configdisplayoptions', 'practice'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('practicemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('practice/printintro',
        get_string('printintro', 'practice'), get_string('printintroexplain', 'practice'), 1));
    $settings->add(new admin_setting_configselect('practice/display',
        get_string('displayselect', 'practice'), get_string('displayselectexplain', 'practice'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    $settings->add(new admin_setting_configtext('practice/popupwidth',
        get_string('popupwidth', 'practice'), get_string('popupwidthexplain', 'practice'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('practice/popupheight',
        get_string('popupheight', 'practice'), get_string('popupheightexplain', 'practice'), 450, PARAM_INT, 7));
}
