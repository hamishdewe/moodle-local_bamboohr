<?php

$tasks = [
    // Daily at 2:05 am
    [
        'classname' => 'local_bamboohr\task\full_sync',
        'blocking' => 0,
        'minute' => '5',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ]
];
