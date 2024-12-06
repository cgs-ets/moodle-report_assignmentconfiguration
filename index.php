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

use function report_assignmentconfiguration\test;

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/report/assignmentconfiguration/classes/reportxsl.php');
require_once('lib.php');
require_once('assignmentconfiguration_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course ID.
$cmid = optional_param('cmid', 0, PARAM_INT); // Course module ID.
$data = data_submitted();

if (!$course = $DB->get_record('course', ['id' => $id])) {
    $message = get_string('invalidcourse', 'report_assignfeedback_download');
    $level = core\output\notification::NOTIFY_ERROR;
    \core\notification::add($message, $level);
}

$error = false;

if ($data && confirm_sesskey() ) {
    if (isset($data->downloadbutton) && $data->selectedassessmentsJSON != '[]') {
        $PAGE->requires->js_call_amd('report_assignmentconfiguration/renable', 'init');
        $details = report_assignmentconfiguration\manager::get_report($id, $data->selectedassessmentsJSON);
        report_assignmentconfiguration\report_assignmentconfiguration_setup_workbook($details, $course, $cmid);

    } else if ((isset($data->getbutton) && $data->selectedassessmentsJSON == '[]')
     || isset($data->downloadbutton) && $data->selectedassessmentsJSON == '[]') {
        $error = true;
        $message = get_string('select:assessment_error', 'report_assignmentconfiguration');
        $level = core\output\notification::NOTIFY_ERROR;
        \core\notification::add($message, $level);
    }
}

$url = new moodle_url('/report/assignmentconfiguration/index.php', ['id' => $id, 'cmid' => $cmid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'report_assignmentconfiguration'));
$PAGE->add_body_class('report_assignmentconfiguration');


require_login($course);
$context = context_course::instance($course->id);
require_capability('report/assignmentconfiguration:grade', $context);
$PAGE->set_context($context);
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

echo $OUTPUT->header();

$manager = new report_assignmentconfiguration\manager();

$categories = $manager::get_grade_categories($id);

$mform = new assignmentconfiguration_form(null, ['id' => $id, 'cmid' => $cmid, 'gradecategories' => $categories]);
$filter = false;
// var_dump($data);
if ($data = $mform->get_data()) {
    $filter = true;

    if ($data->getbutton && $data->selectedassessmentsJSON != '[]') {
        $details = $manager::get_report($id, $data->selectedassessmentsJSON);
        echo $OUTPUT->render_from_template('report_assignmentconfiguration/report_view', $details);
    }
} else if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $id]));
}

echo $OUTPUT->box_start();


if (!$filter) {
    echo html_writer::start_tag('h3');
    echo get_string('pluginname', 'report_assignmentconfiguration');
    echo html_writer::end_tag('h3');
    echo html_writer::end_tag('br');

    $mform->display();
} else {
    echo $OUTPUT->render_from_template('report_assignmentconfiguration/goback', ['url' => $url]);
}

echo $OUTPUT->box_end();

// Call JS.
$PAGE->requires->js_call_amd('report_assignmentconfiguration/control', 'init');

echo $OUTPUT->footer();
