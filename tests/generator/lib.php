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
 * Data generator for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/resourcelib.php');

/**
 * Browse module data generator class.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_browse_generator extends testing_module_generator {

    /**
     * Create a browse activity instance.
     *
     * @param array|stdClass|null $record instance data
     * @param array|null $options course module options
     * @return stdClass the created instance record including cmid
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        if (!isset($record->externalurl)) {
            $record->externalurl = 'https://example.com/';
        }
        if (!isset($record->display)) {
            $record->display = RESOURCELIB_DISPLAY_EMBED;
        }

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Create a step within a browse activity.
     *
     * @param stdClass $browse the browse instance record
     * @param array|stdClass|null $record step data
     * @return stdClass the created step record
     */
    public function create_step(stdClass $browse, $record = null): stdClass {
        $record = (object) (array) $record;

        if (!isset($record->title)) {
            $record->title = 'Step ' . $this->stepcount($browse);
        }
        if (!isset($record->type)) {
            $record->type = \mod_browse\local\manager::STEP_MANUAL;
        }

        $manager = \mod_browse\local\manager::from_instance($browse);
        return $manager->add_step($record);
    }

    /**
     * Count the existing steps of an instance, for default step titles.
     *
     * @param stdClass $browse the browse instance record
     * @return int the next step number
     */
    private function stepcount(stdClass $browse): int {
        global $DB;
        return $DB->count_records('browse_steps', ['browseid' => $browse->id]) + 1;
    }
}
