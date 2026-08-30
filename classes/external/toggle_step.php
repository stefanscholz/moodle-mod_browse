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

namespace mod_browse\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_browse\local\manager;

/**
 * External function to tick a manual step off or on again.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_step extends external_api {
    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id of the browse activity'),
            'stepid' => new external_value(PARAM_INT, 'Id of the step to toggle'),
            'completed' => new external_value(PARAM_BOOL, 'New completion state of the step'),
        ]);
    }

    /**
     * Toggle a manual step for the current user.
     *
     * @param int $cmid course module id
     * @param int $stepid step id
     * @param bool $completed new state
     * @return array completed state and re-rendered steps panel
     */
    public static function execute(int $cmid, int $stepid, bool $completed): array {
        global $USER, $PAGE, $OUTPUT;

        [
            'cmid' => $cmid,
            'stepid' => $stepid,
            'completed' => $completed,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'stepid' => $stepid,
            'completed' => $completed,
        ]);

        $manager = manager::from_cmid($cmid);
        $context = $manager->get_context();
        self::validate_context($context);
        require_capability('mod/browse:completesteps', $context);

        $step = $manager->get_step($stepid);
        if ((int) $step->type !== manager::STEP_MANUAL) {
            throw new \moodle_exception('cannotrevokestep', 'browse');
        }

        if ($completed) {
            $manager->complete_step($step, (int) $USER->id);
        } else {
            $manager->revoke_step($step, (int) $USER->id);
        }

        $PAGE->set_context($context);
        $panel = new \mod_browse\output\steps_panel($manager, (int) $USER->id);

        return [
            'completed' => $manager->is_step_completed($stepid, (int) $USER->id),
            'panelhtml' => $OUTPUT->render($panel),
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'completed' => new external_value(PARAM_BOOL, 'Whether the step is now completed'),
            'panelhtml' => new external_value(PARAM_RAW, 'Re-rendered steps panel HTML'),
        ]);
    }
}
