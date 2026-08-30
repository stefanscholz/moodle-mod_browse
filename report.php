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
 * Step progress report: students x steps grid with group filtering.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'browse');
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/browse:viewreport', $context);

$manager = \mod_browse\local\manager::from_cmid($cm->id);
$pageurl = new moodle_url('/mod/browse/report.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname . ': ' . get_string('report', 'browse'));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($manager->get_instance());

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'browse'), 3);

groups_print_activity_menu($cm, $pageurl);
$currentgroup = groups_get_activity_group($cm, true);

$steps = array_values($manager->get_steps());
if (!$steps) {
    echo $OUTPUT->notification(get_string('nosteps', 'browse'), \core\output\notification::NOTIFY_INFO, false);
    echo $OUTPUT->footer();
    die;
}

$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$users = get_enrolled_users($context, 'mod/browse:completesteps', $currentgroup ?: 0,
    'u.id, ' . $userfields, 'u.lastname ASC, u.firstname ASC');

// All recorded progress of this activity, keyed by user and step.
$progress = [];
$sql = "SELECT p.id, p.userid, p.stepid, p.timecompleted
          FROM {browse_progress} p
          JOIN {browse_steps} s ON s.id = p.stepid
         WHERE s.browseid = :browseid";
foreach ($DB->get_records_sql($sql, ['browseid' => $manager->get_instance()->id]) as $record) {
    $progress[$record->userid][$record->stepid] = $record->timecompleted;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_browse-report';
$table->head = [get_string('fullnameuser')];
foreach ($steps as $index => $step) {
    $table->head[] = html_writer::span(($index + 1) . '. ' .
        format_string($step->title, true, ['context' => $context]));
}
$table->head[] = get_string('progress', 'moodle');

$yes = $OUTPUT->pix_icon('i/grade_correct', get_string('stepdone', 'browse'));
foreach ($users as $user) {
    $row = [html_writer::link(
        new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]),
        fullname($user))];
    $complete = 0;
    foreach ($steps as $step) {
        if (isset($progress[$user->id][$step->id])) {
            $complete++;
            $row[] = html_writer::span($yes, '', ['title' => userdate((int) $progress[$user->id][$step->id])]);
        } else {
            $row[] = '-';
        }
    }
    $row[] = get_string('progress', 'browse', (object) ['complete' => $complete, 'total' => count($steps)]);
    $table->data[] = $row;
}

if (!$table->data) {
    echo $OUTPUT->notification(get_string('nothingtodisplay'), \core\output\notification::NOTIFY_INFO, false);
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
