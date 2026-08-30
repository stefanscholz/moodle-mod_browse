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
 * List all browse activities in a course.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \core\event\course_module_instance_list_viewed::create(['context' => $coursecontext]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/browse/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'browse'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'browse'));

$modinfo = get_fast_modinfo($course);
if (empty($modinfo->instances['browse'])) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'browse')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [get_string('sectionname', 'format_' . $course->format), get_string('name')];
$table->align = ['left', 'left'];

foreach ($modinfo->instances['browse'] as $cm) {
    if (!$cm->uservisible) {
        continue;
    }
    $sectionname = get_section_name($course, $cm->sectionnum);
    $class = $cm->visible ? '' : 'class="dimmed"';
    $link = "<a $class href=\"" . $cm->url->out() . '">' . format_string($cm->name) . '</a>';
    $table->data[] = [$sectionname, $link];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
