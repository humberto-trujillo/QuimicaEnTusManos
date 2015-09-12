<?php
$curdir = getcwd();
chdir("{$_SERVER["DOCUMENT_ROOT"]}/moodle");
require_once("config.php");
chdir($curdir);
require_login();

$myusername = $USER->username; 
$myid = $USER->id;
$myname = (!empty($USER->lastname)) ? "{$USER->firstname} {$USER->lastname}" : $USER->firstname;
$roleid = $DB->get_field('role_assignments', 'roleid', array('userid' => $USER->id));

$isteacher = $isstudent = false;

if($roleid == 3)
    $isteacher = true;
if($roleid == 5)
    $isstudent = true;

require_once("chem-config.php");

if (isset($_SERVER['SCRIPT_FILENAME']) && 'session.php' == basename($_SERVER['SCRIPT_FILENAME']))
{
    require_once("404.php");
    die();
}

?>