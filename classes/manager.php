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
/**
 * Class to manage the plugin
 */
class manager {

    public static function get_assessments($course) {
        global $DB;

        $sql = 'SELECT *
                FROM {assign} WHERE course = :course';

        $r = $DB->get_records_sql($sql, ['course' => $course]);

        return $r;

    }

    public static function get_report($course, $assignments, $assignid) {
        global $DB;
        // Get general information already fetched
        $assignment = json_decode($assignments)[$assignid];

    }

    private static function get_submissionandfeedbacktype_configuration($assignid){
        global $DB;

        $sql = 'SELECT plugin, subtype
                FROM {assign_plugin_config}
                WHERE assignment = :assignment
                AND value <> 0';

        $r = $DB->get_records_sql($sql, ['assignment' => $assignid]);

    }

    private static function get_grade_and_outcome_configuration($course, $assignid, $assignname) {
        global $DB;

        $sql = 'SELECT gi.*, gc.fullname
                FROM mdl_grade_items gi
                JOIN mdl_grade_categories gc ON gi.categoryid = gc.id
                WHERE gi.courseid = :course AND iteminstance = :iteminstance AND gi.itemname = :itemname';
        // You will get the itemname with the name of the assignment and if there is more than one row.
        //  It will be the  outcome.
        $params = ['course' => $course, 'iteminstance' => $assignid];

        $r = $DB->get_records_sql($sql, $params);

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
        $r = $DB->get_records_sql($sql, $params);

    }

}
