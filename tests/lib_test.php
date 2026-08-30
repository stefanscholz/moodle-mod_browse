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

namespace mod_browse;

use mod_browse\local\manager;
use PHPUnit\Framework\Attributes\CoversNothing;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/browse/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Tests for the mod_browse library functions.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class lib_test extends \advanced_testcase {

    /**
     * Deleting an instance removes its steps and progress.
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $browse = $generator->create_module('browse', ['course' => $course->id]);
        $other = $generator->create_module('browse', ['course' => $course->id]);

        $manager = manager::from_cmid($browse->cmid);
        $step = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $manager->complete_step($step, (int) $student->id);

        $othermanager = manager::from_cmid($other->cmid);
        $otherstep = $othermanager->add_step((object) ['title' => 'Keep', 'type' => manager::STEP_MANUAL]);
        $othermanager->complete_step($otherstep, (int) $student->id);

        $cmactions = new \core_courseformat\local\cmactions($course);
        $cmactions->delete($browse->cmid);
        $this->runAdhocTasks();

        $this->assertFalse($DB->record_exists('browse', ['id' => $browse->id]));
        $this->assertFalse($DB->record_exists('browse_steps', ['browseid' => $browse->id]));
        $this->assertFalse($DB->record_exists('browse_progress', ['stepid' => $step->id]));

        // The other instance is untouched.
        $this->assertTrue($DB->record_exists('browse', ['id' => $other->id]));
        $this->assertTrue($DB->record_exists('browse_progress', ['stepid' => $otherstep->id]));
    }

    /**
     * Course reset deletes all step progress in the course, keeping the steps.
     */
    public function test_reset_userdata(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $otherstudent = $generator->create_and_enrol($othercourse, 'student');

        $browse = $generator->create_module('browse', ['course' => $course->id]);
        $otherbrowse = $generator->create_module('browse', ['course' => $othercourse->id]);

        $manager = manager::from_cmid($browse->cmid);
        $step = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $manager->complete_step($step, (int) $student->id);

        $othermanager = manager::from_cmid($otherbrowse->cmid);
        $otherstep = $othermanager->add_step((object) ['title' => 'Other', 'type' => manager::STEP_MANUAL]);
        $othermanager->complete_step($otherstep, (int) $otherstudent->id);

        $status = browse_reset_userdata((object) [
            'courseid' => $course->id,
            'reset_browse_progress' => 1,
        ]);

        $this->assertFalse(in_array(true, array_column($status, 'error')));
        $this->assertFalse($DB->record_exists('browse_progress', ['stepid' => $step->id]));
        $this->assertTrue($DB->record_exists('browse_steps', ['id' => $step->id]));

        // The other course is untouched.
        $this->assertTrue($DB->record_exists('browse_progress', ['stepid' => $otherstep->id]));
    }

    /**
     * The module reports its features, most importantly completion rules and purpose.
     */
    public function test_browse_supports(): void {
        $this->assertTrue(browse_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(browse_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(browse_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertFalse(browse_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertEquals(MOD_PURPOSE_CONTENT, browse_supports(FEATURE_MOD_PURPOSE));
    }
}
