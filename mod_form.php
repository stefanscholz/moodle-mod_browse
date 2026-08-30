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
 * Browse activity configuration form.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/resourcelib.php');

use mod_browse\local\url_helper;

/**
 * Browse activity configuration form definition.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_browse_mod_form extends moodleform_mod {
    /**
     * Form definition.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('url', 'externalurl', get_string('externalurl', 'browse'), ['size' => '60']);
        $mform->setType('externalurl', PARAM_RAW_TRIMMED);
        $mform->addRule('externalurl', null, 'required', null, 'client');
        $mform->addHelpButton('externalurl', 'externalurl', 'browse');

        $this->standard_intro_elements();

        $mform->addElement('header', 'optionssection', get_string('appearance'));

        $options = [
            RESOURCELIB_DISPLAY_EMBED => get_string('resourcedisplayembed'),
            RESOURCELIB_DISPLAY_NEW => get_string('resourcedisplaynew'),
        ];
        $mform->addElement('select', 'display', get_string('displayselect', 'browse'), $options);
        $mform->setDefault('display', RESOURCELIB_DISPLAY_EMBED);
        $mform->addHelpButton('display', 'displayselect', 'browse');

        $mform->addElement('header', 'stepssection', get_string('steps', 'browse'));
        $mform->addElement('advcheckbox', 'sequential', get_string('sequential', 'browse'));
        $mform->addHelpButton('sequential', 'sequential', 'browse');

        $mform->addElement('header', 'parameterssection', get_string('parametersheader', 'browse'));
        $mform->addElement('static', 'parametersinfo', '', get_string('parametersheader_help', 'browse'));

        $currentparameters = [];
        if (!empty($this->current->parameters)) {
            $currentparameters = url_helper::decode_parameters($this->current->parameters);
        }
        $parcount = min(100, 5 + count($currentparameters));
        $variables = url_helper::get_variable_options();

        for ($i = 0; $i < $parcount; $i++) {
            $group = [
                $mform->createElement('text', "parameter_$i", '', ['size' => '12']),
                $mform->createElement('selectgroups', "variable_$i", '', $variables),
            ];
            $mform->addGroup($group, "pargroup_$i", get_string('parameterinfo', 'browse'), ' ', false);
            $mform->setType("parameter_$i", PARAM_RAW);
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Load the stored parameters into the repeated form fields.
     *
     * @param array $defaultvalues the default values
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if (!empty($defaultvalues['parameters'])) {
            $i = 0;
            foreach (url_helper::decode_parameters($defaultvalues['parameters']) as $parameter => $variable) {
                $defaultvalues["parameter_$i"] = $parameter;
                $defaultvalues["variable_$i"] = $variable;
                $i++;
            }
        }
    }

    /**
     * Validate the submitted URL.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['externalurl'])) {
            if (!url_helper::appears_valid_url(url_helper::fix_submitted_url($data['externalurl']))) {
                $errors['externalurl'] = get_string('invalidurl', 'browse');
            }
        }

        return $errors;
    }

    /**
     * Add the custom completion rule to the form.
     *
     * @return array names of the added form elements
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $completionstepsel = 'completionsteps' . $suffix;
        $mform->addElement('checkbox', $completionstepsel, '', get_string('completionsteps', 'browse'));
        $mform->addHelpButton($completionstepsel, 'completionsteps', 'browse');
        // Enable the rule by default for new instances.
        $mform->setDefault($completionstepsel, empty($this->_instance) ? 1 : 0);

        return [$completionstepsel];
    }

    /**
     * Determine whether the custom completion rule is enabled.
     *
     * @param array $data form data
     * @return bool true if the rule is enabled
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsteps' . $this->get_suffix()]);
    }

    /**
     * Make sure unticked checkboxes are stored as 0.
     *
     * @param stdClass $data the submitted form data
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix};
            $autocompletion = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{'completionsteps' . $suffix}) || !$autocompletion) {
                $data->{'completionsteps' . $suffix} = 0;
            }
        }
    }
}
