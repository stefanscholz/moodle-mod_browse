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
 * Return link target for callback steps.
 *
 * The external site sends the student here (e.g. as redirect after submitting
 * a survey). The arriving student is identified by their Moodle session and the
 * step is recorded as completed. This is intentionally reachable via a plain
 * GET without sesskey, because the request originates from the external site;
 * recording the logged-in user's own step progress is a benign, idempotent
 * operation.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
$stepid = required_param('step', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'browse');
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/browse:view', $context);

$manager = \mod_browse\local\manager::from_cmid($cm->id);
$step = $manager->get_step($stepid);

if ((int) $step->type !== \mod_browse\local\manager::STEP_CALLBACK) {
    throw new moodle_exception('invalidstep', 'browse');
}

$PAGE->set_url('/mod/browse/complete.php', ['id' => $cm->id, 'step' => $step->id]);
$PAGE->set_title($course->shortname . ': ' . format_string($manager->get_instance()->name));
$PAGE->set_heading($course->fullname);

$viewurl = new moodle_url('/mod/browse/view.php', ['id' => $cm->id]);

if (has_capability('mod/browse:completesteps', $context) && !isguestuser()) {
    if (!$manager->is_step_available($step, (int) $USER->id)) {
        notice(get_string('stepnotavailable', 'browse'), $viewurl);
    }
    $manager->complete_step($step, (int) $USER->id);
}

echo $OUTPUT->header();
echo $OUTPUT->notification(
    get_string('stepcompletedmessage', 'browse', format_string($step->title, true, ['context' => $context])),
    \core\output\notification::NOTIFY_SUCCESS,
    false
);
echo $OUTPUT->single_button($viewurl, get_string('continue'), 'get', ['type' => 'primary']);
echo $OUTPUT->footer();
