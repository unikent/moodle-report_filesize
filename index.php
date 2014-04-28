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
 * Filesize report
 *
 * @package    report
 * @subpackage filesize
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('reportfilesize', '', null, '', array('pagelayout' => 'report'));

$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);
$category = optional_param('category', 0, PARAM_INT);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'report_filesize'));

// Setup the table.
$table = new html_table();
$table->head  = array("Course", "File count", "Total file size");
$table->colclasses = array('mdl-left course', 'mdl-left count', 'mdl-left size');
$table->attributes = array('class' => 'admintable filesizereport generaltable');
$table->id = 'filesizereporttable';
$table->data  = array();

$resultset = \report_filesize\data::get_result_set($page * $perpage, $perpage);
foreach ($resultset['data'] as $item) {
    $course = new \html_table_cell(\html_writer::tag('a', $item->shortname, array(
        'href' => $CFG->wwwroot . '/course/view.php?id=' . $item->id,
        'target' => '_blank'
    )));
    $table->data[] = array($course, $item->totalfiles, $item->filesize);
}

echo html_writer::table($table);

$baseurl = new moodle_url('/report/filesize/index.php', array('perpage' => $perpage));
echo $OUTPUT->paging_bar($resultset['total'], $page, $perpage, $baseurl);

echo $OUTPUT->footer();