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

namespace mod_browse\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the url_helper class.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(url_helper::class)]
final class url_helper_test extends \advanced_testcase {
    /**
     * Common URL mistakes are fixed on submission.
     */
    public function test_fix_submitted_url(): void {
        $this->assertSame('http://example.com/path', url_helper::fix_submitted_url(' example.com/path '));
        $this->assertSame('https://example.com/?a=1&b=2', url_helper::fix_submitted_url('https://example.com/?a=1&amp;b=2'));
        $this->assertSame('https://example.com', url_helper::fix_submitted_url('https://example.com'));
    }

    /**
     * Weak URL validation accepts common URLs and rejects garbage.
     */
    public function test_appears_valid_url(): void {
        $this->assertTrue(url_helper::appears_valid_url('https://example.com/some/path?x=1#frag'));
        $this->assertTrue(url_helper::appears_valid_url('http://localhost:8080/'));
        $this->assertFalse(url_helper::appears_valid_url('https://'));
        $this->assertFalse(url_helper::appears_valid_url('this is not a url'));
    }

    /**
     * Parameters survive an encode/decode round trip and bad data decodes to an empty array.
     */
    public function test_encode_decode_parameters(): void {
        $parameters = ['uid' => 'userid', 'course' => 'courseshortname'];
        $this->assertSame($parameters, url_helper::decode_parameters(url_helper::encode_parameters($parameters)));
        $this->assertSame([], url_helper::decode_parameters(null));
        $this->assertSame([], url_helper::decode_parameters(''));
        $this->assertSame([], url_helper::decode_parameters('not json'));
    }

    /**
     * The repeated form fields are collected into an encoded parameter map.
     */
    public function test_parameters_from_formdata(): void {
        $data = (object) [
            'parameter_0' => 'uid',
            'variable_0' => 'userid',
            'parameter_1' => '',
            'variable_1' => 'courseid',
            'parameter_2' => 'course',
            'variable_2' => 'courseshortname',
        ];
        $this->assertSame(
            ['uid' => 'userid', 'course' => 'courseshortname'],
            url_helper::decode_parameters(url_helper::parameters_from_formdata($data))
        );

        // Data that does not come from the form leaves the stored parameters alone.
        $this->assertNull(url_helper::parameters_from_formdata((object) ['name' => 'No form fields']));
    }

    /**
     * Parameters are appended with the correct separator and encoding; unknown variables are dropped.
     */
    public function test_append_parameters(): void {
        $values = ['userid' => 7, 'coursefullname' => 'Fun & games'];

        $this->assertSame(
            'https://example.com/?uid=7',
            url_helper::append_parameters('https://example.com/', ['uid' => 'userid'], $values)
        );
        $this->assertSame(
            'https://example.com/?x=1&uid=7',
            url_helper::append_parameters('https://example.com/?x=1', ['uid' => 'userid'], $values)
        );
        $this->assertSame(
            'https://example.com/?c=Fun%20%26%20games',
            url_helper::append_parameters('https://example.com/', ['c' => 'coursefullname'], $values)
        );
        $this->assertSame(
            'https://example.com/',
            url_helper::append_parameters('https://example.com/', ['x' => 'nosuchvariable'], $values)
        );
        $this->assertSame(
            'https://example.com/',
            url_helper::append_parameters('https://example.com/', [], $values)
        );
    }

    /**
     * The variable options contain every variable that get_variable_values can resolve.
     */
    public function test_variable_options_match_values(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $browse = $generator->create_module('browse', ['course' => $course->id]);
        [$course, $cm] = get_course_and_cm_from_cmid($browse->cmid, 'browse');

        $available = [];
        foreach (url_helper::get_variable_options() as $group) {
            $available = array_merge($available, array_keys($group));
        }
        $available = array_filter($available); // Drop the empty "choose" entry.

        $values = url_helper::get_variable_values($browse, $cm, $course);
        foreach ($available as $variable) {
            $this->assertArrayHasKey($variable, $values, "No value resolved for variable '$variable'");
        }
    }

    /**
     * The full URL of the activity carries the configured parameters with resolved values.
     */
    public function test_get_full_url(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'C-BROWSE']);
        $user = $generator->create_user();
        $this->setUser($user);

        $browse = $generator->create_module('browse', [
            'course' => $course->id,
            'externalurl' => 'https://example.com/survey',
            'parameters' => url_helper::encode_parameters(['uid' => 'userid', 'course' => 'courseshortname']),
        ]);
        [$course, $cm] = get_course_and_cm_from_cmid($browse->cmid, 'browse');

        $this->assertSame(
            "https://example.com/survey?uid={$user->id}&course=C-BROWSE",
            url_helper::get_full_url($browse, $cm, $course)
        );
    }

    /**
     * Step URLs carry the activity-level parameters as well.
     */
    public function test_get_step_url(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $this->setUser($user);

        $browse = $generator->create_module('browse', [
            'course' => $course->id,
            'parameters' => url_helper::encode_parameters(['uid' => 'userid']),
        ]);
        [$course, $cm] = get_course_and_cm_from_cmid($browse->cmid, 'browse');

        $step = (object) ['url' => 'https://example.com/page2'];
        $this->assertSame(
            "https://example.com/page2?uid={$user->id}",
            url_helper::get_step_url($step, $browse, $cm, $course)
        );
    }
}
