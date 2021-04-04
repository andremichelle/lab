<?php
class postgresRecordSet
{
	var $initialData = array();
	var $columnNames = array();
	function postgresRecordSet($d)
	{
		// grab all of the rows
		while ($line = pg_fetch_row($d)) {
			// decode each value ready for encoding when it goes through serialization
			foreach($line as $key => $value) {
				$line[$key] = utf8_decode($value);
			}
			// add each row to the initial data array
			$this->initialData[] = $line;
		}	
		// grab the number of fields
		$fieldcount = pg_num_fields($d);
		// loop over all of the fields
		for($i=0; $i<$fieldcount; $i++)	{
			// decode each field name ready for encoding when it goes through serialization
			// and save each field name into the array
			$this->columnNames[$i] = utf8_decode(pg_field_name($d, $i));
		}
		$this->numRows = pg_num_rows($d);
	}
}
?>