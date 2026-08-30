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
 * Restore structure step for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores the browse structure from backup, with user data.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_browse_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the restore paths.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('browse', '/activity/browse');
        $paths[] = new restore_path_element('browse_step', '/activity/browse/steps/step');
        if ($userinfo) {
            $paths[] = new restore_path_element('browse_progress',
                '/activity/browse/steps/step/progresses/progress');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore one browse instance.
     *
     * @param array $data the instance data from the backup
     */
    protected function process_browse($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('browse', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore one step.
     *
     * @param array $data the step data from the backup
     */
    protected function process_browse_step($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->browseid = $this->get_new_parentid('browse');

        $newitemid = $DB->insert_record('browse_steps', $data);
        $this->set_mapping('browse_step', $oldid, $newitemid);
    }

    /**
     * Restore one progress record.
     *
     * @param array $data the progress data from the backup
     */
    protected function process_browse_progress($data) {
        global $DB;

        $data = (object) $data;
        $data->stepid = $this->get_new_parentid('browse_step');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('browse_progress', $data);
    }

    /**
     * Restore the files of the intro area.
     */
    protected function after_execute() {
        $this->add_related_files('mod_browse', 'intro', null);
    }
}
