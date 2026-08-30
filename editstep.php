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
 * Create or edit one step of a browse activity.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$stepid = optional_param('step', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'browse');
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/browse:managesteps', $context);

$manager = \mod_browse\local\manager::from_cmid($cm->id);
$returnurl = new moodle_url('/mod/browse/managesteps.php', ['id' => $cm->id]);

$PAGE->set_url('/mod/browse/editstep.php', ['cmid' => $cm->id, 'step' => $stepid]);
$PAGE->set_title($course->shortname . ': ' .
    get_string($stepid ? 'editstep' : 'addstep', 'browse'));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($manager->get_instance());

$form = new \mod_browse\form\step_form($PAGE->url->out(false));

if ($stepid) {
    $step = $manager->get_step($stepid);
    $form->set_data([
        'cmid' => $cm->id,
        'step' => $step->id,
        'title' => $step->title,
        'type' => $step->type,
        'url' => $step->url,
        'description' => ['text' => $step->description, 'format' => $step->descriptionformat],
    ]);
} else {
    $form->set_data(['cmid' => $cm->id, 'step' => 0]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $step = new stdClass();
    $step->title = $data->title;
    $step->type = (int) $data->type;
    $step->url = ($step->type === \mod_browse\local\manager::STEP_LINK)
        ? \mod_browse\local\url_helper::fix_submitted_url($data->url)
        : '';
    $step->description = $data->description['text'];
    $step->descriptionformat = $data->description['format'];

    if ($data->step) {
        $step->id = $data->step;
        $manager->update_step($step);
    } else {
        $manager->add_step($step);
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($stepid ? 'editstep' : 'addstep', 'browse'), 3);
$form->display();
echo $OUTPUT->footer();
