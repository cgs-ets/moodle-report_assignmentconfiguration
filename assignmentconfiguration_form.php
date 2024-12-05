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
        $mform->addElement('hidden', 'gradecategories', json_encode($this->_customdata['gradecategories']));
        $mform->settype('gradecategories', PARAM_TEXT); // To be able to pre-fill the form.

        // Grade Categories.
        $gradecategories = [];

        foreach ($this->_customdata['gradecategories'] as $id => $category) {
            $gradecategories[$id] = $category->fullname;
        }

        $mform->addElement('select', 'gradecategoriesselect', get_string('select:category', 'report_assignmentconfiguration'), $gradecategories);
        $mform->getElement('gradecategoriesselect')->setMultiple(true);
        $mform->addRule('gradecategoriesselect', '', 'required', null, 'server');
        $mform->setDefault('gradecategoriesselect', 0);

        // Generate a drop down  with the activities available in the category(ies) selected.
        $mform->addElement('text', 'selectedcategoriesJSON', 'Select category(ies) JSON');
        $mform->settype('selectedcategoriesJSON', PARAM_RAW);
        $mform->setDefault('selectedcategoriesJSON', '[]');

        $mform->addElement('html', '<div class="report-assignmentconfiguration-assignments-container"></div>');
        $mform->addElement('text', 'selectedassessmentsJSON', 'Select assessments JSON');

        $mform->settype('selectedassessmentsJSON', PARAM_RAW);
        $mform->setDefault('selectedassessmentsJSON', '[]');

        $buttonarray = [];

        $buttonarray[] = &$mform->createElement('submit', 'getbutton', get_string('report:get', 'report_assignmentconfiguration'));
        $buttonarray[] = &$mform->createElement('submit', 'downloadbutton', get_string('report:download', 'report_assignmentconfiguration'));
        $buttonarray[] = &$mform->createElement('cancel', 'canceltbutton');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->closeHeaderBefore('buttonar');

    }

    function validation($data, $files)    {
        $errors = parent::validation($data, $files);

        // if ($data['selectedassessmentsJSON'] == '[]') {
        //     $errors ['gradecategoriesselect'] = 'Select an assignment';
        // }

        return $errors;
    }

 }