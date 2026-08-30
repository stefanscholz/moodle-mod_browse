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
 * Manage the steps of a browse activity: list, reorder, delete.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$stepid = optional_param('step', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'browse');
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/browse:managesteps', $context);

$manager = \mod_browse\local\manager::from_cmid($cm->id);
$pageurl = new moodle_url('/mod/browse/managesteps.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname . ': ' . get_string('managesteps', 'browse'));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($manager->get_instance());

if ($action !== '' && $stepid) {
    $step = $manager->get_step($stepid);

    if ($action === 'moveup' || $action === 'movedown') {
        require_sesskey();
        $manager->move_step($step->id, $action === 'moveup'
            ? \mod_browse\local\manager::MOVE_UP
            : \mod_browse\local\manager::MOVE_DOWN);
        redirect($pageurl);
    }

    if ($action === 'delete') {
        require_sesskey();
        if ($confirm) {
            $manager->delete_step($step->id);
            redirect($pageurl);
        }
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deletestepconfirm', 'browse', format_string($step->title, true, ['context' => $context])),
            new moodle_url($pageurl, ['action' => 'delete', 'step' => $step->id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $pageurl
        );
        echo $OUTPUT->footer();
        die;
    }

    throw new moodle_exception('invalidaction', 'error');
}

echo $OUTPUT->header();

$steps = array_values($manager->get_steps());
$typenames = [
    \mod_browse\local\manager::STEP_MANUAL => get_string('steptypemanual', 'browse'),
    \mod_browse\local\manager::STEP_LINK => get_string('steptypelink', 'browse'),
    \mod_browse\local\manager::STEP_CALLBACK => get_string('steptypecallback', 'browse'),
];

if (!$steps) {
    echo $OUTPUT->notification(get_string('nosteps', 'browse'), \core\output\notification::NOTIFY_INFO, false);
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable mod_browse-managesteps';
    $table->head = [
        '#',
        get_string('steptitle', 'browse'),
        get_string('steptype', 'browse'),
        get_string('url'),
        get_string('actions'),
    ];

    foreach ($steps as $index => $step) {
        $actions = [];
        if ($index > 0) {
            $actions[] = $OUTPUT->action_icon(
                new moodle_url($pageurl, ['action' => 'moveup', 'step' => $step->id, 'sesskey' => sesskey()]),
                new pix_icon('t/up', get_string('moveup')));
        }
        if ($index < count($steps) - 1) {
            $actions[] = $OUTPUT->action_icon(
                new moodle_url($pageurl, ['action' => 'movedown', 'step' => $step->id, 'sesskey' => sesskey()]),
                new pix_icon('t/down', get_string('movedown')));
        }
        $actions[] = $OUTPUT->action_icon(
            new moodle_url('/mod/browse/editstep.php', ['cmid' => $cm->id, 'step' => $step->id]),
            new pix_icon('t/edit', get_string('edit')));
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($pageurl, ['action' => 'delete', 'step' => $step->id, 'sesskey' => sesskey()]),
            new pix_icon('t/delete', get_string('delete')));

        $urlcell = '';
        if ((int) $step->type === \mod_browse\local\manager::STEP_LINK) {
            $urlcell = html_writer::div(s($step->url), 'text-break');
        } else if ((int) $step->type === \mod_browse\local\manager::STEP_CALLBACK) {
            $callbackurl = $manager->get_callback_url($step)->out(false);
            $urlcell = html_writer::div(
                html_writer::tag('strong', get_string('callbackurl', 'browse')) . ' ' .
                $OUTPUT->help_icon('callbackurl', 'browse') .
                html_writer::tag('code', s($callbackurl), ['class' => 'd-block text-break user-select-all']),
                ''
            );
        }

        $table->data[] = [
            $index + 1,
            format_string($step->title, true, ['context' => $context]),
            $typenames[(int) $step->type] ?? '',
            $urlcell,
            implode(' ', $actions),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->single_button(
    new moodle_url('/mod/browse/editstep.php', ['cmid' => $cm->id]),
    get_string('addstep', 'browse'), 'get', ['type' => 'primary']);

echo $OUTPUT->footer();
