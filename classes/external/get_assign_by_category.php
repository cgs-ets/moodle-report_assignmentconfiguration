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
 *  External Web Service Template
 *
 * @package   report_assignmentconfiguration
 * @copyright 2024 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_assignmentconfiguration\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use report_reflectionexporter\reflectionexportermanager;

require_once($CFG->libdir . '/externallib.php');

trait get_assign_by_category {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     *
     */
    public static function get_assign_by_category_parameters() {
        return new external_function_parameters([
            'data' => new external_value(PARAM_RAW, 'JSON with the assignids to get the data'),
            'course' => new external_value(PARAM_RAW, 'course id'),
        ]);
    }

    public static function get_assign_by_category($data, $course) {
        global $COURSE;

        $context = \context_user::instance($COURSE->id);
        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::get_assign_by_category_parameters(),
            ['data' => $data, 'course' => $course]
        );


        $categoryids = implode(',', json_decode($data));
        $manager = new \report_assignmentconfiguration\manager();
        $assigments = $manager::get_assessments_by_category($course, $categoryids);
        error_log(print_r("ASSIGN", true));
        error_log(print_r($assigments, true));
        $items = [];
        foreach($assigments as $assign) {
            $items['items'][] =  $assign;
        }

        return ['templatecontext' => json_encode($items)];
    }

    /**
     * Describes the structure of the function return value.
     * Returns the URL of the file for the student
     * @return external_single_structure
     *
     */
    public static function get_assign_by_category_returns() {
          return new external_single_structure([ 'templatecontext' =>
                                                new external_value(PARAM_RAW, 'Context for the mustache template'), ]
        );
    }
}
