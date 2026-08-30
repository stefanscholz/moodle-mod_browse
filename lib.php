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
 * Library of interface functions and constants for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * List of features supported by the browse module.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function browse_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Add a new browse instance.
 *
 * @param stdClass $data the form data
 * @param mod_browse_mod_form|null $mform the form
 * @return int the id of the new instance
 */
function browse_add_instance($data, $mform = null) {
    global $DB;

    $data->externalurl = \mod_browse\local\url_helper::fix_submitted_url($data->externalurl);
    $data->parameters = \mod_browse\local\url_helper::parameters_from_formdata($data) ?? ($data->parameters ?? '');
    $data->sequential = empty($data->sequential) ? 0 : 1;
    $data->timemodified = time();

    $data->id = $DB->insert_record('browse', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'browse', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update an existing browse instance.
 *
 * @param stdClass $data the form data
 * @param mod_browse_mod_form|null $mform the form
 * @return bool true
 */
function browse_update_instance($data, $mform = null) {
    global $DB;

    $data->externalurl = \mod_browse\local\url_helper::fix_submitted_url($data->externalurl);
    $data->parameters = \mod_browse\local\url_helper::parameters_from_formdata($data) ?? ($data->parameters ?? '');
    $data->sequential = empty($data->sequential) ? 0 : 1;
    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('browse', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'browse', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete a browse instance including its steps and user progress.
 *
 * @param int $id the instance id
 * @return bool true on success
 */
function browse_delete_instance($id) {
    global $DB;

    if (!$browse = $DB->get_record('browse', ['id' => $id])) {
        return false;
    }

    if ($cm = get_coursemodule_from_instance('browse', $id)) {
        \core_completion\api::update_completion_date_event($cm->id, 'browse', $id, null);
    }

    $stepids = $DB->get_fieldset('browse_steps', 'id', ['browseid' => $id]);
    if ($stepids) {
        $DB->delete_records_list('browse_progress', 'stepid', $stepids);
        $DB->delete_records('browse_steps', ['browseid' => $id]);
    }
    $DB->delete_records('browse', ['id' => $id]);

    return true;
}

/**
 * Add extra information to the course module info, most importantly the custom completion rules.
 *
 * @param stdClass $coursemodule the course module record
 * @return cached_cm_info|false info object, or false if the instance does not exist
 */
function browse_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completionsteps';
    if (!$browse = $DB->get_record('browse', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $browse->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('browse', $browse, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionsteps'] = $browse->completionsteps;
    }

    return $info;
}

/**
 * Extend the activity secondary navigation with the manage steps and report pages.
 *
 * @param settings_navigation $settings the settings navigation
 * @param navigation_node $node the browse module navigation node
 */
function browse_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    $cm = $settings->get_page()->cm;

    if (has_capability('mod/browse:managesteps', $cm->context)) {
        $node->add(
            get_string('managesteps', 'browse'),
            new moodle_url('/mod/browse/managesteps.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_browse_managesteps'
        );
    }
    if (has_capability('mod/browse:viewreport', $cm->context)) {
        $node->add(
            get_string('report', 'browse'),
            new moodle_url('/mod/browse/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_browse_report'
        );
    }
}

/**
 * Called by course/reset.php: build the browse part of the course reset form.
 *
 * @param MoodleQuickForm $mform the course reset form
 */
function browse_reset_course_form_definition($mform) {
    $mform->addElement('header', 'browseheader', get_string('modulenameplural', 'browse'));
    $mform->addElement('advcheckbox', 'reset_browse_progress', get_string('deleteallprogress', 'browse'));
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course the course
 * @return array the defaults
 */
function browse_reset_course_form_defaults($course) {
    return ['reset_browse_progress' => 1];
}

/**
 * Delete all user step progress in the given course.
 *
 * @param stdClass $data the data submitted from the reset course form
 * @return array status array
 */
function browse_reset_userdata($data) {
    global $DB;

    $status = [];

    if (!empty($data->reset_browse_progress)) {
        $DB->delete_records_select(
            'browse_progress',
            'stepid IN (SELECT s.id
                          FROM {browse_steps} s
                          JOIN {browse} b ON b.id = s.browseid
                         WHERE b.course = ?)',
            [$data->courseid]
        );

        $status[] = [
            'component' => get_string('modulenameplural', 'browse'),
            'item' => get_string('deleteallprogress', 'browse'),
            'error' => false,
        ];
    }

    return $status;
}
