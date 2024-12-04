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
 * @package   report_assignmentconfiguration
 * @copyright 2024, Veronica Bermegui <>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_assignmentconfiguration;
require_once('../../config.php');
/**
 * Class to manage the plugin
 */
class manager {

    const GRADE_TYPE = [ 'None',  'Point', 'Scale'];
    const SUBMISSION_ATTEMPT = [ 'none' => 'Never', 'manual' => 'Manually', 'untilpass' => 'Automatically until pass'];

    /**
     * Get the assessments for this course
     *
     * @param mixed $course
     */
    public static function get_assessments($course, $ids) {
        global $DB;

        $sql = "SELECT *
                FROM {assign}
                WHERE course = :course
                AND id IN ($ids)";

        $r = $DB->get_records_sql($sql, ['course' => $course]);

        return $r;

    }
    /**
     * Get the assessments for this course
     *
     * @param mixed $course
     */
    public static function get_assessments_by_category($course, $category) {
        global $DB;

        $sql = "SELECT iteminstance, itemname
                FROM {grade_items}
                WHERE courseid = :course
                AND itemmodule = :itemmodule
                AND categoryid IN ($category)";

        $r = $DB->get_records_sql($sql, ['course' => $course, 'itemmodule' => "assign"]);

        return $r;

    }
    /**
     * Get the grade categories for this course
     *
     * @param mixed $course
     * @return void
     */
    public static function get_grade_categories($course) {
        global $DB;

        $sql = 'SELECT * FROM mdl_grade_categories WHERE courseid = :courseid;';
        $params = ['courseid' => $course];

        $results = $DB->get_records_sql($sql, ['courseid' => $course]);


        foreach($results as $result) {
            if ($result->fullname == '?') {
                $result->fullname = 'Uncategorised';
            }
        }

        return $results;
    }

    /**
     *  Get the mustache context to generate the report view
     *
     * @param mixed $course
     * @param mixed $assignments
     * @param mixed $assignid
     * @param mixed $cmid
     */
    public static function get_report($course, $assignments) {
        global $DB;
        // Get general information already fetched.
        $assignments = json_decode($assignments, true);
        $assigmentsids = implode(',', $assignments);
        $assignments = self::get_assessments($course, $assigmentsids);

        $assignmentsdetails = [];

        foreach ($assignments as $assignid => $assignment) {

            $assign = $assignment; //$assignments[$assignid];
            $assign->allowsubmissionsfromdate = $assign->allowsubmissionsfromdate == 0 ? 'Not set' : userdate($assign->allowsubmissionsfromdate);
            $assign->cutoffdate = $assign->cutoffdate == 0 ? 'Not set' : userdate($assign->cutoffdate);
            $assign->duedate = $assign->duedate == 0 ? 'No' : userdate($assign->duedate);
            $assign->gradingduedate = $assign->gradingduedate == 0 ? 'Not set' : userdate($assign->gradingduedate);
            $assign->timelimit = $assign->timelimit == 0 ? 'Not set' : userdate($assign->timelimit);
            $assign->attemptreopenmethod = self::SUBMISSION_ATTEMPT[$assign->attemptreopenmethod];
            $assign->maxattempts = $assign->maxattempts == -1 ? 'Unlimited' : $assign->maxattempts;
            $config = self::get_submissionandfeedbacktype_configuration($assignid);
            $gradeandoutcomes = self::get_grade_and_outcome_configuration($course, $assignid, $assign);
            // error_log(print_r($gradeandoutcomes['grade'], true));
            $coursemoduleid = self::get_cmid($course, $assignid);
            $turnitinconfig = self::get_turnitin_config($course, $assignid, $coursemoduleid);
            $assign->editurl = new \moodle_url('/course/modedit.php', ['update' => $coursemoduleid, 'return' => 1]);
            $details = $assign;
            $details->grade_details = $gradeandoutcomes['grade'];
            $details->config = $config;
            $details->outcome = $gradeandoutcomes['outcome'];
            $details->plagiarism = $turnitinconfig;

            $assignmentsdetails['details'][] = $details;
        }

        return $assignmentsdetails;
    }

    /**
     * Get the  feedback and submission type configuration for the assigment
     *
     * @param mixed $assignid
     * @return void
     */
    private static function get_submissionandfeedbacktype_configuration($assignid) {
        global $DB;

        $sql = 'SELECT plugin, subtype
                FROM {assign_plugin_config}
                WHERE assignment = :assignment
                AND name = :stat
                AND value = 1
              ';

        $results = $DB->get_records_sql($sql, ['assignment' => $assignid, 'stat' => 'enabled']);
        $config = [];

        foreach ($results as $result) {
            $result->plugin = $result->plugin == 'editpdf' ? 'Annotate PDF' : $result->plugin;
            $result->plugin = $result->plugin == 'onlinetext' ? 'Online text' : $result->plugin;
            $config[$result->subtype][] = ucfirst($result->plugin); // Group the type = submission or feedback
        }

        if(is_null($config['assignsubmission'])) {
            $config['assignsubmission'][] = 'Not set';
        }

        if(is_null($config['assignfeedback'])) {
            $config['assignfeedback'][] = 'Not set';
        }

        return $config;

    }

    /**
     * Get the outcome configuration for this assignment
     */
    private static function get_grade_and_outcome_configuration($course, $assignid, $assign) {
        global $DB;

        $sql = 'SELECT gi.*, gc.fullname
                FROM mdl_grade_items gi
                JOIN mdl_grade_categories gc ON gi.categoryid = gc.id
                WHERE gi.courseid = :course AND iteminstance = :iteminstance';
        // You will get the itemname with the name of the assignment and if there is more than one row.
        //  It will be the  outcome.
        $params = ['course' => $course, 'iteminstance' => $assignid];

        $results = $DB->get_records_sql($sql, $params);
        $gradingmethod = self::get_grading_method($assignid);
        $gradeandoutcomes = [];
        $gradetype = '';

        foreach ($results as $result) {
            if ($result->itemname == $assign->name) { // Its the assignment itself
                $grade = new \stdClass();
                $gradetype = self::GRADE_TYPE[$result->gradetype]; // If there is outcome, they gradetype comes from there
                $grade->maxgrade = round($result->grademax);
                $grade->category = $result->fullname == '?' ? 'Uncategorised' : $result->fullname;
                $grade->gradepass = round($result->gradepass);
                $grade->method = empty($gradingmethod->activemethod) ? 'Simple grading method' : $gradingmethod->activemethod;
                $grade->annon = $assign->markinganonymous == 0 ? 'No' : 'Yes';
                $grade->hidegrader = $assign->hidegrader == 0 ? 'No' : 'Yes';
                $grade->markingworkflow = $assign->markingworkflow == 0 ? 'No' : 'Yes';
                $grade->markingworkflow = $assign->markingallocation == 0 ? 'No' : 'Yes';
                $gradeandoutcomes['grade'][] = $grade;
            } else {
                $outcome = new \stdClass();
                $outcome->name = $result->itemname;
                $gradeandoutcomes['outcome'][] = $outcome;

            }
        }

        if (is_null($gradeandoutcomes['outcome']) ) {
            $outcome = new \stdClass();
            $outcome->name = 'Not set';
            $gradeandoutcomes['outcome'][] = $outcome;
        }
        ($gradeandoutcomes['grade'][0])->type = $gradetype;

        return $gradeandoutcomes;

    }

    private static function get_grading_method($assignid) {
        global $DB;
        $sql = 'SELECT activemethod
                FROM mdl_grading_areas
                WHERE contextid = (SELECT ctx.id AS contextid
                                   FROM mdl_context ctx
                                   JOIN mdl_course_modules cm ON cm.id = ctx.instanceid
                                   JOIN mdl_modules m ON m.id = cm.module
                                   WHERE m.name = :name
                                   AND cm.instance = :instance
                                   AND ctx.contextlevel = :contextlevel);';

        $params = ['instance' => $assignid, 'name' => 'assign', 'contextlevel' => 70];

        $r = $DB->get_record_sql($sql, $params);
        $r->activemethod = $r->activemethod == 'frubric' ? 'Flexrubric' : ucfirst($r->activemethod);

        return $r;

    }

    private static function get_contextid($assignid) {
        global $DB;

        $sql = 'SELECT ctx.id AS contextid
                FROM mdl_context ctx
                JOIN mdl_course_modules cm ON cm.id = ctx.instanceid
                JOIN mdl_modules m ON m.id = cm.module
                WHERE m.name = :name
                AND cm.instance = :instance
                AND ctx.contextlevel = :contextlevel';

        $params = ['instance' => $assignid, 'name' => 'assign', 'contextlevel' => 70];

        $r = $DB->get_record_sql($sql, $params);

        return $r->contextid;
    }


    private static function get_cmid($course, $assignid ){
        global $DB;

        $sql = 'SELECT cm.id AS cmid
                FROM mdl_course_modules cm
                JOIN mdl_assign a ON cm.instance = a.id
                WHERE a.id = :assignment_id
                AND cm.course = :course_id;';

       $r =  $DB->get_record_sql($sql, ['assignment_id' => $assignid, 'course_id' => $course]);

       return $r->cmid;

    }

    private static function get_turnitin_config($course, $assignid, $cmid) {
        global $DB;

        $sql = 'SELECT *
                FROM {plagiarism_turnitin_config}
                WHERE cm = :cmid';

        $results =  $DB->get_records_sql($sql, ['cmid' => $cmid]);
        $turnitinconfig = new \stdClass();
        foreach ($results as $result ) {

            switch ($result->name) {
                case 'use_turnitin':
                    $turnitinconfig->use_turnitin = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_show_student_report':
                    $turnitinconfig->similarityreport = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_draft_submit':
                    $turnitinconfig->plagiarism_draft_submit = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_allow_non_or_submissions':
                    $turnitinconfig->plagiarism_allow_non_or_submissions = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_submitpapersto':
                    $turnitinconfig->plagiarism_submitpapersto = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_compare_student_papers':
                    $turnitinconfig->plagiarism_compare_student_papers = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_compare_internet':
                    $turnitinconfig->plagiarism_compare_internet = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_compare_journals':
                    $turnitinconfig->plagiarism_compare_journals = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_report_gen':
                    $turnitinconfig->plagiarism_report_gen = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_exclude_biblio':
                    $turnitinconfig->plagiarism_exclude_biblio = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_exclude_quoted':
                    $turnitinconfig->plagiarism_exclude_quoted = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_exclude_matches':
                    $turnitinconfig->plagiarism_exclude_matches = $result->value == 0 ? 'No' : 'Yes';
                    break;
                case 'plagiarism_exclude_matches_value':
                    $turnitinconfig->plagiarism_exclude_matches_value = $result->value;
                    break;
                case 'plagiarism_transmatch':
                    $turnitinconfig->plagiarism_transmatch = $result->value == 0 ? 'No' : 'Yes';
                    break;

            }


        }

        return $turnitinconfig;

    }


}
