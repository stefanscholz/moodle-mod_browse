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

namespace mod_browse\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_browse\local\manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the mod_browse privacy provider.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Create two activities with progress from two users.
     *
     * @return array [manager1, manager2, user1, user2]
     */
    private function setup_progress(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_and_enrol($course, 'student');
        $user2 = $generator->create_and_enrol($course, 'student');

        $browse1 = $generator->create_module('browse', ['course' => $course->id]);
        $browse2 = $generator->create_module('browse', ['course' => $course->id]);
        $manager1 = manager::from_cmid($browse1->cmid);
        $manager2 = manager::from_cmid($browse2->cmid);

        $step1 = $manager1->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $step2 = $manager2->add_step((object) ['title' => 'Two', 'type' => manager::STEP_MANUAL]);

        $manager1->complete_step($step1, (int) $user1->id);
        $manager2->complete_step($step2, (int) $user1->id);
        $manager1->complete_step($step1, (int) $user2->id);

        return [$manager1, $manager2, $user1, $user2];
    }

    /**
     * Contexts with recorded progress are reported for the user.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        [$manager1, $manager2, $user1, $user2] = $this->setup_progress();

        $contextids = provider::get_contexts_for_userid((int) $user1->id)->get_contextids();
        $this->assertEqualsCanonicalizing(
            [$manager1->get_context()->id, $manager2->get_context()->id],
            $contextids
        );

        $contextids = provider::get_contexts_for_userid((int) $user2->id)->get_contextids();
        $this->assertEquals([$manager1->get_context()->id], $contextids);
    }

    /**
     * Users with progress in a context are reported.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();
        [$manager1, $manager2, $user1, $user2] = $this->setup_progress();

        $userlist = new userlist($manager1->get_context(), 'mod_browse');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([(int) $user1->id, (int) $user2->id], $userlist->get_userids());

        $userlist = new userlist($manager2->get_context(), 'mod_browse');
        provider::get_users_in_context($userlist);
        $this->assertEquals([(int) $user1->id], $userlist->get_userids());
    }

    /**
     * Exported data contains the user's completed steps.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        [$manager1, $manager2, $user1] = $this->setup_progress();
        $context = $manager1->get_context();

        $this->export_context_data_for_user((int) $user1->id, $context, 'mod_browse');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('steps', 'browse')]);
        $this->assertCount(1, $data->steps);
        $step = reset($data->steps);
        $this->assertSame('One', $step['title']);
        $this->assertNotEmpty($step['timecompleted']);
    }

    /**
     * Deleting for a whole context removes only that context's progress.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$manager1, $manager2, $user1] = $this->setup_progress();

        provider::delete_data_for_all_users_in_context($manager1->get_context());

        $this->assertEmpty($manager1->get_user_progress((int) $user1->id));
        $this->assertNotEmpty($manager2->get_user_progress((int) $user1->id));
        $this->assertEquals(1, $DB->count_records('browse_progress'));
    }

    /**
     * Deleting for one user removes only their progress in the given contexts.
     */
    public function test_delete_data_for_user(): void {
        $this->resetAfterTest();
        [$manager1, $manager2, $user1, $user2] = $this->setup_progress();

        $contextlist = new approved_contextlist(
            $user1,
            'mod_browse',
            [$manager1->get_context()->id, $manager2->get_context()->id]
        );
        provider::delete_data_for_user($contextlist);

        $this->assertEmpty($manager1->get_user_progress((int) $user1->id));
        $this->assertEmpty($manager2->get_user_progress((int) $user1->id));
        $this->assertNotEmpty($manager1->get_user_progress((int) $user2->id));
    }

    /**
     * Deleting for an approved userlist removes only those users in that context.
     */
    public function test_delete_data_for_users(): void {
        $this->resetAfterTest();
        [$manager1, $manager2, $user1, $user2] = $this->setup_progress();

        $userlist = new approved_userlist($manager1->get_context(), 'mod_browse', [(int) $user1->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEmpty($manager1->get_user_progress((int) $user1->id));
        $this->assertNotEmpty($manager1->get_user_progress((int) $user2->id));
        $this->assertNotEmpty($manager2->get_user_progress((int) $user1->id));
    }
}
