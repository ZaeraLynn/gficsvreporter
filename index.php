<?php

// File Includes
include("databaseconnect.php");
include("reportbuildercsv.php");

/* Static Report Arrays
*
* These arrays are built based on the fields in the GFI API. The indexes are given
* the GFI names from the XML output and assigned easy to read strings for CSV output.
*
*/
$ipmacfields = array(	'name' => 'System Name',
			'os' => 'Operating System',
			'osversion' => 'OS Version #',
			'servicepack' => 'Service Pack',
			'lastscantime' => 'Last Scan Date',
			'ip' => 'IP Address',
			'domain' => 'Domain',
			'user' => 'Last Logged In User',
			'deviceserial' => 'Serial Number',
			'mac1' => 'MAC Address'
		);

$memoryfields = array(	'name' => 'System Name',
			'model' => 'System Model',
			'totalmemory' => 'Memory',
			'lastscantime' => 'Last Scan Date',
			'deviceserial' => 'Serial Number'
		);
		
		
$completefields = array(	'name' => 'System Name',
			'model' => 'System Model',
			'deviceserial' => 'Serial Number',
			'bios' => 'BIOS',
			'processor' => 'Processor',
			'totalmemory' => 'Memory',
			'harddrive' => 'Hard Drive',
			'os' => 'Operating System',
			'osversion' => 'OS Version #',
			'servicepack' => 'Service Pack',
			'networkcard' => 'Network Card',
			'domain' => 'Domain',
			'ip' => 'IP Address',
			'mac1' => 'MAC Address',
			'user' => 'Last Logged In User',
			'lastscantime' => 'Last Scan Date',
			'installdate' => 'Install Date'
		);



/* Determine what kind of report to generate or whether an upload needs to be performed.
*
* The Asset List with IP and MAC and the Memory reports are currently not in use. They have
* been combined with the complete report.
*
*/

$report = $_POST['report'];

if(!empty($_POST['exportCSV']) && $report == "ipmac"){
	exportReport("Asset List with IP and MAC", $ipmacfields);
	
}else if(!empty($_POST['exportCSV']) && $report == "memory"){
	exportReport("Memory", $memoryfields);
	
}else if(!empty($_POST['exportCSV']) && $report == "complete"){
	exportReport("Complete Asset Report", $completefields);	
}

// 	Uploading a .SQL export from GFI into the temp database
}else if(!empty($_POST['upload'])){

	$UPLOAD_MAX_FILESIZE = ini_get('upload_max_filesize');
	$mul = substr($UPLOAD_MAX_FILESIZE, -1);
	$mul = ($mul == 'M' ? 1048576 : ($mul == 'K' ? 1024 : ($mul == 'G' ? 1073741824 : 1)));

	if (($_SERVER['CONTENT_LENGTH'] > $mul*(int)$UPLOAD_MAX_FILESIZE)){
		echo "Upload file too large. Please truncate and try again.<br/>";

	}else{

		/* Drop existing tables from database. The database is used as a temporary place to store all of the SQL
		*  data from the GFI export.
		*/
		 
		mysql_query("DROP TABLE as_catalog");
		mysql_query("DROP TABLE as_device");
		mysql_query("DROP TABLE as_software");
		mysql_query("DROP TABLE as_hardware");
		mysql_query("DROP TABLE client");
		mysql_query("DROP TABLE site");

		$tempFilename = $_FILES["file"]["tmp_name"];
		$filename = $_FILES["file"]["name"];

		if (($handle = fopen($tempFilename, "r")) !== FALSE){

			// Once the web server handles the temp file upload, we'll store it in a sqlfiles directory.
			move_uploaded_file($tempFilename, "sqlfiles/" . $filename);

			// Split the .SQL export into an array of separate queries to be imported into the temp database.
			$delimiter = ";";
       	 	$query = @file_get_contents("sqlfiles/" . $filename);
        	$query = remove_remarks($query);
        	$query = split_sql_file($query, $delimiter);

			/* Run each of the queries in the $query array to import data into the temp database.
			*  There is no code to prevent malicious queries from being inserted into the .SQL file before import.
			*  Because this database is wiped each time and has no access to permanent / sensitive data, it is
			*  an acceptable risk.
			*/
			foreach($query as $sql){
				// Fix for GFI's NULL catalog entries, even though it doesn't allow null.
				if($sql != "INSERT INTO as_catalog VALUES(null,null,null)"){
					mysql_query($sql) or die(mysql_error());
				}
			} 

			fclose($handle);
			echo "Upload complete. Export a report below.<br/><br/>";
		}

	}
}


/*
* remove_remarks will strip the SQL comment lines out of an uploaded SQL file
*/
function remove_remarks($sql){
    $sql = preg_replace('/\n{2,}/', "\n", preg_replace('/^[-].*$/m', "\n", $sql));
    $sql = preg_replace('/\n{2,}/', "\n", preg_replace('/^#.*$/m', "\n", $sql));
    return $sql;
}

/*
* split_sql_file will split an uploaded SQL file into single SQL statements.
* Note: expects trim() to have already been run on $sql.
*/
function split_sql_file($sql, $delimiter){
    $sql = str_replace("\r" , '', $sql);
    $data = preg_split('/' . preg_quote($delimiter, '/') . '$/m', $sql);
    $data = array_map('trim', $data);
    // The empty case
    $end_data = end($data);
    if (empty($end_data))
    {
        unset($data[key($data)]);
    }
    return $data;
}  


/*
* Report site display below. This is based on a stripped down version of the Wordpress template and
* will need to be updated when the site is updated visually.
*/
?>
  
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
	<head profile="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>GFI Asset Management Report Creator</title>
		<link rel="stylesheet" href="http://www.choicetech.com/wp-content/themes/choicetech/style.css" type="text/css" media="screen" />
		<style type="text/css">#header { background: url(http://www.choicetech.com/wp-content/uploads/2012/08/header-bg.gif) no-repeat; }</style>
	</head>
	<body class="home page page-id-12 page-template-default logged-in header-image full-width-content">
		<div id="wrap">
			<div id="header">
				<div class="wrap">
					<div id="title-area">
						<p id="title"><a href="http://www.choicetech.com/" title="Choice Technologies, IT Services">Choice Technologies, IT Services</a></p>
						<p id="description"></p>
					</div><!-- end #title-area -->
					<div class="widget-area">
					</div><!-- end .widget-area -->

					<div class="clear"></div>
					<div id="content" class="hfeed" style="float: none !important; text-align: left;">
						<p><img src="http://www.hound-dog.us/customisation/295/icon.gif"></p>
						<p><strong>Asset Management Report Creator</strong></p>
						<p>Download SQL Data Dump from MonitorSecure before proceeding. Reports -> Asset Tracking Reports -> SQL Data Dump (Choose the desired client).</p>
						<p>Current client loaded:</p>

<?php


/* Display current client loaded in the temp database. This serves two purposes:
*  1) It is a secondary alert to the user that the upload was successful.
*  2) If the proper client is loaded and the data isn't old, it can be reused without another upload.
*/
$query = "SELECT * FROM client";
$result = mysql_query($query);
$numRows = mysql_num_rows($result);

if($numRows == 1){
	if($row = mysql_fetch_array($result)){
		echo "<strong>" . $row['name'] . "</strong>"; // Print client name

		// Display time that it was uploaded to the web server
		$query = "SELECT UPDATE_TIME FROM information_schema.tables WHERE TABLE_SCHEMA = 'DATABASE_NAME' AND TABLE_NAME = 'client'";
		$result = mysql_query($query) or die(mysql_error());

		$row = mysql_fetch_array($result);
		echo " uploaded on " . $row['UPDATE_TIME']; // Print uploaded time.
	}else{
		echo "<b>NONE</b>";
	}
}
?>

						<p>
							<form enctype="multipart/form-data" action="index.php" method="post">
								<p>Upload .SQL File: <input type="file" name="file" /> <input type="submit" name="upload" value="Upload" /></p>
								<p>Report Type: <select name="report"><option value="complete"<?php if($report == "complete") echo " selected";?>>Complete Asset Report</option></select></p>
								<input type="submit" name="exportCSV" value="Export to CSV" />
						  </form>
						</p>
					</div><!-- end #content -->
				</div><!-- end .wrap -->
			</div><!-- end #header -->
			<div id="footer" class="footer">
				<div class="wrap">&copy; <?php echo date("Y"); ?> Choice Technologies
				</div><!-- end .wrap -->
			</div><!-- end #footer -->
		</div><!-- end #wrap -->
	</body>
</html>

  
  
  