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
 * Data class for filesize report
 *
 * @package    report
 * @subpackage filesize
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_filesize;

defined('MOODLE_INTERNAL') || die();

class data
{
    /**
     * Creates our temporary table
     */
    public static function get_result_set($category = 0, $limitfrom = 0, $limitnum = 0) {
        $data = new static();

        $params = array();

        $select = 'c.id, c.shortname, SUM(fctx_tmp.filesize) AS filesize, COUNT(fctx_tmp.id) AS totalfiles';
        $select = $data->get_sql($select, $category, $params);
        $result = $data->get_result($select, $params, $limitfrom, $limitnum);

        $select = $data->get_sql('COUNT(DISTINCT c.id) AS count', $category, $params);
        $total = $data->get_total($select, $params);

        return array(
            "data" => $result,
            "total" => $total
        );
    }

    /**
     * Grab a result set from the db
     */
    private function get_sql($select, $category, &$params) {

        $sql = <<<SQL
            SELECT $select
            FROM {course} c
            INNER JOIN {context} ctx ON ctx.instanceid=c.id AND ctx.contextlevel=50
            INNER JOIN {course_categories} cc ON cc.id=c.category
            INNER JOIN (
                SELECT DISTINCT f.id, ictx.path, f.filesize
                FROM {files} f
                INNER JOIN {context} ictx ON ictx.id=f.contextid
                WHERE f.filesize > 0 AND f.component NOT IN (
                    'mod_turnitintool', 'mod_folder',
                    'assignsubmission_onlinetext', 'assignsubmission_file',
                    'assignfeedback_file', 'assignfeedback_editpdf'
                )
            ) fctx_tmp ON fctx_tmp.path LIKE CONCAT("%/", ctx.id, "/%") OR fctx_tmp.path LIKE CONCAT("%/", ctx.id)
SQL;

        $params = array();

        if ($category !== 0) {
            $sql .= " WHERE cc.path LIKE :categorya OR cc.path LIKE :categoryb";
            $params['categorya'] = "%/" . $category;
            $params['categoryb'] = "%/" . $category . "/%";
        }

        return $sql;
    }

    /**
     * Grab a result set from the db
     */
    private function get_result($sql, $params, $limitfrom = 0, $limitnum = 0) {
        global $DB;
        $sql .= ' GROUP BY c.id ORDER BY filesize DESC';
        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Count the total number of results
     */
    private function get_total($sql, $params) {
        global $DB;
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Prettify a file size
     */
    public static function pretty_filesize($size) {
        $filesize = $size . ' bytes';

        if ($size >= 1073741824) {
            $filesize = round($size / 1024 / 1024 / 1024, 1) . 'GB';
        } else if ($size >= 1048576) {
            $filesize = round($size / 1024 / 1024, 1) . 'MB';
        } else if ($size >= 1024) {
            $filesize = round($size / 1024, 1) . 'KB';
        }

        return $filesize;
    }
}