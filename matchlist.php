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
 * @package mod
 * @subpackage reservation
 * @author Roberto Pinna (bobo@di.unipmn.it)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$fieldname = required_param('field', PARAM_ALPHANUM);
$matchvalue = required_param('match', PARAM_ALPHANUMEXT);

if ($id) {
    if (! $course = $DB->get_record('course', array('id' => $id))) {
        error('Course ID is incorrect');
    }
}

require_course_login($course);

$coursecontext = context_course::instance($course->id);

require_capability('moodle/course:manageactivities', $coursecontext);

$values = array();

$customfields = reservation_get_profilefields();

// Get the list of used values for requested field.
if (isset($customfields[$fieldname])) {
    // Retrieve custom field values.
    $queryparameters = array('fieldid' => $customfields[$fieldname]->id);
    if ($datas = $DB->get_records('user_info_data', $queryparameters, 'data ASC', 'DISTINCT data')) {
        foreach ($datas as $data) {
            if (!empty($data->data)) {
                $value = new stdClass();
                $value->$fieldname = $data->data;
                $values[] = $value;
            }
        }
    }
} else if ($fieldname == 'group') {
    // Get groups list.
    $groups = groups_get_all_groups($id);
    if (!empty($groups)) {
        foreach ($groups as $group) {
            $value = new stdClass();
            $value->group = $group->name;
            $values[] = $value;
        }
    }
} else {
    // One of standard fields.
    if (in_array($fieldname, array('city', 'institution', 'department', 'address'))) {
        $values = $DB->get_records_select('user', 'deleted=0 AND '.$fieldname.'<>""', null,
                $fieldname.' ASC', 'DISTINCT '.$fieldname);
    }
}
   $style = 'position: relative; float:right; text-align: right;';
   $onclick = 'document.getElementById(\'matchvalue_list\').style.display=\'none\';';
   $strclose = get_string('close', 'reservation');
   echo '<div style="'.$style.'"><a href="javascript:void(0)" onclick="'.$onclick.'">'.$strclose.'&#9746;</a></div>'."\n";
// Generate inner div code.
if (!empty($values)) {
    echo '<strong>'.get_string('selectvalue', 'reservation').'</strong><br />'."\n";
    foreach ($values as $value) {
        if (!empty($value->$fieldname)) {
            $onclick = 'document.getElementById(\''.$matchvalue.'\').value=\''.addslashes(htmlentities($value->$fieldname)).
                    '\'; document.getElementById(\'matchvalue_list\').style.display=\'none\';';
            echo '<a href="javascript:void(0)" onclick="'.$onclick.'">'.$value->$fieldname.'</a><br />'."\n";
        }
    }
} else {
    echo '<br /><strong>'.get_string('novalues', 'reservation').'</strong><br />'."\n";
}
