<?php
global $CFG;
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
/**
 * Get the directory from BambooHR
 *
 * @return
 */
function local_bamboohr_get_directory() {
  $query = local_bamboohr_curl_get('employees/directory');
  return $query->employees;
}

function local_bamboohr_get_employee_by_id($id) {
  // Only get the fields being mapped
  $fieldlist = local_bamboohr_get_mapped_fields();
  $path = "employees/{$id}?fields={$fieldlist}";
  $employee = local_bamboohr_curl_get($path);
  return $employee;
}

function local_bamboohr_get_mapped_fields() {
  $config = get_config('bamboohr');
  $fields = array('workEmail','supervisor','supervisorEId','status','terminationDate');
  foreach ($config as $key=>$value) {
    $split = explode('_', $key);
    if ($split[0] === 'map' && !empty($value)) {
      $fields[] = $split[1];
    }
  }
  return implode(',', $fields);
}

function local_bamboohr_get_mapping() {
  $config = get_config('bamboohr');
  $fields = array();
  foreach ($config as $key=>$value) {
    $split = explode('_', $key);
    if ($split[0] === 'map' && !empty($value)) {
      $fields[$split[1]] = $value;
    }
  }
  return $fields;
}

function local_bamboohr_get_user_by_employee($employee) {
  global $DB;
  $email = strtolower(trim($employee->workEmail));
  $user = $DB->get_record('user', ['idnumber'=>$employee->id], '*', IGNORE_MULTIPLE);
  return $user;
}

function local_bamboohr_field_has_alias($item) {
  return isset($item->alias) && !isset($item->deprecated);
}

function local_bamboohr_get_fields() {
  if ($remote = local_bamboohr_curl_get('meta/fields/')) {
    $fields = array_filter($remote, "local_bamboohr_field_has_alias");
    return $fields;
  }
}

function local_bamboohr_get_lists() {
  return local_bamboohr_curl_get('meta/lists/');
}

function local_bamboohr_update_supervisor_role($supervisor, $bamboo_employee) {
  global $DB;
  
  $roleid = get_config('bamboohr', 'supervisorroleid');
  $employee = local_bamboohr_get_user_by_employee($bamboo_employee);
  $context = context_user::instance($employee->id);
  if ($assignment = $DB->get_record('role_assignments', ['contextid'=>$context->id, 'component'=>'local_bamboohr'])) {
    if ($employee->suspended || is_null($supervisor) || !isset($supervisor->id)) {
      $nullsupervisor = is_null($supervisor);
      mtrace("Delete supervisor role: {$assignment->id} (suspended: {$employee->suspended}, null: {$nullsupervisor}, supervisorid: {$supervisor->id})");
      $DB->delete_records('role_assignments', (array)$assignment);
    } else {
      if ($assignment->roleid !== $roleid || $assignment->userid !== $supervisor->id) {
        mtrace("Update supervisor role: {$assignment->id}");
        $assignment->roleid = $roleid;
        $assignment->userid = $supervisor->id;
        $assignment->timemodified = time();
        $DB->update_record('role_assignments', $assignment);
      } else {
        mtrace("Supervisor role already valid: {$assignment->id}");
      }
    }
  } else {
    if ($supervisor && $supervisor->id) {
      $assignment = (object) array(
        'roleid'=>$roleid,
        'contextid'=>$context->id,
        'userid'=>$supervisor->id,
        'timemodified'=>time(),
        'modifierid'=>2,
        'component'=>'local_bamboohr',
        'itemid'=>0,
        'sortorder'=>0
      );
      $assignment->id = $DB->insert_record('role_assignments', $assignment);
      mtrace("Supervisor role created: {$assignment->id}");
    }
  }
}

function local_bamboohr_get_country_code($employee) {
  if (isset($employee->country)) {
    return array_search($employee->country, get_string_manager()->get_list_of_countries());
  } else {
    return 'US';
  }
}

function local_bamboohr_is_user_field($field) {
  $list = array(
    'firstname',
    'lastname',
    'email',
    'icq',
    'skype',
    'yahoo',
    'aim',
    'msn',
    'phone1',
    'phone2',
    'institution',
    'department',
    'address',
    'city',
    'url',
    'description',
    'lastnamephonetic',
    'firstnamephonetic',
    'middlename',
    'alternatename');
  return in_array($field, $list);
}

function local_bamboohr_sync_directory() {
  global $DB;
  set_time_limit(0);

  $employees = local_bamboohr_get_directory();
  $mapping = local_bamboohr_get_mapping();
  foreach ($employees as $employee) {
    $user = local_bamboohr_get_user_by_employee($employee);
    $supervisor = local_bamboohr_get_supervisor_by_employee($employee, $employees);
    local_bamboohr_save_employee_as_user($employee, $supervisor, $mapping, $user);
  }
}

function local_bamboohr_get_supervisor_by_employee($employee, $employees) {
  $supervisor = empty($employee->supervisor)
    ? null
    : local_bamboohr_get_user_by_employee($employees[array_search($employee->supervisor, array_column($employees, 'displayName'))]);
    return $supervisor;
}

function local_bamboohr_get_last_cron_time() {
  global $DB;

  return $DB->get_field('task_scheduled', 'lastruntime', ['component'=>'local_bamboohr', 'classname'=>'\local_bamboohr\task\local']);
}

// function local_bamboohr_sync_local_users($userid = null, $until = null, $limit = null) {
//   global $DB;
//   //var_dump(get_string_manager()->get_list_of_countries());
//   set_time_limit(0);
//   $where = !is_null($userid) && intval($userid) > 1 ?  " and u.id = {$userid}" : '';
//   $join = '';
//   $orderby = '';
//   if (!is_null($until) && intval($until) < time()) {
//     $config = get_config('bamboohr', 'update');
//     $join = 'left join {user_info_data} uid on uid.fieldid = (select id from {user_info_field} where shortname = "' . $config . '") and uid.userid = u.id ';
//     $where .= ' and (uid.data <= ' . $until . ' or uid.data is null) and u.idnumber > 0 ';
//     $orderby = ' order by uid.data asc';
//   }
//   $count = intval($limit) > 0 ? intval($limit) : 500;
//   $users = $DB->get_records_sql('select u.* from {user} u ' . $join . ' where u.idnumber is not null and u.idnumber != "" and u.idnumber > 0 and u.deleted = 0' . $where . $orderby, array(), 0, $count);
//   $employees = local_bamboohr_get_directory();
//   $mapping = local_bamboohr_get_mapping();
//   foreach($users as $user) {
//     if ($employee = local_bamboohr_get_employee_by_id($user->idnumber)) {
//       $supervisor = null;
// 
//       if (isset($employee->supervisorEId) && isset($employee->supervisor)) {
//         $supervisor = $DB->get_record_sql('select * from {user} where idnumber = :idnumber and (deleted = 0 or suspended = 0)', ['idnumber'=>$employee->supervisorEId]);
//         //mtrace('supervisor search 1');
//       } else if (!is_null($employee->supervisor) && !is_null($employee->supervisorEId)) {
//         $supervisor = local_bamboohr_get_user_by_employee($employees[array_search($employee->supervisor, array_column($employees, 'displayName'))]);
//         //mtrace('supervisor search 2');
//       }
// 
//       local_bamboohr_save_employee_as_user($employee, $supervisor, $mapping, $user);
//     }
//   }
// }

function local_bamboohr_save_employee_as_user($employee, $supervisor, $mapping, $user) {
  global $DB;

  if (!$employee || !$user || !$employee->id) {
    return;
  }
  $config = get_config('bamboohr', 'update');
  $user->idnumber = $employee->id;

  $profilefields = [ $config => time() ];
  foreach ($mapping as $source=>$target) {
    if (!isset($employee->$source) ) {
      continue;
    }

    if (local_bamboohr_is_user_field($target)) {
      $user->$target = $employee->$source;
    } else if ($target === 'country') {
      //mtrace("Update country '{$employee->$source}'");
      if ($country = array_search($employee->$source, get_string_manager()->get_list_of_countries())) {
        $user->$target = array_search($employee->$source, get_string_manager()->get_list_of_countries());
      } else {
        switch ($employee->$source) {
          case 'Moldova': {
            $user->$target = 'MD';
            break;
          }
          default: {
            mtrace("Unmapped country code for {$employee->$source}");
            $user->$target = null;
          }
        }
      }
    } else {
      $uif = $DB->get_record('user_info_field', ['shortname'=> $target]);
      if ($uif->datatype === 'datetime') {
        $parts = explode('-', $employee->$source);
        $employee->$source = mktime (0, 0, 0, $parts[1], $parts[2], $parts[0]);
      }
      $profilefields[$target] = $employee->$source;
    }
  }

  if (isset($user->id)) {
    user_update_user($user, false);
    mtrace("Updated user: {$user->id}");
    profile_save_custom_fields($user->id, $profilefields);
    local_bamboohr_update_supervisor_role($supervisor, $employee);
  }
}

function local_bamboohr_update_menu_options() {
  global $DB;
  $lists = (array) local_bamboohr_get_lists();
  $menus = (array) $DB->get_records_sql('select shortname, id from {user_info_field} where datatype = :datatype', ['datatype'=>'menu']);
  $mapping = local_bamboohr_get_mapping();
  foreach($mapping as $source=>$target) {
    if (!isset($menus[$target])) {
      continue;
    }
    try {
      $aliased = $lists[array_search($source, array_column($lists, 'alias'))];
      $items = [];
      foreach($aliased->options as $option) {
        $items[] = $option->name;
      }
      $menus[$target]->param1 = implode(PHP_EOL, $items);
      $DB->update_record('user_info_field', $menus[$target]);
    } catch (Exception $ex) {
      mtrace("Could not update menu options for {$menus[$target]}");
    }
  }
}

function local_bamboohr_curl_get($path) {
  $config = get_config('bamboohr');
  // Exit if not yet configured
  if (!$config->subdomain || !$config->apikey) {
    return false;
  }

  $ch = curl_init("https://api.bamboohr.com/api/gateway.php/{$config->subdomain}/v1/{$path}");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
  curl_setopt($ch, CURLOPT_USERPWD, $config->apikey . ":x");
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $result = curl_exec($ch);
  curl_close($ch);
  return json_decode($result);
}
