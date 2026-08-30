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

namespace mod_browse\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the step manager.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(manager::class)]
final class manager_test extends \advanced_testcase {
    /**
     * Create a course, a browse activity and its manager.
     *
     * @param array $instancedata extra fields for the browse instance
     * @return array [manager, browse record, course]
     */
    private function create_activity(array $instancedata = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $browse = $generator->create_module('browse', ['course' => $course->id] + $instancedata);
        return [manager::from_cmid($browse->cmid), $browse, $course];
    }

    /**
     * Steps are created with increasing sort order and sensible defaults.
     */
    public function test_add_and_get_steps(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity();

        $first = $manager->add_step((object) ['title' => 'Read the intro', 'type' => manager::STEP_MANUAL]);
        $second = $manager->add_step((object) [
            'title' => 'Open the form',
            'type' => manager::STEP_LINK,
            'url' => 'https://example.com/form',
        ]);

        $steps = array_values($manager->get_steps());
        $this->assertCount(2, $steps);
        $this->assertEquals([$first->id, $second->id], [$steps[0]->id, $steps[1]->id]);
        $this->assertSame('Read the intro', $steps[0]->title);
        $this->assertEquals(manager::STEP_LINK, $steps[1]->type);
        $this->assertGreaterThan($steps[0]->sortorder, $steps[1]->sortorder);
        $this->assertNotEmpty($steps[0]->timecreated);
    }

    /**
     * Updating a step keeps its identity and sort order.
     */
    public function test_update_step(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity();

        $step = $manager->add_step((object) ['title' => 'Old title', 'type' => manager::STEP_MANUAL]);
        $manager->update_step((object) ['id' => $step->id, 'title' => 'New title']);

        $updated = $manager->get_step($step->id);
        $this->assertSame('New title', $updated->title);
        $this->assertEquals($step->sortorder, $updated->sortorder);
    }

    /**
     * Deleting a step removes recorded progress for it.
     */
    public function test_delete_step(): void {
        global $DB;
        $this->resetAfterTest();
        [$manager] = $this->create_activity();
        $user = $this->getDataGenerator()->create_user();

        $step = $manager->add_step((object) ['title' => 'Step', 'type' => manager::STEP_MANUAL]);
        $manager->complete_step($step, $user->id);
        $this->assertTrue($DB->record_exists('browse_progress', ['stepid' => $step->id]));

        $manager->delete_step($step->id);
        $this->assertFalse($DB->record_exists('browse_steps', ['id' => $step->id]));
        $this->assertFalse($DB->record_exists('browse_progress', ['stepid' => $step->id]));
    }

    /**
     * Steps can be moved up and down.
     */
    public function test_move_step(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity();

        $a = $manager->add_step((object) ['title' => 'A', 'type' => manager::STEP_MANUAL]);
        $b = $manager->add_step((object) ['title' => 'B', 'type' => manager::STEP_MANUAL]);
        $c = $manager->add_step((object) ['title' => 'C', 'type' => manager::STEP_MANUAL]);

        $manager->move_step($c->id, manager::MOVE_UP);
        $this->assertEquals([$a->id, $c->id, $b->id], array_keys($manager->get_steps()));

        $manager->move_step($a->id, manager::MOVE_DOWN);
        $this->assertEquals([$c->id, $a->id, $b->id], array_keys($manager->get_steps()));

        // Moving beyond the ends is a no-op.
        $manager->move_step($c->id, manager::MOVE_UP);
        $manager->move_step($b->id, manager::MOVE_DOWN);
        $this->assertEquals([$c->id, $a->id, $b->id], array_keys($manager->get_steps()));
    }

    /**
     * Completing a step records progress once and fires the step_completed event.
     */
    public function test_complete_step(): void {
        $this->resetAfterTest();
        [$manager, $browse] = $this->create_activity();
        $user = $this->getDataGenerator()->create_user();

        $step = $manager->add_step((object) ['title' => 'Step', 'type' => manager::STEP_MANUAL]);

        $sink = $this->redirectEvents();
        $this->assertTrue($manager->complete_step($step, $user->id));
        $this->assertFalse($manager->complete_step($step, $user->id), 'Completing twice must be idempotent');
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \mod_browse\event\step_completed);
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals($step->id, $event->objectid);
        $this->assertEquals($user->id, $event->relateduserid);

        $this->assertTrue($manager->is_step_completed($step->id, $user->id));
        $progress = $manager->get_user_progress($user->id);
        $this->assertArrayHasKey($step->id, $progress);
    }

    /**
     * Manual steps can be unticked again; automatic steps cannot.
     */
    public function test_revoke_step(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity();
        $user = $this->getDataGenerator()->create_user();

        $manual = $manager->add_step((object) ['title' => 'Manual', 'type' => manager::STEP_MANUAL]);
        $link = $manager->add_step((object) [
            'title' => 'Link', 'type' => manager::STEP_LINK, 'url' => 'https://example.com/',
        ]);

        $manager->complete_step($manual, $user->id);
        $manager->complete_step($link, $user->id);

        $this->assertTrue($manager->revoke_step($manual, $user->id));
        $this->assertFalse($manager->is_step_completed($manual->id, $user->id));

        $this->expectException(\moodle_exception::class);
        $manager->revoke_step($link, $user->id);
    }

    /**
     * Without sequential mode all steps are available; with it, only after the previous ones.
     */
    public function test_is_step_available(): void {
        $this->resetAfterTest();

        // Free order.
        [$manager] = $this->create_activity(['sequential' => 0]);
        $user = $this->getDataGenerator()->create_user();
        $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $two = $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);
        $this->assertTrue($manager->is_step_available($two, $user->id));

        // Sequential.
        [$manager] = $this->create_activity(['sequential' => 1]);
        $one = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $two = $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);

        $this->assertTrue($manager->is_step_available($one, $user->id));
        $this->assertFalse($manager->is_step_available($two, $user->id));

        $manager->complete_step($one, $user->id);
        $this->assertTrue($manager->is_step_available($two, $user->id));
    }

    /**
     * Completing an unavailable step in sequential mode is rejected.
     */
    public function test_complete_step_unavailable(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity(['sequential' => 1]);
        $user = $this->getDataGenerator()->create_user();

        $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $two = $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);

        $this->expectException(\moodle_exception::class);
        $manager->complete_step($two, $user->id);
    }

    /**
     * All-steps check requires at least one step and every step completed.
     */
    public function test_all_steps_completed(): void {
        $this->resetAfterTest();
        [$manager] = $this->create_activity();
        $user = $this->getDataGenerator()->create_user();

        // An activity without steps is never "all completed".
        $this->assertFalse($manager->all_steps_completed($user->id));

        $one = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $two = $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);

        $this->assertFalse($manager->all_steps_completed($user->id));
        $manager->complete_step($one, $user->id);
        $this->assertFalse($manager->all_steps_completed($user->id));
        $manager->complete_step($two, $user->id);
        $this->assertTrue($manager->all_steps_completed($user->id));
    }

    /**
     * The generator create_step helper produces valid ordered steps.
     */
    public function test_generator_create_step(): void {
        $this->resetAfterTest();
        [$manager, $browse] = $this->create_activity();

        /** @var \mod_browse_generator $plugingenerator */
        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_browse');
        $plugingenerator->create_step(['browseid' => $browse->id]);
        $plugingenerator->create_step(['browseid' => $browse->id, 'title' => 'Custom', 'type' => 'callback']);

        $steps = array_values($manager->get_steps());
        $this->assertCount(2, $steps);
        $this->assertSame('Step 1', $steps[0]->title);
        $this->assertSame('Custom', $steps[1]->title);
        $this->assertEquals(manager::STEP_CALLBACK, $steps[1]->type);
    }
}
