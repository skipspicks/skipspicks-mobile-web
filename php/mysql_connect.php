<?php

DEFINE ('DB_USER', 'skipspicks');
DEFINE ('DB_PASSWORD', 'skipspicks');
DEFINE ('DB_HOST', 'mysql.skipspicks.com');
DEFINE ('DB_NAME', 'skipspicks_v2');

// Make the connnection and then select the database.
DEFINE ('DB_LINK', mysql_connect(DB_HOST, DB_USER, DB_PASSWORD)) OR die ('Error connecting to db: ' . mysql_error() );
mysql_select_db (DB_NAME) OR die ('Could not select the database: ' . mysql_error() );

?>
