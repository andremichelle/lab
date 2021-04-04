<?php
// original section of writeRecordSet method JC
// this makes it easy for developers edit results with touching the more important parts of the PHP Gateway JC
// maybe use different methods to format recordset different ways depending on something??? JC
class mssqlRecordSet
{
	var $initialData = array();
	var $columnNames = array();

	function mssqlRecordSet($d)
	{
		// grab all of the rows
		while ($line = mssql_fetch_row($d)) {
			// decode each value ready for encoding when it goes through serialization
			foreach($line as $key => $value) {
				$line[$key] = utf8_decode($value);
			}
			// add each row to the initial data array
			$this->initialData[] = $line;
		}	
		// grab the number of fields
		$fieldcount = mssql_num_fields($d);
		// loop over all of the fields
		for($i=0; $i<$fieldcount; $i++)	{
			// decode each field name ready for encoding when it goes through serialization
			// and save each field name into the array
			$this->columnNames[$i] = utf8_decode(mssql_field_name($d, $i));
		}
		$this->numRows = mssql_num_rows($d);
	}
}
?>