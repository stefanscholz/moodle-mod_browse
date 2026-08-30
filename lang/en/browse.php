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
 * English language strings for mod_browse.
 *
 * @package    mod_browse
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addstep'] = 'Add step';
$string['browse:addinstance'] = 'Add a new browse activity';
$string['browse:completesteps'] = 'Complete steps of a browse activity';
$string['browse:managesteps'] = 'Manage steps of a browse activity';
$string['browse:view'] = 'View browse activity';
$string['browse:viewreport'] = 'View the browse step progress report';
$string['callbackurl'] = 'Return link';
$string['callbackurl_help'] = 'Configure the external site to send the student to this URL, for example as the redirect after submitting a survey or as a link on the final page. When the student arrives, the step is recorded as completed.';
$string['cannotrevokestep'] = 'Only manually completed steps can be marked as not done again.';
$string['chooseavariable'] = 'Choose a variable...';
$string['completedautomatically'] = 'Completed automatically when you reach the target page on the external site.';
$string['completiondetail:steps'] = 'Complete all steps';
$string['completionsteps'] = 'All steps must be completed';
$string['completionsteps_help'] = 'The activity is only considered completed once the student has completed every step.';
$string['deleteallprogress'] = 'Delete all step progress';
$string['deletestepconfirm'] = 'Are you sure you want to delete the step "{$a}"? Any student progress recorded for it will be deleted as well.';
$string['displayselect'] = 'Display';
$string['displayselect_help'] = '* Embed - The external content is shown on the activity page next to the list of steps.
* New window - The list of steps is shown on the activity page and the external content opens in a new browser window.';
$string['editstep'] = 'Edit step';
$string['eventstepcompleted'] = 'Browse step completed';
$string['externalurl'] = 'External URL';
$string['externalurl_help'] = 'The address of the external content that students should browse, for example https://example.com/survey.';
$string['invalidstep'] = 'Invalid step';
$string['invalidurl'] = 'Entered URL is invalid';
$string['managesteps'] = 'Manage steps';
$string['markdone'] = 'Mark step as done: {$a}';
$string['modulename'] = 'Browse';
$string['modulename_help'] = 'The browse activity lets a teacher embed external web content, guide students through it with a list of steps, and track that each student has taken every step.

Steps can be completed manually by the student, automatically when the student opens a step link, or automatically when the external site sends the student to a return link - for example the thank-you page of a survey.

Activity completion can require that all steps have been completed.';
$string['modulenameplural'] = 'Browse activities';
$string['nosteps'] = 'No steps have been created yet.';
$string['opencontent'] = 'Open content in new window';
$string['parameterinfo'] = '&amp;parameter=variable';
$string['parametersheader'] = 'URL variables';
$string['parametersheader_help'] = 'Some internal Moodle variables may be automatically appended to the URL. Type your name for the parameter into each text box(es) and then select the required matching variable. The variables are appended to the main URL and to every step URL.';
$string['pluginadministration'] = 'Browse module administration';
$string['pluginname'] = 'Browse';
$string['privacy:metadata:browse_progress'] = 'Information about the steps a user has completed in a browse activity';
$string['privacy:metadata:browse_progress:stepid'] = 'The step that was completed';
$string['privacy:metadata:browse_progress:timecompleted'] = 'The time when the user completed the step';
$string['privacy:metadata:browse_progress:userid'] = 'The user who completed the step';
$string['progress'] = '{$a->complete} of {$a->total} steps completed';
$string['report'] = 'Step progress';
$string['sequential'] = 'Steps must be completed in order';
$string['sequential_help'] = 'When enabled, a step only becomes available once all previous steps have been completed.';
$string['serverurl'] = 'Server URL';
$string['stepcompleted'] = 'Step completed';
$string['stepcompletedmessage'] = 'The step "{$a}" has been recorded as completed.';
$string['stepdescription'] = 'Description';
$string['stepdone'] = 'Done';
$string['steplocked'] = 'Locked';
$string['stepnotavailable'] = 'This step is not available yet. Please complete the previous steps first.';
$string['steps'] = 'Steps';
$string['steptitle'] = 'Title';
$string['steptype'] = 'How is the step completed?';
$string['steptype_help'] = '* Manually - The student ticks the step off themselves.
* By visiting a link - The step has its own URL and is completed as soon as the student opens it from the step list.
* By return link - The step is completed when the external site sends the student to the step\'s return link, for example after submitting a survey.';
$string['steptypecallback'] = 'By return link from the external site';
$string['steptypelink'] = 'By visiting a link';
$string['steptypemanual'] = 'Manually by the student';
$string['stepurl'] = 'Step URL';
$string['stepurl_help'] = 'The page of the external content this step refers to. The step is completed as soon as the student opens this link. Configured URL variables are appended.';
$string['stepvisit'] = 'Open step link';
$string['viewreport'] = 'View step progress';
