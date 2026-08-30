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

declare(strict_types=1);

namespace mod_browse\local;

use cm_info;
use completion_info;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Service class for one browse activity: step management and progress tracking.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var int Step is ticked off manually by the student. */
    public const STEP_MANUAL = 0;

    /** @var int Step is completed by opening its link. */
    public const STEP_LINK = 1;

    /** @var int Step is completed when the external site sends the student to the return link. */
    public const STEP_CALLBACK = 2;

    /** @var int Direction for move_step: towards the top of the list. */
    public const MOVE_UP = -1;

    /** @var int Direction for move_step: towards the end of the list. */
    public const MOVE_DOWN = 1;

    /** @var cm_info the course module */
    private cm_info $cm;

    /** @var stdClass the browse instance record */
    private stdClass $browse;

    /** @var stdClass the course record */
    private stdClass $course;

    /**
     * Constructor, use one of the from_* factory methods.
     *
     * @param cm_info $cm the course module
     * @param stdClass $browse the browse instance record
     * @param stdClass $course the course record
     */
    private function __construct(cm_info $cm, stdClass $browse, stdClass $course) {
        $this->cm = $cm;
        $this->browse = $browse;
        $this->course = $course;
    }

    /**
     * Create a manager from a course module id.
     *
     * @param int $cmid the course module id
     * @return self
     */
    public static function from_cmid(int $cmid): self {
        global $DB;

        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'browse');
        $browse = $DB->get_record('browse', ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $browse, $course);
    }

    /**
     * Create a manager from a browse instance record.
     *
     * @param stdClass $browse the browse instance record
     * @return self
     */
    public static function from_instance(stdClass $browse): self {
        [$course, $cm] = get_course_and_cm_from_instance($browse->id, 'browse');
        return new self($cm, $browse, $course);
    }

    /**
     * Create a manager from already loaded records.
     *
     * @param cm_info $cm the course module
     * @param stdClass $browse the browse instance record
     * @param stdClass $course the course record
     * @return self
     */
    public static function from_coursemodule(cm_info $cm, stdClass $browse, stdClass $course): self {
        return new self($cm, $browse, $course);
    }

    /**
     * Get the course module.
     *
     * @return cm_info
     */
    public function get_cm(): cm_info {
        return $this->cm;
    }

    /**
     * Get the browse instance record.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->browse;
    }

    /**
     * Get the course record.
     *
     * @return stdClass
     */
    public function get_course(): stdClass {
        return $this->course;
    }

    /**
     * Get the module context.
     *
     * @return \context_module
     */
    public function get_context(): \context_module {
        return $this->cm->context;
    }

    /**
     * Is the external content embedded on the activity page (as opposed to opening in a new window)?
     *
     * @return bool
     */
    public function is_embedded(): bool {
        global $CFG;
        require_once($CFG->libdir . '/resourcelib.php');
        return (int) $this->browse->display === RESOURCELIB_DISPLAY_EMBED;
    }

    /**
     * Mark the activity as viewed: trigger the event and update view completion.
     */
    public function mark_viewed(): void {
        $event = \mod_browse\event\course_module_viewed::create([
            'objectid' => $this->browse->id,
            'context' => $this->get_context(),
        ]);
        $event->add_record_snapshot('course', $this->course);
        $event->add_record_snapshot('course_modules', $this->cm->get_course_module_record());
        $event->add_record_snapshot('browse', $this->browse);
        $event->trigger();

        $completion = new completion_info($this->course);
        $completion->set_module_viewed($this->cm);
    }

    /**
     * Get all steps ordered by their sort order, keyed by step id.
     *
     * @return stdClass[]
     */
    public function get_steps(): array {
        global $DB;
        return $DB->get_records('browse_steps', ['browseid' => $this->browse->id], 'sortorder ASC, id ASC');
    }

    /**
     * Get the number of steps.
     *
     * @return int
     */
    public function count_steps(): int {
        global $DB;
        return $DB->count_records('browse_steps', ['browseid' => $this->browse->id]);
    }

    /**
     * Get one step of this activity.
     *
     * @param int $stepid the step id
     * @return stdClass the step record
     */
    public function get_step(int $stepid): stdClass {
        global $DB;
        return $DB->get_record('browse_steps',
            ['id' => $stepid, 'browseid' => $this->browse->id], '*', MUST_EXIST);
    }

    /**
     * Add a new step at the end of the list.
     *
     * @param stdClass $data step data: title, type, and optionally description, descriptionformat, url
     * @return stdClass the created step record
     */
    public function add_step(stdClass $data): stdClass {
        global $DB;

        $step = new stdClass();
        $step->browseid = $this->browse->id;
        $step->title = trim((string) $data->title);
        $step->description = $data->description ?? '';
        $step->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        $step->type = (int) ($data->type ?? self::STEP_MANUAL);
        $step->url = isset($data->url) ? trim((string) $data->url) : null;
        $step->sortorder = 1 + (int) $DB->get_field('browse_steps', 'COALESCE(MAX(sortorder), 0)',
            ['browseid' => $this->browse->id]);
        $step->timecreated = time();
        $step->timemodified = $step->timecreated;

        $step->id = $DB->insert_record('browse_steps', $step);
        return $step;
    }

    /**
     * Update an existing step. Only content fields can be changed, not the position.
     *
     * @param stdClass $data step data including the id
     * @return stdClass the updated step record
     */
    public function update_step(stdClass $data): stdClass {
        global $DB;

        $step = $this->get_step((int) $data->id);

        foreach (['title', 'description', 'descriptionformat', 'type', 'url'] as $field) {
            if (property_exists($data, $field)) {
                $step->$field = $data->$field;
            }
        }
        $step->title = trim((string) $step->title);
        $step->type = (int) $step->type;
        $step->timemodified = time();

        $DB->update_record('browse_steps', $step);
        return $step;
    }

    /**
     * Delete a step including all progress recorded for it.
     *
     * @param int $stepid the step id
     */
    public function delete_step(int $stepid): void {
        global $DB;

        $step = $this->get_step($stepid);

        $DB->delete_records('browse_progress', ['stepid' => $step->id]);
        $DB->delete_records('browse_steps', ['id' => $step->id]);
    }

    /**
     * Move a step one position up or down. Moving beyond the ends is a no-op.
     *
     * @param int $stepid the step id
     * @param int $direction self::MOVE_UP or self::MOVE_DOWN
     */
    public function move_step(int $stepid, int $direction): void {
        global $DB;

        $steps = array_values($this->get_steps());
        $index = array_search($stepid, array_column($steps, 'id'));
        if ($index === false) {
            throw new moodle_exception('invalidstep', 'browse');
        }

        $newindex = $index + ($direction === self::MOVE_UP ? -1 : 1);
        if ($newindex < 0 || $newindex >= count($steps)) {
            return;
        }

        $DB->set_field('browse_steps', 'sortorder', $steps[$newindex]->sortorder, ['id' => $steps[$index]->id]);
        $DB->set_field('browse_steps', 'sortorder', $steps[$index]->sortorder, ['id' => $steps[$newindex]->id]);
    }

    /**
     * Get the recorded progress of a user, keyed by step id.
     *
     * @param int $userid the user id
     * @return int[] map of step id => time completed
     */
    public function get_user_progress(int $userid): array {
        global $DB;

        $sql = "SELECT p.stepid, p.timecompleted
                  FROM {browse_progress} p
                  JOIN {browse_steps} s ON s.id = p.stepid
                 WHERE s.browseid = :browseid AND p.userid = :userid";
        return $DB->get_records_sql_menu($sql, ['browseid' => $this->browse->id, 'userid' => $userid]);
    }

    /**
     * Has the user completed the given step?
     *
     * @param int $stepid the step id
     * @param int $userid the user id
     * @return bool
     */
    public function is_step_completed(int $stepid, int $userid): bool {
        global $DB;
        return $DB->record_exists('browse_progress', ['stepid' => $stepid, 'userid' => $userid]);
    }

    /**
     * Is the step currently available to the user?
     *
     * In sequential mode a step only becomes available once all previous steps are completed.
     *
     * @param stdClass $step the step record
     * @param int $userid the user id
     * @return bool
     */
    public function is_step_available(stdClass $step, int $userid): bool {
        if (empty($this->browse->sequential)) {
            return true;
        }

        $progress = $this->get_user_progress($userid);
        foreach ($this->get_steps() as $other) {
            if ($other->id == $step->id) {
                return true;
            }
            if (!isset($progress[$other->id])) {
                return false;
            }
        }

        // The step does not belong to this activity.
        throw new moodle_exception('invalidstep', 'browse');
    }

    /**
     * Record a step as completed by a user.
     *
     * @param stdClass $step the step record
     * @param int $userid the user id
     * @return bool true if progress was recorded, false if the step was already completed
     */
    public function complete_step(stdClass $step, int $userid): bool {
        global $DB;

        if ($step->browseid != $this->browse->id) {
            throw new moodle_exception('invalidstep', 'browse');
        }
        if ($this->is_step_completed((int) $step->id, $userid)) {
            return false;
        }
        if (!$this->is_step_available($step, $userid)) {
            throw new moodle_exception('stepnotavailable', 'browse');
        }

        $progress = new stdClass();
        $progress->stepid = $step->id;
        $progress->userid = $userid;
        $progress->timecompleted = time();
        $progress->id = $DB->insert_record('browse_progress', $progress);

        $event = \mod_browse\event\step_completed::create([
            'objectid' => $step->id,
            'context' => $this->get_context(),
            'relateduserid' => $userid,
            'other' => ['browseid' => $this->browse->id],
        ]);
        $event->trigger();

        $this->update_completion_state($userid);

        return true;
    }

    /**
     * Remove the recorded completion of a manual step.
     *
     * @param stdClass $step the step record
     * @param int $userid the user id
     * @return bool true if progress was removed, false if there was nothing to remove
     */
    public function revoke_step(stdClass $step, int $userid): bool {
        global $DB;

        if ($step->browseid != $this->browse->id) {
            throw new moodle_exception('invalidstep', 'browse');
        }
        if ((int) $step->type !== self::STEP_MANUAL) {
            throw new moodle_exception('cannotrevokestep', 'browse');
        }

        if (!$DB->record_exists('browse_progress', ['stepid' => $step->id, 'userid' => $userid])) {
            return false;
        }

        $DB->delete_records('browse_progress', ['stepid' => $step->id, 'userid' => $userid]);
        $this->update_completion_state($userid);

        return true;
    }

    /**
     * Has the user completed all steps? An activity without steps never counts as completed.
     *
     * @param int $userid the user id
     * @return bool
     */
    public function all_steps_completed(int $userid): bool {
        $total = $this->count_steps();
        return $total > 0 && count($this->get_user_progress($userid)) >= $total;
    }

    /**
     * Get the user's progress summary.
     *
     * @param int $userid the user id
     * @return stdClass object with complete and total counts
     */
    public function get_progress_summary(int $userid): stdClass {
        return (object) [
            'complete' => count($this->get_user_progress($userid)),
            'total' => $this->count_steps(),
        ];
    }

    /**
     * The URL that records a visited-link step and forwards to the external page.
     *
     * @param stdClass $step the step record
     * @return moodle_url
     */
    public function get_go_url(stdClass $step): moodle_url {
        return new moodle_url('/mod/browse/go.php', ['id' => $this->cm->id, 'step' => $step->id]);
    }

    /**
     * The return link of a callback step, for the external site to send the student to.
     *
     * @param stdClass $step the step record
     * @return moodle_url
     */
    public function get_callback_url(stdClass $step): moodle_url {
        return new moodle_url('/mod/browse/complete.php', ['id' => $this->cm->id, 'step' => $step->id]);
    }

    /**
     * Re-evaluate the activity completion state of a user after their step progress changed.
     *
     * @param int $userid the user id
     */
    private function update_completion_state(int $userid): void {
        if (empty($this->browse->completionsteps)) {
            return;
        }
        $completion = new completion_info($this->course);
        if ($completion->is_enabled($this->cm)) {
            $completion->update_state($this->cm, COMPLETION_UNKNOWN, $userid);
        }
    }
}
