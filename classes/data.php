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
    /** UUID for the TMP table */
    private $_uid;

    /**
     * Singleton!
     */
    private function __construct() {
        $this->_uid = uniqid("tmp_fsize_");
    }

    /**
     * Creates our temporary table
     */
    public static function get_result_set($category = 0, $limitfrom = 0, $limitnum = 0) {
        $data = new static();
        $data->create_tmp_table();
        $data->fill_tmp_table();

        $params = array();
        $select = $data->get_sql('c.id, c.shortname, COUNT(ftmp.id) totalfiles, SUM(ftmp.filesize) filesize', $category, $params);
        $result = $data->get_result($select, $params, $limitfrom, $limitnum);

        $select = $data->get_sql('COUNT(DISTINCT c.id) AS count', $category, $params);
        $total = $data->get_total($select, $params);

        $data->destroy_tmp_table();

        return array(
            "data" => $result,
            "total" => $total
        );
    }

    /**
     * Grab a result set from the db
     */
    private function get_sql($select, $category, &$params) {
        $sql = 'SELECT '.$select.'
                FROM {course} c
                INNER JOIN {context} ctx ON ctx.instanceid=c.id AND ctx.contextlevel=50
                INNER JOIN {' . $this->_uid . '} ftmp ON ftmp.ctxpath LIKE CONCAT("%/", ctx.id, "/%")
                INNER JOIN {course_categories} cc ON cc.id=c.category';

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
     * Creates our temporary table
     */
    private function create_tmp_table() {
        global $DB;

        $dbman = $DB->get_manager();

        $table = new \xmldb_table($this->_uid);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ctxpath', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('i_path', XMLDB_INDEX_NOTUNIQUE, array('ctxpath'));

        $dbman->create_temp_table($table);
    }

    /**
     * Fills up our temporary table
     */
    private function fill_tmp_table() {
        global $DB;

        $sql = 'INSERT INTO {' . $this->_uid . '} (ctxpath, filesize)
                SELECT ctx.path, f.filesize FROM {files} f
                INNER JOIN {context} ctx ON ctx.id=f.contextid';

        $DB->execute($sql);
    }

    /**
     * Creates our temporary table
     */
    private function destroy_tmp_table() {
        global $DB;

        // Saftey check to make sure we dont drop a core table.
        if (strpos($this->_uid, "tmp_") !== 0) {
            // Uh oh.
            return false;
        }

        $dbman = $DB->get_manager();
        if ($dbman->table_exists($this->_uid)) {
            $table = new \xmldb_table($this->_uid);

            try {
                $dbman->drop_table($table);
            } catch (Exception $e) {
                // Silently hide.
            }
        }
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