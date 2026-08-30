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

namespace mod_browse\form;

use mod_browse\local\manager;
use mod_browse\local\url_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to create or edit one step.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'step');
        $mform->setType('step', PARAM_INT);

        $mform->addElement('text', 'title', get_string('steptitle', 'browse'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('select', 'type', get_string('steptype', 'browse'), [
            manager::STEP_MANUAL => get_string('steptypemanual', 'browse'),
            manager::STEP_LINK => get_string('steptypelink', 'browse'),
            manager::STEP_CALLBACK => get_string('steptypecallback', 'browse'),
        ]);
        $mform->addHelpButton('type', 'steptype', 'browse');

        $mform->addElement('url', 'url', get_string('stepurl', 'browse'), ['size' => 60]);
        $mform->setType('url', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('url', 'stepurl', 'browse');
        $mform->hideIf('url', 'type', 'noteq', (string) manager::STEP_LINK);

        $mform->addElement('editor', 'description', get_string('stepdescription', 'browse'));
        $mform->setType('description', PARAM_RAW);

        $this->add_action_buttons();
    }

    /**
     * Validate the step: link steps need a valid URL.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ((int) $data['type'] === manager::STEP_LINK) {
            $url = url_helper::fix_submitted_url($data['url'] ?? '');
            if ($url === '' || $url === 'http://' || !url_helper::appears_valid_url($url)) {
                $errors['url'] = get_string('invalidurl', 'browse');
            }
        }

        return $errors;
    }
}
