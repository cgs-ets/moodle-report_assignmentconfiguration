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
 * Version metadata for the report_assignmentconfiguration plugin.
 *
 * @package   report_assignmentconfiguration
 * @copyright 2024, Veronica Bermegui <>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


 defined('MOODLE_INTERNAL') || die();

 require_once($CFG->libdir . '/formslib.php');

 class assignmentconfiguration_form extends moodleform {
    /**
     *Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->settype('id', PARAM_INT); // To be able to pre-fill the form.
        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->settype('cmid', PARAM_INT); // To be able to pre-fill the form.
        $mform->addElement('hidden', 'assignmentdetails', json_encode($this->_customdata['assignments']));
        $mform->settype('assignmentdetails', PARAM_TEXT); // To be able to pre-fill the form.


        $assessments = []; // Assessments in the course

        foreach($this->_customdata['assignments'] as $id => $assign) {
            $assessments[$id] = $assign->name;
        }


        $mform->addElement('select', 'assessmentsconfigreportselect', get_string('selectlabel', 'report_assignmentconfiguration'), $assessments);
        $mform->getElement('assessmentsconfigreportselect')->setMultiple(false);
        $mform->setDefault('assessmentsconfigreportselect', 0);

        $buttonarray = [];

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('getreport', 'report_assignmentconfiguration'));
        $buttonarray[] = &$mform->createElement('cancel', 'canceltbutton');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->closeHeaderBefore('buttonar');

    }

 }