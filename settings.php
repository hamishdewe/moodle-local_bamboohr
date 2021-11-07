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
 * Global settings
 *
 * @package local_bamboohr
 * @copyright 2019 Hamish Dewe
 */
defined('MOODLE_INTERNAL') || die();
global $DB;

include_once($CFG->dirroot . '/local/bamboohr/lib.php');

if ($hassiteconfig) {
  $customfields = array(
    'checkbox' => $DB->get_records_menu('user_info_field', array('datatype'=>'checkbox'), 'name asc', 'shortname, name'),
    'datetime' => $DB->get_records_menu('user_info_field', array('datatype'=>'datetime'), 'name asc', 'shortname, name'),
    'menu' => $DB->get_records_menu('user_info_field', array('datatype'=>'menu'), 'name asc', 'shortname, name'),
    'textarea' => $DB->get_records_menu('user_info_field', array('datatype'=>'textarea'), 'name asc', 'shortname, name'),
    'text' => $DB->get_records_menu('user_info_field', array('datatype'=>'text'), 'name asc', 'shortname, name')
  );
  $textfields = array(
    'firstname' => 'firstname',
    'lastname' => 'lastname',
    'icq' => 'icq',
    'skype' => 'skype',
    'yahoo' => 'yahoo',
    'aim' => 'aim',
    'msn' => 'msn',
    'phone1' => 'phone1',
    'phone2' => 'phone2',
    'institution' => 'institution',
    'department' => 'department',
    'address' => 'address',
    'city' => 'city',
    'url' => 'url',
    'description' => 'description',
    'lastnamephonetic' => 'lastnamephonetic',
    'firstnamephonetic' => 'firstnamephonetic',
    'middlename' => 'middlename',
    'alternatename' => 'alternatename'
  );

  $roles = $DB->get_records_sql_menu('select r.id, r.name from {role} r join {role_context_levels} rc on rc.roleid = r.id and rc.contextlevel = 30');

  $settings = new admin_settingpage('local_bamboohr',
          get_string('pluginname', 'local_bamboohr'));

  $ADMIN->add( 'localplugins', $settings );

  $settings->add(
    new admin_setting_configtext('bamboohr/subdomain',
            get_string('subdomain', 'local_bamboohr'),
            get_string('subdomaindesc', 'local_bamboohr')
            , '')
    );
  $settings->add(
    new admin_setting_configtext('bamboohr/apikey',
            get_string('apikey', 'local_bamboohr'),
            get_string('apikeydesc', 'local_bamboohr')
            , '')
    );

  $settings->add(
    new admin_setting_configselect("bamboohr/update",
      'Sync update field',
      'This field must be set. Datetime is updated on sync.', 0, array_merge([0=>'Not set'], $customfields['datetime'])
      )
    );

  $settings->add(
    new admin_setting_configselect("bamboohr/supervisorroleid",
      'Supervisor role',
      '', 0, $roles
      )
    );
  if (!$fields = local_bamboohr_get_fields()) {
    return;
  }
// checkbox, datetime, menu, text
  foreach ($fields as $field) {
    if ($field->id === 'workEmail') {
      continue;
    }
    if ($field->name === '') {
      continue;
    }
    if(!ctype_alnum(str_replace(['-','_'], '', $field->id))) {
      $settings->add(
        new admin_setting_description("bamboohr/map_{$field->id}",
          "{$field->name}", "Cannot be mapped due to unsupported characters in its id: '{$field->id}'"
          )
        );
      continue;
    }
    $options = array('None'=> array('0'=>'No mapping'));
    $default = 0;
    $description = '';
    switch ($field->id) {
      case 'city': {
        $default = 'city';
        break;
      }
      case 'country': {
        $default = 'country';
        $description = 'If the default is chosen, the name of the country in BambooHR will be translated to a 2-character country code';
        break;
      }
      case 'department': {
        $default = 'department';
        break;
      }
      case 'division': {
        $default = 'institution';
        break;
      }
      case 'firstName': {
        $default = 'firstname';
        break;
      }
      case 'lastName': {
        $default = 'lastname';
        break;
      }
      case 'middleName': {
        $default = 'middlename';
        break;
      }
      case 'preferredName': {
        $default = 'alternatename';
        break;
      }
    }
    if ($field->name === 'firstName') {
      $default = 'firstname';
  } else if ($field->name === 'lastName') {
      $default = 'lastname';
    }
    switch ($field->type) {
      // datetimes
      case 'date': {
        $options['User profile fields'] = $customfields['datetime'];
        break;
      }
      case 'country': {
        $options['User'] = array('country' => 'country');
        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
      case 'list': {
        $options['User'] = $textfields;
        $options['Custom menu field'] = $customfields['menu'];
        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
      case 'email': {
        $options['User'] = array('username'=>'username', 'email'=>'email');
        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
      case 'contact_url': {
        $options['User'] = array('icq'=>'icq','skype'=>'skype','yahoo'=>'yahoo','aim'=>'aim','msn'=>'msn');
        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
      case 'phone': {
        $options['User'] = array('phone1'=>'phone1','phone2'=>'phone1');

        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
      case 'text':
      case 'employee_number':
      case 'aca_status':
      case 'gender':
      case 'marital_status': //text
      case 'exempt': //text
      case 'paid_per': //text
      case 'currency': //text
      case 'pay_type': // text
      case 'sin': // text
      case 'ssn': // text
      case 'state': //text
      case 'status': //text
      case 'twitter_handle':
      default: {
        $options['User'] = $textfields;
        if (count($customfields['text']) > 0) {
          $options['Custom text field'] = $customfields['text'];
        }
        if (count($customfields['textarea']) > 0) {
          $options['Custom textarea field'] = $customfields['textarea'];
        }
        break;
      }
    }
    $settings->add(
      new admin_setting_configselect("bamboohr/map_{$field->id}",
        get_string('enablefield', 'local_bamboohr', $field),
        $description, $default, $options
        )
      );

  }
}
