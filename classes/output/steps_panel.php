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

namespace mod_browse\output;

use mod_browse\local\manager;
use renderable;
use renderer_base;
use templatable;

/**
 * The list of steps as shown to a student, with their progress.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class steps_panel implements renderable, templatable {
    /** @var manager the activity manager */
    private manager $manager;

    /** @var int the user whose progress is shown */
    private int $userid;

    /**
     * Constructor.
     *
     * @param manager $manager the activity manager
     * @param int $userid the user whose progress is shown
     */
    public function __construct(manager $manager, int $userid) {
        $this->manager = $manager;
        $this->userid = $userid;
    }

    /**
     * Export the panel data for the template.
     *
     * @param renderer_base $output the renderer
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $manager = $this->manager;
        $cm = $manager->get_cm();
        $browse = $manager->get_instance();
        $context = $manager->get_context();

        $cancomplete = has_capability('mod/browse:completesteps', $context) && !isguestuser($this->userid);
        $progress = $manager->get_user_progress($this->userid);
        $linktarget = $manager->is_embedded() ? 'mod_browse_content_frame' : '_blank';

        $steps = [];
        $number = 0;
        foreach ($manager->get_steps() as $step) {
            $number++;
            $completed = isset($progress[$step->id]);
            $locked = !$completed && !$manager->is_step_available($step, $this->userid);
            $ismanual = (int) $step->type === manager::STEP_MANUAL;
            $islink = (int) $step->type === manager::STEP_LINK;

            $steps[] = (object) [
                'id' => $step->id,
                'number' => $number,
                'title' => format_string($step->title, true, ['context' => $context]),
                'description' => format_text($step->description, $step->descriptionformat, ['context' => $context]),
                'completed' => $completed,
                'locked' => $locked,
                'ismanual' => $ismanual,
                'islink' => $islink,
                'iscallback' => (int) $step->type === manager::STEP_CALLBACK,
                'manualinteractive' => $ismanual && $cancomplete && !$locked,
                'gourl' => ($islink && !$locked) ? $manager->get_go_url($step)->out(false) : '',
                'linktarget' => $linktarget,
            ];
        }

        $summary = $manager->get_progress_summary($this->userid);

        return (object) [
            'cmid' => $cm->id,
            'hassteps' => !empty($steps),
            'steps' => $steps,
            'cancomplete' => $cancomplete,
            'progresstext' => get_string('progress', 'browse', $summary),
            'progresspercent' => $summary->total ? (int) round(100 * $summary->complete / $summary->total) : 0,
        ];
    }
}
