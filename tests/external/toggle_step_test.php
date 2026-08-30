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

namespace mod_browse\external;

use mod_browse\local\manager;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the toggle_step external function.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(toggle_step::class)]
final class toggle_step_test extends \externallib_advanced_testcase {
    /**
     * Create a course, an activity with one manual step, and an enrolled student.
     *
     * @param array $instancedata extra fields for the browse instance
     * @return array [manager, step, student]
     */
    private function setup_activity(array $instancedata = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $browse = $generator->create_module('browse', ['course' => $course->id] + $instancedata);
        $manager = manager::from_cmid($browse->cmid);
        $step = $manager->add_step((object) ['title' => 'Manual step', 'type' => manager::STEP_MANUAL]);
        $student = $generator->create_and_enrol($course, 'student');
        return [$manager, $step, $student];
    }

    /**
     * A student can tick a manual step and untick it again.
     */
    public function test_toggle(): void {
        $this->resetAfterTest();
        [$manager, $step, $student] = $this->setup_activity();
        $this->setUser($student);

        $result = toggle_step::execute($manager->get_cm()->id, $step->id, true);
        $result = \core_external\external_api::clean_returnvalue(toggle_step::execute_returns(), $result);
        $this->assertTrue($result['completed']);
        $this->assertNotEmpty($result['panelhtml']);
        $this->assertTrue($manager->is_step_completed($step->id, (int) $student->id));

        $result = toggle_step::execute($manager->get_cm()->id, $step->id, false);
        $result = \core_external\external_api::clean_returnvalue(toggle_step::execute_returns(), $result);
        $this->assertFalse($result['completed']);
        $this->assertFalse($manager->is_step_completed($step->id, (int) $student->id));
    }

    /**
     * Only manual steps can be toggled through the web service.
     */
    public function test_toggle_rejects_automatic_steps(): void {
        $this->resetAfterTest();
        [$manager, $step, $student] = $this->setup_activity();
        $link = $manager->add_step((object) [
            'title' => 'Link step', 'type' => manager::STEP_LINK, 'url' => 'https://example.com/',
        ]);
        $this->setUser($student);

        $this->expectException(\moodle_exception::class);
        toggle_step::execute($manager->get_cm()->id, $link->id, true);
    }

    /**
     * In sequential mode a locked step cannot be ticked.
     */
    public function test_toggle_respects_sequential_order(): void {
        $this->resetAfterTest();
        [$manager, $step, $student] = $this->setup_activity(['sequential' => 1]);
        $second = $manager->add_step((object) ['title' => 'Second', 'type' => manager::STEP_MANUAL]);
        $this->setUser($student);

        $this->expectException(\moodle_exception::class);
        toggle_step::execute($manager->get_cm()->id, $second->id, true);
    }

    /**
     * Users without the completesteps capability are rejected.
     */
    public function test_toggle_requires_capability(): void {
        $this->resetAfterTest();
        [$manager, $step] = $this->setup_activity();
        $teacher = $this->getDataGenerator()->create_and_enrol($manager->get_course(), 'teacher');
        $this->setUser($teacher);

        $this->expectException(\required_capability_exception::class);
        toggle_step::execute($manager->get_cm()->id, $step->id, true);
    }
}
