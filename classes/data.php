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

        raise_memory_limit(MEMORY_HUGE);

        // Grab a list of all relevant files.
        $files = $data->get_files($category);

        // Grab a list of all relevant courses.
        $courses = $data->get_courses($category);

        // Do some gathering.
        $paths = array();
        foreach ($courses as $course) {
            if (!isset($paths[$course->path])) {
                $paths[$course->path] = array(
                    "cid" => $course->id,
                    "shortname" => $course->shortname,
                    "size" => 0,
                    "count" => 0
                );
            }
        }
        unset($courses);

        // Now gather files.
        foreach ($files as $file) {
            $subpath = $file->path;
            while (!empty($subpath) && !isset($paths[$subpath])) {
                $tmp = explode('/', $subpath);
                array_pop($tmp);
                $subpath = implode('/', $tmp);

            }

            if (!empty($subpath)) {
                $paths[$subpath]['size'] += $file->filesize;
                $paths[$subpath]['count'] += $file->files;
            }
        }
        unset($files);

        // Map paths to courses.
        $result = array();
        foreach ($paths as $path) {
            if (!isset($result[$path['cid']])) {
                $result[$path['cid']] = array(
                    "cid" => $path['cid'],
                    "shortname" => $path['shortname'],
                    "size" => 0,
                    "count" => 0
                );
            }
            $result[$path['cid']]['size'] += $path['size'];
            $result[$path['cid']]['count'] += $path['count'];
        }

        // Filtering.
        $result = array_filter($result, function($a) {
            return $a['count'] > 0;
        });

        // Ordering.
        uasort($result, function ($a, $b) {
            return $a['size'] < $b['size'];
        });

        // Total them up.
        $total = count($result);

        // Split up.
        $result = array_slice($result, $limitfrom, $limitnum);

        return array(
            "data" => $result,
            "total" => $total
        );
    }

    /**
     * Grab a result set of files from the db
     */
    private function get_files($category) {
        global $DB;

        $sql = <<<SQL
            SELECT ctx.path, COUNT(f.id) files, SUM(f.filesize) filesize
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid
            WHERE f.filesize > 0
SQL;

        $params = array();
        if ($category !== 0) {
            $sql .= " AND ctx.path LIKE :category";
            $params['category'] = "%/" . $category . "/%";
        }

        $sql .= ' GROUP BY ctx.path';

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Grab a result set of courses from the db
     */
    private function get_courses($category) {
        global $DB;

        $sql = <<<SQL
            SELECT c.id, c.shortname, ctx.path
            FROM {course} c
            INNER JOIN {context} ctx ON ctx.instanceid=c.id AND ctx.contextlevel=:coursectx
            INNER JOIN {course_categories} cc ON cc.id=c.category
SQL;

        $params = array(
            "coursectx" => CONTEXT_COURSE
        );

        if ($category !== 0) {
            $sql .= " AND (cc.path LIKE :cata OR cc.path LIKE :catb)";
            $params['cata'] = "%/" . $category . "/%";
            $params['catb'] = "%/" . $category;
        }

        $sql .= ' GROUP BY ctx.path';

        return $DB->get_records_sql($sql, $params);
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