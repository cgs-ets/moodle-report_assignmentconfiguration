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
 * Exports an Excel spreadsheet of the component grades in a frubric-graded assignment.
 *
 * @package    report_assignmentconfiguration
 * @copyright  2024 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace report_assignmentconfiguration;

require_once($CFG->dirroot.'/lib/excellib.class.php');
require_once($CFG->dirroot . '/report/assignmentconfiguration/classes/xslmanager.php');

use AssignmentConfigurationExcelWorkbook;
use MoodleExcelWorksheet;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

const HEADINGSROW = 4;
const HEADINGTITLES = ['size' => 12, 'bold' => 1, 'text_wrap' => true, 'align' => 'centre'];
const HEADINGSUBTITLES = ['bold' => 1, 'text_wrap' => true, 'align' => 'fill'];


function report_assignmentconfiguration_setup_workbook($details, $course, $cmid) {
    global $DB;

    $filename       = $course->shortname . '_AssignmentConfigurationSetup.xls';
    $tempdir        = make_temp_directory('report_assignmentconfiguration/excel');
    $workbook       = new AssignmentConfigurationExcelWorkbook("-");

    $workbook->send($filename);

    report_assignmentconfiguration_get_report($workbook, $details, $course);
    $tempdir = make_temp_directory('report_assignmentconfiguration/excel');
    $workbook->savetotempdir($tempdir);

    zip_excelworkbook($course->id, $cmid);

}
/**
 * Undocumented function
 *
 * @param mixed $workbook
 * @param mixed $assessmentdetails
 * @param mixed $course
 * @return void
 */
function report_assignmentconfiguration_get_report($workbook, $assessmentdetails, $course) {

    foreach ($assessmentdetails as $assessments) {
        foreach ($assessments as $assessment) {
            // One sheet for each assessment.
            $sheet          = $workbook->add_worksheet($assessment->name);
            report_assignmentconfiguration_setup_headers($workbook, $sheet, $course->fullname, $assessment->name);
            report_assignmentconfiguration_setup_rows($sheet, $assessment);

        }
    }

}

/**
 * Set up the headers for the sheets.
 *
 * @param mixed $workbook
 * @param MoodleExcelWorksheet $sheet
 * @param mixed $coursename
 * @param mixed $modname
 * @return void
 */
function report_assignmentconfiguration_setup_headers($workbook, MoodleExcelWorksheet $sheet, $coursename, $modname) {

    // Course Name.
    $format = $workbook->add_format(['size' => 18, 'bold' => 1]);
    $coursename = 'Course: ' . $coursename;
    $sheet->write_string(0, 0, $coursename, $format);
    $sheet->set_row(0, 24, $format);
    // Assignment name.
    $format = $workbook->add_format(['size' => 16, 'bold' => 1]);
    $modname = 'Assignment: ' . $modname;
    $sheet->write_string(1, 0, $modname, $format);
    $sheet->set_row(1, 21, $format);

    // Column headers - two rows for grouping.

    $format = $workbook->add_format(HEADINGTITLES);
    $format2 = $workbook->add_format(HEADINGSUBTITLES);

    $sheet->write_string(HEADINGSROW, 0, get_string('report:availability', 'report_assignmentconfiguration'), $format);
    $sheet->merge_cells(HEADINGSROW, 0, HEADINGSROW, 4, $format); // Availability section.
    $col = 0;
    $sheet->write_string(5, $col++, get_string('report:allowsubmissionsfromdate', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:duedate', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:cutoffdate', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:gradingduedate', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:timelimit', 'report_assignmentconfiguration'), $format2);
    $sheet->set_column(0, $col, 10);

    // Submision types

    $sheet->write_string(HEADINGSROW, $col++, get_string('report:submissiontype', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col++, '', $format2);  // Empty cell
    $sheet->set_column($col, $col, 10);

    // Feedback types.

     $sheet->write_string(HEADINGSROW, $col - 1, get_string('report:feedbacktype', 'report_assignmentconfiguration'), $format);
     $sheet->write_string(5, $col++, '', $format2); // Empty cell
     $sheet->set_column($col, $col, 10);

    // Submission settings

    $sheet->write_string(HEADINGSROW, $col - 1, get_string('report:submissionsettings', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col - 1, get_string('report:submissiondrafts', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:requiresubmissionstatement', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:attemptreopenmethod', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:maxattempts', 'report_assignmentconfiguration'), $format2);
    $sheet->set_column($col - 1, $col, 10);
    $sheet->merge_cells(HEADINGSROW, $col - 4, HEADINGSROW, $col - 1, $format);

    // Group submission settings.

    $sheet->write_string(HEADINGSROW, $col++, get_string('report:groupsubmissionsettings', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col - 1, get_string('report:teamsubmission', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:requireallteammemberssubmit', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:teamsubmissiongroupingid', 'report_assignmentconfiguration'), $format2);
    $sheet->set_column($col - 1, $col, 10);
    $sheet->merge_cells(HEADINGSROW, $col - 3, HEADINGSROW, $col - 1, $format);

    // Grade settings.

    $sheet->write_string(HEADINGSROW, $col++, get_string('report:grade', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col - 1, get_string('report:grade:type', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:maxgrade', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:category', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:gradepass', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:method', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:annon', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:hidegrader', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:grade:markingworkflow', 'report_assignmentconfiguration'), $format2);
    $sheet->set_column($col - 1, $col, 10);
    $sheet->merge_cells(HEADINGSROW, $col - 8, HEADINGSROW, $col - 1, $format);

    // Outcomes settings.
    $sheet->write_string(HEADINGSROW, $col++, get_string('report:outcomes', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col - 1, '', $format2);

    // Turnitin settigns.

    $sheet->write_string(HEADINGSROW, $col++, get_string('report:turnitin', 'report_assignmentconfiguration'), $format);
    $sheet->write_string(5, $col - 1, get_string('report:turnitin:use', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:similarityreport', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:allow_any', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:submitpapersto', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:compare_paper', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:compare_internet', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:compare_journals', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:exclude_biblio', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:exclude_quoted', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:exclude_match', 'report_assignmentconfiguration'), $format2);
    $sheet->write_string(5, $col++, get_string('report:turnitin:transmatch', 'report_assignmentconfiguration'), $format2);
    $sheet->set_column($col - 1, $col, 10);
    $sheet->merge_cells(HEADINGSROW, $col - 11, HEADINGSROW, $col - 1, $format);

    return $col;
}
/**
 * Add data to the sheet
 *
 * @param mixed $sheet
 * @param mixed $assessment
 * @return void
 */
function report_assignmentconfiguration_setup_rows(MoodleExcelWorksheet $sheet,  $assessment) {
    $col = 0;
    $row = 6;
    $format = ['text_wrap' => true];

    $sheet->write_string($row, $col++, $assessment->allowsubmissionsfromdate, $format);
    $sheet->write_string($row, $col++ , $assessment->duedate, $format);
    $sheet->write_string($row, $col++ , $assessment->cutoffdate, $format);
    $sheet->write_string($row, $col++ , $assessment->gradingduedate, $format);
    $sheet->write_string($row, $col++ , $assessment->timelimit, $format);

    foreach ($assessment->config as $config) {
        $sheet->write_string($row, $col++ , implode(', ', $config), $format);
    }

    $assessment->submissiondrafts = $assessment->submissiondrafts == 0 ? 'No' : 'Yes';
    $sheet->write_string($row, $col++ , $assessment->submissiondrafts, $format);

    $assessment->requiresubmissionstatement = $assessment->requiresubmissionstatement == 0 ? 'No' : 'Yes';
    $sheet->write_string($row, $col++ , $assessment->requiresubmissionstatement, $format);

    $sheet->write_string($row, $col++ , $assessment->attemptreopenmethod, $format);
    $sheet->write_string($row, $col++ , $assessment->maxattempts, $format);

    $assessment->teamsubmission = $assessment->teamsubmission == 0 ? 'No' : 'Yes';
    $sheet->write_string($row, $col++ ,  $assessment->teamsubmission, $format);

    $assessment->requireallteammemberssubmit = $assessment->requireallteammemberssubmit == 0 ? 'No' : 'Yes';
    $sheet->write_string($row, $col++ , $assessment->requireallteammemberssubmit, $format);

    $assessment->teamsubmissiongroupingid = $assessment->teamsubmissiongroupingid == 0 ? 'No' : 'Yes';
    $sheet->write_string($row, $col++ , $assessment->teamsubmissiongroupingid, $format);
    //  Grade
    foreach ($assessment->grade_details as $grade) {

            $sheet->write_string($row, $col++ , $grade->type, $format);
            $sheet->write_string($row, $col++ , $grade->maxgrade, $format);
            $sheet->write_string($row, $col++ , $grade->category, $format);
            $sheet->write_string($row, $col++ , $grade->gradepass, $format);
            $sheet->write_string($row, $col++ , $grade->method, $format);
            $sheet->write_string($row, $col++ , $grade->annon, $format);
            $sheet->write_string($row, $col++ , $grade->hidegrader, $format);
            $sheet->write_string($row, $col++ , $grade->markingworkflow, $format);
    }

    // Outcome
    foreach ($assessment->outcome as $outcome) {
        $sheet->write_string($row, $col++ , $outcome->name, $format);
    }


    $sheet->write_string($row, $col++ , $assessment->plagiarism->use_turnitin, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->similarityreport, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_allow_non_or_submissions, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_submitpapersto, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_compare_student_papers, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_compare_internet, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_compare_journals, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_exclude_biblio, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_exclude_quoted, $format);
    $sheet->write_string($row, $col++ , $assessment->plagiarism->plagiarism_exclude_matches, $format);
    $sheet->write_string($row, $col, $assessment->plagiarism->plagiarism_transmatch, $format);

}


  /**
     * Creates a zip file with excel files in it
     */
 function zip_excelworkbook($courseid, $cmid) {
        global $CFG;
        $foldertozip = $CFG->tempdir.'/report_assignmentconfiguration/excel';
        // Get real path for our folder.
        $rootpath = realpath($foldertozip);

        // Initialize archive object.
        $zip = new \ZipArchive();
        $filename = $CFG->tempdir.'/report_assignmentconfiguration/configuration.zip';

        $zip->open( $filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Create recursive directory iterator.
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootpath),
        RecursiveIteratorIterator::LEAVES_ONLY
        );
        $filestodelete = [];
        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically).
            if (!$file->isDir()) {
                // Get real and relative path for current file.
                $filepath = $file->getRealPath();
                $relativepath = substr($filepath, strlen($rootpath) + 1);
                // Add current file to archive.
                $zip->addFile($filepath, $relativepath);
                $filestodelete[] = $filepath;
            }
        }

        if ($zip->numFiles > 0) {
            // Zip archive will be created only after closing object.
            $zip->close();

            foreach ($filestodelete as $file) {
                unlink($file);
            }

            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=assignconfig.zip");
            header("Content-Length: " . filesize("$filename"));
            readfile("$filename");
            unlink("$filename");
        } else {
            $url = new \moodle_url('/report/assignmentconfiguration/index.php', array('id' => $courseid, 'cmid' => $cmid));
            redirect($url, get_string('nofilestocompress', 'report_assignfeedback_download'), null, \core\output\notification::NOTIFY_INFO);
        }

    //    die(); // If not set, a invalid zip file error is thrown.

    }
