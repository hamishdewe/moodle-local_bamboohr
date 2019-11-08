<?php

require_once('../../config.php');

require_once('lib.php');

/* 1. Create/update from directory sync */
# local_bamboohr_sync_directory();

/* 2a. Sync an individual user */
# local_bamboohr_sync_local_users(46);

/* 2b. Sync an individual user before time */
# local_bamboohr_sync_local_users(2, 1573021153);

/* 3a. Sync all local users */
# local_bamboohr_sync_local_users();

/* 3b. Sync all local users before time */
# local_bamboohr_sync_local_users(null, 1573021153);

/* 3c. Sync all local users before time with limit */
# local_bamboohr_sync_local_users(null, 1573021663, 1);

/* 4. Update menu options */
# local_bamboohr_update_menu_options();
