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
 * Behat data generator for mod_browse.
 *
 * @package   mod_browse
 * @category  test
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_browse_generator extends behat_generator_base {
    /**
     * Entities that can be created in Behat feature files.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'steps' => [
                'singular' => 'step',
                'datagenerator' => 'step',
                'required' => ['browse', 'title'],
                'switchids' => ['browse' => 'browseid'],
            ],
        ];
    }

    /**
     * Look up the id of a browse activity from its name.
     *
     * @param string $browsename the activity name, for example 'Test browse'.
     * @return int corresponding instance id.
     */
    protected function get_browse_id(string $browsename): int {
        $cm = $this->get_cm_by_activity_name('browse', $browsename);
        return (int) $cm->instance;
    }
}
