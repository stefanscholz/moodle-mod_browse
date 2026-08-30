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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Backup and restore tests for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class backup_restore_test extends \advanced_testcase {

    /**
     * Duplicating an activity copies the configuration and steps but not user progress.
     */
    public function test_duplicate_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $browse = $generator->create_module('browse', [
            'course' => $course->id,
            'externalurl' => 'https://example.com/content',
            'parameters' => local\url_helper::encode_parameters(['uid' => 'userid']),
            'sequential' => 1,
        ]);
        $manager = manager::from_cmid($browse->cmid);
        $step = $manager->add_step((object) [
            'title' => 'Visit page', 'type' => manager::STEP_LINK, 'url' => 'https://example.com/page',
        ]);
        $manager->add_step((object) ['title' => 'Check', 'type' => manager::STEP_MANUAL]);
        $manager->complete_step($step, (int) $student->id);

        $cmactions = new \core_courseformat\local\cmactions($course);
        $newcm = $cmactions->duplicate($browse->cmid);
        $newmanager = manager::from_cmid($newcm->id);
        $newinstance = $newmanager->get_instance();

        $this->assertSame('https://example.com/content', $newinstance->externalurl);
        $this->assertEquals(1, $newinstance->sequential);
        $this->assertSame(['uid' => 'userid'], local\url_helper::decode_parameters($newinstance->parameters));

        $newsteps = array_values($newmanager->get_steps());
        $this->assertCount(2, $newsteps);
        $this->assertSame('Visit page', $newsteps[0]->title);
        $this->assertEquals(manager::STEP_LINK, $newsteps[0]->type);
        $this->assertSame('https://example.com/page', $newsteps[0]->url);
        $this->assertSame('Check', $newsteps[1]->title);

        // User progress is not duplicated.
        $this->assertEmpty($newmanager->get_user_progress((int) $student->id));
    }

    /**
     * A full course backup and restore with user data carries the progress across, remapped to the new steps.
     */
    public function test_backup_restore_with_userdata(): void {
        global $USER, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $browse = $generator->create_module('browse', ['course' => $course->id]);
        $manager = manager::from_cmid($browse->cmid);
        $step1 = $manager->add_step((object) ['title' => 'One', 'type' => manager::STEP_MANUAL]);
        $manager->add_step((object) ['title' => 'Two', 'type' => manager::STEP_CALLBACK]);
        $manager->complete_step($step1, (int) $student->id);

        // Backup the course including user data.
        $bc = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id);
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Extract the backup file so the restore controller can use it.
        $backupid = 'browse_restore_test';
        $backuppath = $CFG->tempdir . '/backup/' . $backupid;
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backuppath);

        // Restore into a new course.
        $newcourseid = \restore_dbops::create_new_course('Restored', 'RST1', $course->category);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, $USER->id, \backup::TARGET_NEW_COURSE);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $modinfo = get_fast_modinfo($newcourseid);
        $cms = $modinfo->get_instances_of('browse');
        $this->assertCount(1, $cms);
        $newmanager = manager::from_cmid(reset($cms)->id);

        $newsteps = array_values($newmanager->get_steps());
        $this->assertCount(2, $newsteps);
        $this->assertSame(['One', 'Two'], array_column($newsteps, 'title'));

        $progress = $newmanager->get_user_progress((int) $student->id);
        $this->assertCount(1, $progress);
        $this->assertArrayHasKey($newsteps[0]->id, $progress);
        $this->assertArrayNotHasKey($step1->id, $progress, 'Progress must be remapped to the new step ids');
    }
}
