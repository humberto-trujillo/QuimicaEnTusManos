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
 * Unit tests for some mod practice lib stuff.
 *
 * @package    mod_practice
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * mod_practice tests
 *
 * @package    mod_practice
 * @category   phpunit
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_practice_lib_testcase extends basic_testcase {

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/practice/locallib.php');
    }

    /**
     * Tests the practice_appears_valid_practice function
     * @return void
     */
    public function test_practice_appears_valid_practice() {
        $this->assertTrue(practice_appears_valid_practice('http://example'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com'));
        $this->assertTrue(practice_appears_valid_practice('http://www.exa-mple2.com'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com/~nobody/index.html'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com#hmm'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com/#hmm'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com/žlutý koníček/lala.txt'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com/žlutý koníček/lala.txt#hmmmm'));
        $this->assertTrue(practice_appears_valid_practice('http://www.example.com/index.php?xx=yy&zz=aa'));
        $this->assertTrue(practice_appears_valid_practice('https://user:password@www.example.com/žlutý koníček/lala.txt'));
        $this->assertTrue(practice_appears_valid_practice('ftp://user:password@www.example.com/žlutý koníček/lala.txt'));

        $this->assertFalse(practice_appears_valid_practice('http:example.com'));
        $this->assertFalse(practice_appears_valid_practice('http:/example.com'));
        $this->assertFalse(practice_appears_valid_practice('http://'));
        $this->assertFalse(practice_appears_valid_practice('http://www.exa mple.com'));
        $this->assertFalse(practice_appears_valid_practice('http://www.examplé.com'));
        $this->assertFalse(practice_appears_valid_practice('http://@www.example.com'));
        $this->assertFalse(practice_appears_valid_practice('http://user:@www.example.com'));

        $this->assertTrue(practice_appears_valid_practice('lalala://@:@/'));
    }
}