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
        $this->_uid = uniqid("tmp_fsize_report_");
    }

    /**
     * Creates our temporary table
     */
    public static function get_data() {
        $data = new static();
        $data->create_tmp_table();

        $result = array();
        // TODO..

        $data->destroy_tmp_table();

        return $result;
    }

    /**
     * Creates our temporary table
     */
    private function create_tmp_table() {
        global $DB;

        $dbman = $DB->get_manager();

        $table = new xmldb_table($this->_uid);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ctxpath', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('i_path', XMLDB_INDEX_NOTUNIQUE, array('ctxpath'));

        $dbman->create_temp_table($table);
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
            $table = new xmldb_table($this->_uid);

            try {
                $dbman->drop_table($table);
            } catch (Exception $e) {
                // Silently hide.
            }
        }
    }
}