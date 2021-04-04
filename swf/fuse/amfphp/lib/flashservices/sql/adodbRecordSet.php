<?php
  class adodbRecordSet
  {
      // original section of writeRecordSet method JC
      // this makes it easy for developers edit results with touching the more important parts of the PHP Gateway JC
      // maybe use different methods to format recordset different ways depending on something??? JC
      function adodbRecordSet($d)
      {
          // create an initialData array
          $this->initialData = array();
          // grab all of the rows
         while ($line = $d->FetchRow()) {
              // decode each value ready for encoding when it goes through serialization
              foreach($line as $key => $value) {
                  $line[$key] = utf8_decode($value);
              }
              // add each row to the initial data array
              $this->initialData[] = $line;
          }
          // create the columnNames array
          $this->columnNames = array();
          // grab the number of fields
          $fieldcount = $d->FieldCount();
          // loop over all of the fields
          for($i=0; $i<$fieldcount; $i++){
              // decode each field name ready for encoding when it goes through serialization
              // and save each field name into the array
              $fld = $d->FetchField($i) ;
              $this->columnNames[$i] = utf8_decode($fld->name);
          }
          $this->numRows = $d->RecordCount();
      }
  }
?>