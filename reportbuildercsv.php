<?php

/* exportReport
*
* @param string $reportName - name of report to run
* @param array $fieldArray - array of asset fields and names in GFI to export
* @return None, but it will prompt the user to download the generated CSV report.
*/
function exportReport($reportName, $fieldArray){

	// File name generation based on loaded client name and date.
	$query1 = "SELECT * FROM client";
	$result1 = mysql_query($query1);
	$row1 = mysql_fetch_array($result1);
	$filename = $row1['name'] . " - " . $reportName . " - " . date("m-d-y") . ".csv";	

	// Select all assets in the temporary database
	$query2 = "SELECT * FROM as_device ORDER BY name ASC";
	$result2 = mysql_query($query2);
	$num_rows = mysql_num_rows($result2);

	// Create a blank CSV file using the above file name.
	$file = fopen($filename,"w");

	// Add column names to CSV, using the array of chosen fields.
	$columnNames = array();
	foreach ($fieldArray as $dbname => $propername){
		$columnNames[] = $propername;	
	}
	
	// Save the column names to the CSV file.
	fputcsv($file, $columnNames);

	// Add data to CSV, using the array of chosen fields.	
	while($row2 = mysql_fetch_array($result2)){
		$data = array();	// working array of the next data to be added to the CSV file
		$fields = mysql_num_fields($result2);
		foreach ($fieldArray as $dbname => $propername){

			// Details for additional asset information need to be grabbed from the hardware table.
			
			// Installed Memory
			if($dbname == "totalmemory"){
				$query3 = "SELECT details FROM as_hardware WHERE (name=\"Physical Memory\" OR name=\"3DRAM\") AND deviceid='$row2[deviceid]'";
				$result3 = mysql_query($query3);

				$memoryInfo = "Total: " . $row2['totalmemory'] / 1048576 . "MB  Slots: ";

				while($row3 = mysql_fetch_array($result3)){
					list($type, $value) = split("=", $row3['details']);
					if($type == "Capacity"){
						$memoryInfo .= $value / 1048576 . "MB  ";
					}	
				}
				$data[] = $memoryInfo;
			// BIOS Information
			}else if($dbname == "bios"){
				$query3 = "SELECT name FROM as_hardware WHERE hwtype='2' AND deviceid='$row2[deviceid]'";
				$result3 = mysql_query($query3);
				$row3 = mysql_fetch_array($result3);
				$data[] = $row3['name'];
			// Processor Information
			}else if($dbname == "processor"){
				$query3 = "SELECT name FROM as_hardware WHERE hwtype='13' AND deviceid='$row2[deviceid]'";
				$result3 = mysql_query($query3);
				$row3 = mysql_fetch_array($result3);
				$data[] = $row3['name'];
			// Hard Drive Information
			}else if($dbname == "harddrive"){
				$query3 = "SELECT details, name FROM as_hardware WHERE hwtype='9' AND deviceid='$row2[deviceid]'";
				$result3 = mysql_query($query3);

				$driveInfo = "";

				while($row3 = mysql_fetch_array($result3)){
					$values = preg_split("/[\s]*[=][\s]*/", $row3['details']);
					$driveInfo .= $row3['name'] . " - " . number_format($values[3] / 1073741824, 0, '.', '') . "GB   ";	
				}
				$data[] = $driveInfo;
			// Network Card Information
			}else if($dbname == "networkcard"){
				$query3 = "SELECT name FROM as_hardware WHERE hwtype='1' AND deviceid='$row2[deviceid]'";
				$result3 = mysql_query($query3);
				$row3 = mysql_fetch_array($result3);
				$data[] = $row3['name'];
			// Remaining Data
			}else{
				for($i = 0; $i < $fields; $i++){
					$fieldData = mysql_fetch_field($result2, $i);
					if($fieldData->name == $dbname){
						$data[] = $row2[$i];
					}
				}
			}
		}
		// Add data to CSV file.
		fputcsv($file, $data);
	}	
	
	// Save the file and prompt user for download.
	fclose($file);
	header("Content-type: application/x-file-to-save");
	header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
	readfile($filename);
	die();
}

?>