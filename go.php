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
 * Tracked redirect for visited-link steps: records the visit, then forwards to the external page.
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

if ((int) $step->type !== \mod_browse\local\manager::STEP_LINK || empty($step->url)) {
    throw new moodle_exception('invalidstep', 'browse');
}

$PAGE->set_url('/mod/browse/go.php', ['id' => $cm->id, 'step' => $step->id]);

// Users who cannot complete steps (e.g. teachers previewing) are simply forwarded.
if (has_capability('mod/browse:completesteps', $context) && !isguestuser()) {
    if (!$manager->is_step_available($step, (int) $USER->id)) {
        notice(get_string('stepnotavailable', 'browse'), new moodle_url('/mod/browse/view.php', ['id' => $cm->id]));
    }
    $manager->complete_step($step, (int) $USER->id);
}

$fullurl = \mod_browse\local\url_helper::get_step_url($step, $manager->get_instance(), $cm, $course);
redirect($fullurl);
