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
 *
 * @package    report_assignmentconfiguration
 * @copyright  2021 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once('assignmentconfiguration_form.php');


$id                      = optional_param('id', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.


$url = new moodle_url('/report/assignmentconfiguration/index.php', ['id' => $id, 'cmid' => $cmid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('report_assignmentconfiguration');

if (!$course = $DB->get_record('course', ['id' => $id])) {
    $message = get_string('invalidcourse', 'report_assignfeedback_download');
    $level = core\output\notification::NOTIFY_ERROR;
    \core\notification::add($message, $level);
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('report/assignmentconfiguration:grade', $context);
// Display the backup report.
$PAGE->set_title(format_string($course->shortname, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
echo $OUTPUT->header();

$manager = new report_assignmentconfiguration\manager();

$assigments = $manager::get_assessments($id);
$mform = new assignmentconfiguration_form(null, ['id' => $id, 'cmid' => $cmid, 'assignments' => $assigments]);

if ($data = $mform->get_data()) {

}


echo $OUTPUT->box_start();

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

