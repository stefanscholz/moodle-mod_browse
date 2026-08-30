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

namespace mod_browse\local;

/**
 * URL handling for mod_browse: validation, fixing and variable substitution.
 *
 * The variable substitution follows the behaviour of mod_url so that teachers
 * familiar with the URL activity can use the same parameters here.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_helper {
    /**
     * Weak url validation, looking for major problems only.
     *
     * @param string $url the URL to check
     * @return bool true if it seems valid, false if definitely not valid
     */
    public static function appears_valid_url(string $url): bool {
        if (preg_match('/^(\/|https?:|ftp:)/i', $url)) {
            // Note: this is not exact validation, we look for severely malformed URLs only.
            return (bool) preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[^ @]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $url);
        }
        return (bool) preg_match('/^[a-z]+:\/\/...*$/i', $url);
    }

    /**
     * Fix common URL problems that we want teachers to see fixed the next time they edit the activity.
     *
     * This function does not include any XSS protection.
     *
     * @param string $url the submitted URL
     * @return string the fixed URL
     */
    public static function fix_submitted_url(string $url): string {
        $url = trim($url);

        // Remove encoded entities - we want the raw URI here.
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        if (!preg_match('|^[a-z]+:|i', $url) && !preg_match('|^/|', $url)) {
            // Invalid URI, try to fix it by making it a normal URL.
            $url = 'http://' . $url;
        }

        return $url;
    }

    /**
     * Encode a parameter map for storage.
     *
     * @param array $parameters map of parameter name => variable name
     * @return string JSON encoded parameters
     */
    public static function encode_parameters(array $parameters): string {
        return json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decode a stored parameter map.
     *
     * @param string|null $encoded the stored value
     * @return array map of parameter name => variable name
     */
    public static function decode_parameters(?string $encoded): array {
        if ($encoded === null || $encoded === '') {
            return [];
        }
        $decoded = json_decode($encoded, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Collect the repeated parameter_N / variable_N form fields into an encoded parameter map.
     *
     * @param \stdClass $data the submitted form data
     * @return string|null JSON encoded parameters, or null if the data does not carry the form fields
     */
    public static function parameters_from_formdata(\stdClass $data): ?string {
        if (!property_exists($data, 'parameter_0')) {
            return null;
        }
        $parameters = [];
        for ($i = 0; $i < 100; $i++) {
            $parameter = "parameter_$i";
            $variable = "variable_$i";
            if (!property_exists($data, $parameter)) {
                break;
            }
            $name = trim((string) $data->$parameter);
            if ($name !== '' && !empty($data->$variable)) {
                $parameters[$name] = $data->$variable;
            }
        }
        return self::encode_parameters($parameters);
    }

    /**
     * Get the variables that may be appended to URLs, grouped for a selectgroups form element.
     *
     * @return array array describing opt groups
     */
    public static function get_variable_options(): array {
        $options = [];
        $options[''] = ['' => get_string('chooseavariable', 'browse')];

        $options[get_string('course')] = [
            'courseid' => 'id',
            'coursefullname' => get_string('fullnamecourse'),
            'courseshortname' => get_string('shortnamecourse'),
            'courseidnumber' => get_string('idnumbercourse'),
            'courseformat' => get_string('format'),
        ];

        $options[get_string('modulename', 'browse')] = [
            'browseinstance' => 'id',
            'browsecmid' => 'cmid',
            'browsename' => get_string('name'),
            'browseidnumber' => get_string('idnumbermod'),
        ];

        $options[get_string('miscellaneous')] = [
            'sitename' => get_string('fullsitename'),
            'serverurl' => get_string('serverurl', 'browse'),
            'currenttime' => get_string('time'),
            'lang' => get_string('language'),
        ];

        $options[get_string('user')] = [
            'userid' => 'id',
            'userusername' => get_string('username'),
            'useridnumber' => get_string('idnumber'),
            'userfirstname' => get_string('firstname'),
            'userlastname' => get_string('lastname'),
            'userfullname' => get_string('fullnameuser'),
            'useremail' => get_string('email'),
            'userinstitution' => get_string('institution'),
            'userdepartment' => get_string('department'),
            'usercity' => get_string('city'),
            'usertimezone' => get_string('timezone'),
        ];

        return $options;
    }

    /**
     * Resolve the current values of all URL variables.
     *
     * @param \stdClass $browse the browse instance record
     * @param \cm_info|\stdClass $cm the course module
     * @param \stdClass $course the course record
     * @return array map of variable name => value
     */
    public static function get_variable_values(\stdClass $browse, $cm, \stdClass $course): array {
        global $USER, $CFG;

        $site = get_site();
        $coursecontext = \context_course::instance($course->id);

        $values = [
            'courseid' => $course->id,
            'coursefullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
            'courseshortname' => format_string($course->shortname, true, ['context' => $coursecontext]),
            'courseidnumber' => $course->idnumber,
            'courseformat' => $course->format,
            'browseinstance' => $browse->id,
            'browsecmid' => $cm->id,
            'browsename' => format_string($browse->name, true, ['context' => $coursecontext]),
            'browseidnumber' => $cm->idnumber,
            'sitename' => format_string($site->fullname, true, ['context' => $coursecontext]),
            'serverurl' => $CFG->wwwroot,
            'currenttime' => time(),
            'lang' => current_language(),
        ];

        if (isloggedin() && !isguestuser()) {
            $now = new \DateTime('now', \core_date::get_user_timezone_object());
            $values += [
                'userid' => $USER->id,
                'userusername' => $USER->username,
                'useridnumber' => $USER->idnumber,
                'userfirstname' => $USER->firstname,
                'userlastname' => $USER->lastname,
                'userfullname' => fullname($USER),
                'useremail' => $USER->email,
                'userinstitution' => $USER->institution,
                'userdepartment' => $USER->department,
                'usercity' => $USER->city,
                'usertimezone' => $now->getOffset() / 3600.0,
            ];
        }

        return $values;
    }

    /**
     * Append the configured parameters with their resolved values to a URL.
     *
     * @param string $url the base URL
     * @param array $parameters map of parameter name => variable name
     * @param array $values map of variable name => value
     * @return string the URL including the parameters
     */
    public static function append_parameters(string $url, array $parameters, array $values): string {
        $pairs = [];
        foreach ($parameters as $parameter => $variable) {
            if (isset($values[$variable])) {
                $pairs[] = rawurlencode((string) $parameter) . '=' . rawurlencode((string) $values[$variable]);
            }
        }

        if (!$pairs) {
            return $url;
        }

        $join = (strpos($url, '?') === false) ? '?' : '&';
        return $url . $join . implode('&', $pairs);
    }

    /**
     * Sanitise a URL for output, encoding characters that commonly break URLs.
     *
     * This does not make the URL valid in all cases, but it helps with some
     * UTF-8 and copy-paste problems. It is not an XSS protection.
     *
     * @param string $url the URL
     * @return string the cleaned URL
     */
    protected static function clean_url(string $url): string {
        // Make sure there are no encoded entities, it is ok to do this twice.
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        return str_replace(
            ['"', '\'', ' ', '<', '>'],
            ['%22', '%27', '%20', '%3C', '%3E'],
            $url
        );
    }

    /**
     * Get the full external URL of the activity including all configured parameters.
     *
     * This function does not include any XSS protection.
     *
     * @param \stdClass $browse the browse instance record
     * @param \cm_info|\stdClass $cm the course module
     * @param \stdClass $course the course record
     * @return string the full URL
     */
    public static function get_full_url(\stdClass $browse, $cm, \stdClass $course): string {
        return self::build_url((string) $browse->externalurl, $browse, $cm, $course);
    }

    /**
     * Get the full URL of a step including all configured activity-level parameters.
     *
     * This function does not include any XSS protection.
     *
     * @param \stdClass $step the step record
     * @param \stdClass $browse the browse instance record
     * @param \cm_info|\stdClass $cm the course module
     * @param \stdClass $course the course record
     * @return string the full URL, or an empty string if the step has no URL
     */
    public static function get_step_url(\stdClass $step, \stdClass $browse, $cm, \stdClass $course): string {
        if (empty($step->url)) {
            return '';
        }
        return self::build_url((string) $step->url, $browse, $cm, $course);
    }

    /**
     * Clean a URL and append the activity's configured parameters.
     *
     * @param string $url the base URL
     * @param \stdClass $browse the browse instance record
     * @param \cm_info|\stdClass $cm the course module
     * @param \stdClass $course the course record
     * @return string the full URL
     */
    protected static function build_url(string $url, \stdClass $browse, $cm, \stdClass $course): string {
        $url = self::clean_url($url);

        $parameters = self::decode_parameters($browse->parameters ?? null);
        if ($parameters) {
            $url = self::append_parameters($url, $parameters, self::get_variable_values($browse, $cm, $course));
        }

        return $url;
    }
}
