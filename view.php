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
 * Browse activity view page.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'browse');
$browse = $DB->get_record('browse', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/browse:view', $context);

$manager = \mod_browse\local\manager::from_coursemodule($cm, $browse, $course);
$manager->mark_viewed();

$PAGE->set_url('/mod/browse/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . format_string($browse->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($browse);
$PAGE->requires->js_call_amd('mod_browse/steps', 'init', [$cm->id]);

echo $OUTPUT->header();

$viewpage = new \mod_browse\output\view_page($manager, $USER->id);
echo $OUTPUT->render($viewpage);

echo $OUTPUT->footer();
