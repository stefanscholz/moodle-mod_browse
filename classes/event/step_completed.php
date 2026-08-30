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

declare(strict_types=1);

namespace mod_browse\event;

/**
 * Event fired when a user completes one step of a browse activity.
 *
 * @property-read array $other {
 *     Extra information about the event.
 *     - int browseid: the id of the browse instance.
 * }
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step_completed extends \core\event\base {
    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'browse_steps';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventstepcompleted', 'browse');
    }

    /**
     * Description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->relateduserid' completed the step with id '$this->objectid' " .
            "of the browse activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * URL related to the event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/browse/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Validate the event data.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        if (!isset($this->other['browseid'])) {
            throw new \coding_exception('The \'browseid\' value must be set in other.');
        }
    }

    /**
     * Mapping of the objectid for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'browse_steps', 'restore' => 'browse_step'];
    }

    /**
     * Mapping of the other fields for backup and restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return ['browseid' => ['db' => 'browse', 'restore' => 'browse']];
    }
}
