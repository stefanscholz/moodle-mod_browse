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

namespace mod_browse\completion;

use mod_browse\local\manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the mod_browse custom completion rule.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_completion::class)]
final class custom_completion_test extends \advanced_testcase {
    /**
     * Create a course with completion enabled, an activity with the steps rule, and an enrolled student.
     *
     * @return array [manager, student]
     */
    private function create_activity_with_student(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $browse = $generator->create_module('browse', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionsteps' => 1,
        ]);
        $student = $generator->create_and_enrol($course, 'student');
        return [manager::from_cmid($browse->cmid), $student];
    }

    /**
     * The rule is defined and described.
     */
    public function test_rule_definition(): void {
        $this->resetAfterTest();
        [$manager, $student] = $this->create_activity_with_student();

        $completion = new custom_completion($manager->get_cm(), (int) $student->id);
        $this->assertSame(['completionsteps'], custom_completion::get_defined_custom_rules());
        $this->assertArrayHasKey('completionsteps', $completion->get_custom_rule_descriptions());
        $this->assertSame(['completionview', 'completionsteps'], $completion->get_sort_order());
    }

    /**
     * The rule state follows the user's step progress.
     */
    public function test_get_state(): void {
        $this->resetAfterTest();
        [$manager, $student] = $this->create_activity_with_student();

        $one = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $two = $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);

        $completion = new custom_completion($manager->get_cm(), (int) $student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionsteps'));

        $manager->complete_step($one, (int) $student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionsteps'));

        $manager->complete_step($two, (int) $student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionsteps'));
    }

    /**
     * Completing the last step marks the whole activity complete, revoking reopens it.
     */
    public function test_activity_completion_updates_with_progress(): void {
        $this->resetAfterTest();
        [$manager, $student] = $this->create_activity_with_student();

        $step = $manager->add_step((object) ['title' => 'Only step', 'type' => manager::STEP_MANUAL]);

        $completioninfo = new \completion_info($manager->get_course());
        $data = $completioninfo->get_data($manager->get_cm(), false, (int) $student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $data->completionstate);

        $manager->complete_step($step, (int) $student->id);
        $data = $completioninfo->get_data($manager->get_cm(), false, (int) $student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $data->completionstate);

        $manager->revoke_step($step, (int) $student->id);
        $data = $completioninfo->get_data($manager->get_cm(), false, (int) $student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $data->completionstate);
    }

    /**
     * The custom rule is exposed in the course module info customdata.
     */
    public function test_coursemodule_info_exposes_rule(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity_with_student();

        $cm = $manager->get_cm();
        $this->assertEquals(['completionsteps' => 1], $cm->customdata['customcompletionrules']);
    }
}
