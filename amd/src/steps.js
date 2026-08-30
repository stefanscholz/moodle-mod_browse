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
 * Interactivity of the browse steps panel: ticking manual steps off without reloading the page.
 *
 * @module     mod_browse/steps
 * @copyright  2026 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';

const Selectors = {
    toggle: '[data-mod-browse-toggle]',
    panel: '[data-region="mod_browse-steps-panel"]',
};

/**
 * Call the toggle_step web service.
 *
 * @param {number} cmid the course module id
 * @param {number} stepid the step id
 * @param {boolean} completed the new completion state
 * @returns {Promise} resolving to the web service response
 */
const toggleStep = (cmid, stepid, completed) => fetchMany([{
    methodname: 'mod_browse_toggle_step',
    args: {cmid, stepid, completed},
}])[0];

/**
 * Initialise the steps panel for one browse activity page.
 *
 * @param {number} cmid the course module id
 */
export const init = (cmid) => {
    document.addEventListener('change', async(e) => {
        const checkbox = e.target.closest(Selectors.toggle);
        if (!checkbox) {
            return;
        }
        const panel = checkbox.closest(Selectors.panel);
        checkbox.disabled = true;
        try {
            const response = await toggleStep(cmid, parseInt(checkbox.dataset.stepid, 10), checkbox.checked);
            panel.outerHTML = response.panelhtml;
        } catch (error) {
            Notification.exception(error);
        }
    });
};
