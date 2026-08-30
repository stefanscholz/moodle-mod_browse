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

namespace mod_browse\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_browse: step progress is personal data.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data stored by the plugin.
     *
     * @param collection $collection the metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('browse_progress', [
            'stepid' => 'privacy:metadata:browse_progress:stepid',
            'userid' => 'privacy:metadata:browse_progress:userid',
            'timecompleted' => 'privacy:metadata:browse_progress:timecompleted',
        ], 'privacy:metadata:browse_progress');

        return $collection;
    }

    /**
     * Get the contexts in which the user has step progress.
     *
     * @param int $userid the user id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {browse_progress} p
                  JOIN {browse_steps} s ON s.id = p.stepid
                  JOIN {browse} b ON b.id = s.browseid
                  JOIN {course_modules} cm ON cm.instance = b.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE p.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'modname' => 'browse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Get the users with step progress in a context.
     *
     * @param userlist $userlist the userlist to add to
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT p.userid
                  FROM {browse_progress} p
                  JOIN {browse_steps} s ON s.id = p.stepid
                  JOIN {browse} b ON b.id = s.browseid
                  JOIN {course_modules} cm ON cm.instance = b.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['modname' => 'browse', 'cmid' => $context->instanceid]);
    }

    /**
     * Export the user's completed steps for all approved contexts.
     *
     * @param approved_contextlist $contextlist the approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            if (!$cm = get_coursemodule_from_id('browse', $context->instanceid)) {
                continue;
            }

            $sql = "SELECT s.id, s.title, p.timecompleted
                      FROM {browse_progress} p
                      JOIN {browse_steps} s ON s.id = p.stepid
                     WHERE s.browseid = :browseid AND p.userid = :userid
                  ORDER BY s.sortorder ASC";
            $records = $DB->get_records_sql($sql, ['browseid' => $cm->instance, 'userid' => $userid]);
            if (!$records) {
                continue;
            }

            $steps = [];
            foreach ($records as $record) {
                $steps[] = [
                    'title' => format_string($record->title, true, ['context' => $context]),
                    'timecompleted' => transform::datetime((int) $record->timecompleted),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('steps', 'browse')],
                (object) ['steps' => $steps]
            );
        }
    }

    /**
     * Delete all step progress in a context.
     *
     * @param \context $context the context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        if (!$cm = get_coursemodule_from_id('browse', $context->instanceid)) {
            return;
        }

        $stepids = $DB->get_fieldset('browse_steps', 'id', ['browseid' => $cm->instance]);
        if ($stepids) {
            $DB->delete_records_list('browse_progress', 'stepid', $stepids);
        }
    }

    /**
     * Delete the user's step progress in all approved contexts.
     *
     * @param approved_contextlist $contextlist the approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            if (!$cm = get_coursemodule_from_id('browse', $context->instanceid)) {
                continue;
            }

            $stepids = $DB->get_fieldset('browse_steps', 'id', ['browseid' => $cm->instance]);
            if ($stepids) {
                [$insql, $params] = $DB->get_in_or_equal($stepids, SQL_PARAMS_NAMED);
                $params['userid'] = $userid;
                $DB->delete_records_select('browse_progress', "userid = :userid AND stepid $insql", $params);
            }
        }
    }

    /**
     * Delete the step progress of the approved users in a context.
     *
     * @param approved_userlist $userlist the approved users
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        if (!$cm = get_coursemodule_from_id('browse', $context->instanceid)) {
            return;
        }

        $stepids = $DB->get_fieldset('browse_steps', 'id', ['browseid' => $cm->instance]);
        $userids = $userlist->get_userids();
        if ($stepids && $userids) {
            [$stepsql, $params] = $DB->get_in_or_equal($stepids, SQL_PARAMS_NAMED, 'step');
            [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
            $DB->delete_records_select(
                'browse_progress',
                "stepid $stepsql AND userid $usersql",
                $params + $userparams
            );
        }
    }
}
