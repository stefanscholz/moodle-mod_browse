# Browse activity for Moodle (mod_browse)

[![Moodle Plugin CI](https://github.com/stefanscholz/moodle-mod_browse/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/stefanscholz/moodle-mod_browse/actions/workflows/moodle-ci.yml)

The Browse activity lets a teacher embed external web content — like the URL
activity — and adds what the URL activity is missing: **guidance** and
**tracking**. The teacher defines a list of steps the student has to take in
the external content; the plugin records each student's progress per step and
offers a custom activity completion condition "all steps completed".

Typical uses:

- a step-by-step guide for applying or registering on an external site,
- confirming a student reached a specific page, such as the thank-you page of
  a survey or the "completed" page of external learning content.

## Features

- **External content like mod_url**: external URL, embedded in an iframe next
  to the steps or opened in a new window, with mod_url-style URL variables
  (course, user, activity and site fields) appended to the main URL and to
  every step URL.
- **Three step types**:
  - *Manual* — the student ticks the step off themselves (without a page
    reload, so embedded content keeps its state).
  - *By visiting a link* — the step has its own URL and completes as soon as
    the student opens it from the step list.
  - *By return link* — each step has a stable Moodle return URL; configure the
    external site to send the student there (e.g. a survey tool's redirect
    after submit, or a link on the final page). The arriving student is
    identified by their Moodle session, so no tokens leak to the external
    site.
- **Optional sequential mode**: steps must be completed in order; later steps
  stay locked until the previous ones are done.
- **Custom completion condition**: "Complete all steps", implemented with
  `core_completion\activity_custom_completion`, next to the standard "view"
  condition.
- **Step progress report** for teachers: a students × steps grid with
  completion times and group filtering.
- Full **backup/restore** (including user progress), **course reset**,
  **privacy API** (GDPR) support, events and capabilities.

## Requirements

- Moodle 5.2 (2026042000) or later.

## Installation

1. Copy the plugin into `mod/browse` of your Moodle installation
   (in Moodle 5.1+ with the public directory layout: `public/mod/browse`).
2. Visit *Site administration → Notifications* to complete the installation.

## Usage

1. Add a **Browse** activity to a course, set the external URL and choose the
   display mode (embedded or new window).
2. Optionally configure URL variables to append, and enable *Steps must be
   completed in order*.
3. Enable the completion condition *All steps must be completed* under
   Activity completion.
4. After saving, open **Manage steps** in the activity's secondary navigation
   and create the steps. For *return link* steps, copy the shown return link
   into the external tool (e.g. as its redirect-after-submit URL).
5. Follow your students' progress under **Step progress**.

## Development

The plugin is developed test-first. Run the unit tests with:

```bash
vendor/bin/phpunit --testsuite mod_browse_testsuite
```

and the Behat features with the `@mod_browse` tag. Continuous integration runs
[moodle-plugin-ci](https://moodlehq.github.io/moodle-plugin-ci/) against
Moodle 5.2 on PHP 8.3 and 8.4, including code style, PHPDoc, Mustache, Grunt,
PHPUnit and Behat checks.

## License

2026 bdecent GmbH <https://bdecent.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.
