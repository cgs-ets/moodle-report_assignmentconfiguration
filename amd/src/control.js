/* eslint-disable no-unused-vars */
/* eslint-disable require-jsdoc */
/* eslint-disable jsdoc/require-jsdoc */
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
 *
 * @package    report
 * @subpackage ibassessmentreport
 * @copyright  2021 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/log", "core/templates"], function ($, Ajax, Log, Templates) {
    "use strict";

    function init() {
        var control = new Controls();
        control.main();
    }

    function Controls() {
        let self = this;
        self.categorySelection = document.getElementById('id_gradecategoriesselect');
        self.assessmentSelection = document.getElementById('id_assignselected');
        self.categorySelectionJSON = document.getElementById('id_selectedcategoriesJSON').value;
    }

    /**
     * Run the controller.
     *
     */
    Controls.prototype.main = function () {
        let self = this;
        self.initEventListeners();

    };

    Controls.prototype.initEventListeners = function () {
        let self = this;
        self.categorySelection.addEventListener('change', this.getAssignmentsForCategory.bind(this));


    };

    Controls.prototype.getAssignmentsForCategory = function (e) {
        let categorySelectionJSON = JSON.parse(this.categorySelectionJSON);

        // Get the selected options
        let selectedOptions = [];
        for (var i = 0; i < this.categorySelection.options.length; i++) {
            if (this.categorySelection.options[i].selected) {
                selectedOptions.push(this.categorySelection.options[i].value);
            }
        }

        // Update the courseSelectionJSON array
        document.getElementById('id_selectedcategoriesJSON').value = JSON.stringify(selectedOptions)
        // Update the input json

        this.getAssessmentsByCategory();


    }


    Controls.prototype.getAssessmentsByCategory = function () {
        var self = this;
        Ajax.call([{
            methodname: 'report_assignmentconfiguration_get_assign_by_category',
            args: {
                data: document.getElementById('id_selectedcategoriesJSON').value,
                course: document.querySelector('#region-main').querySelector('form').querySelector('input[name="id"]').value
            },
            done: function (response) {
                var templatecontext = JSON.parse(response.templatecontext);

                var context = {
                    items: templatecontext.items,
                }

                Templates.render('report_assignmentconfiguration/assignments_in_category', context)
                    .then(function (html, js) {
                        //report-assignmentconfiguration-assignments-container
                        Templates.replaceNodeContents('.report-assignmentconfiguration-assignments-container', html, js);
                        self.setListenerForActivities();
                    })
                    .catch(function (error) {
                        console.error('Error rendering template:', error);
                    });
            },
            fail: function (reason) {
                console.log(reason);
            }

        }])

    };

    Controls.prototype.setListenerForActivities = function () {
        var selector = document.getElementById('id_assignselected');
        selector.addEventListener('change', this.setAssessmentJSON.bind(this));
    }

    Controls.prototype.setAssessmentJSON = function (e) {
        var self = this;
        // Get the selected options
        let selectedOptions = [];
        let assessmentSelection = document.getElementById('id_assignselected');

        for (var i = 0; i < assessmentSelection.options.length; i++) {
            if (assessmentSelection.options[i].selected) {
                selectedOptions.push(assessmentSelection.options[i].value);
            }
        }

        // Update the courseSelectionJSON array
        document.getElementById('id_selectedassessmentsJSON').value = JSON.stringify(selectedOptions)
        // Update the input json

    }



    return {
        init: init
    };
});