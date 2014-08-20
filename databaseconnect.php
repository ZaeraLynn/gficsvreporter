<?php

	// Connect to the database
	$username="DB_USERNAME";
	$password="DB_PASSWORD";
	$database="DB_NAME";


	$db = mysql_connect(localhost,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");

?>