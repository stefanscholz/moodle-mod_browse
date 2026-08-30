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
 * Backup structure step for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete browse structure for backup, with user data.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_browse_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $browse = new backup_nested_element('browse', ['id'], [
            'name', 'intro', 'introformat', 'externalurl', 'display',
            'parameters', 'sequential', 'completionsteps', 'timemodified',
        ]);

        $steps = new backup_nested_element('steps');
        $step = new backup_nested_element('step', ['id'], [
            'title', 'description', 'descriptionformat', 'type', 'url',
            'sortorder', 'timecreated', 'timemodified',
        ]);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'timecompleted',
        ]);

        $browse->add_child($steps);
        $steps->add_child($step);
        $step->add_child($progresses);
        $progresses->add_child($progress);

        $browse->set_source_table('browse', ['id' => backup::VAR_ACTIVITYID]);
        $step->set_source_table('browse_steps', ['browseid' => backup::VAR_PARENTID], 'sortorder ASC');
        if ($userinfo) {
            $progress->set_source_table('browse_progress', ['stepid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');

        $browse->annotate_files('mod_browse', 'intro', null);

        return $this->prepare_activity_structure($browse);
    }
}
