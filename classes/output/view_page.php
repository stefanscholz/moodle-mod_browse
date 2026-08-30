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
use mod_browse\local\url_helper;
use renderable;
use renderer_base;
use templatable;

/**
 * The browse activity view page: external content plus the steps panel.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_page implements renderable, templatable {

    /** @var manager the activity manager */
    private manager $manager;

    /** @var int the viewing user */
    private int $userid;

    /**
     * Constructor.
     *
     * @param manager $manager the activity manager
     * @param int $userid the viewing user
     */
    public function __construct(manager $manager, int $userid) {
        $this->manager = $manager;
        $this->userid = $userid;
    }

    /**
     * Export the page data for the template.
     *
     * @param renderer_base $output the renderer
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $manager = $this->manager;
        $browse = $manager->get_instance();
        $context = $manager->get_context();

        $contenturl = url_helper::get_full_url($browse, $manager->get_cm(), $manager->get_course());
        $panel = new steps_panel($manager, $this->userid);

        return (object) [
            'embed' => $manager->is_embedded(),
            'contenturl' => $contenturl,
            'contenttitle' => format_string($browse->name, true, ['context' => $context]),
            'panelhtml' => $output->render($panel),
        ];
    }
}
