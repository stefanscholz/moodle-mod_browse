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
 * Restore task for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/browse/backup/moodle2/restore_browse_stepslib.php');

/**
 * The complete browse restore task.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_browse_activity_task extends restore_activity_task {
    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_browse_activity_structure_step('browse_structure', 'browse.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('browse', ['intro'], 'browse'),
        ];
    }

    /**
     * Define the decoding rules for links belonging to the activity.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('BROWSEVIEWBYID', '/mod/browse/view.php?id=$1', 'course_module'),
            new restore_decode_rule('BROWSEINDEX', '/mod/browse/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Define the restore log rules that will be applied when restoring activity logs.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('browse', 'view', 'view.php?id={course_module}', '{browse}'),
        ];
    }
}
