<?php
class ServiceBrowser
{
	var $_classpath;
	var $_classname;
	var $_methodname;
	var $_classConstruct;
	var $_arguments;
	var $_stylesheet;

	function ServiceBrowser()
	{
		$arguments = func_get_args();
		call_user_func_array(array(&$this, "__construct"), $arguments);
	}

	function __construct()
	{
		if(isset($_GET['methodname'])) {
			$this->_methodname = $_GET['methodname'];
		}
		if(isset($_POST['arguments'])) {
			$this->_arguments = $_POST['arguments'];
		}
	}

	function setService($class)
	{
		$this->_classpath = $class;
		// get classname
		$dot = strrpos($this->_classpath, ".");
		if ($dot === false)	{
			// class name was passed
			$trunced = $this->_classpath;
			$this->_classpath .= ".php";
		} else {
			// class filename was passed
			$trunced = substr($this->_classpath, 0, $dot);
		}
		$this->_classname = substr(strchr($trunced, "/"), 1);
		include_once($this->_classpath);
		if (class_exists($this->_classname)) {
			// don't want to use RemotingService so we can keep separate from AMFPHP
			$this->_classConstruct = new $this->_classname();
		} else {
			trigger_error("Class " . $this->_classname . " does not exist.", E_USER_ERROR);
		}
	}
	// ??? maybe include this once the markup is sorted out...maybe also use three templates to create the pages
	function setStyleSheet($path)
	{
		$this->_stylesheet = $path;
	}
	function browse()
	{
		if (isset($this->_methodname)) {
			$this->_testMethod();
		} else {
			$this->_listMethods();
		}		
	}
	// Main method listing page
	function _listMethods()
	{
		echo '<html><head><title>Service ' . $this->_classpath . '</title><link rel="stylesheet" type="text/css" href="' . $this->_stylesheet . '" /></head>';
		echo '<body>';
		echo '<h1>ServiceBrowser</h1><h2>Exploring ' . $this->_classpath . '</h2>';
		foreach ($this->_classConstruct->methodTable as $name => $methodproperties)
		{
			$this->_printMethod($name, $methodproperties);	
		}
		echo '</body></html>'; 
	}
	// Main method result page
	function _testMethod()
	{
		echo '<html><head><title>Testing Service ' . $this->_classpath . ' Method ' .$this->_methodname.' </title><link rel="stylesheet" type="text/css" href="' . $this->_stylesheet . '" /></head>';
		echo '<body>';
		echo '<h1>ServiceBrowser</h1><h2>Testing '.$this->_classpath.' Method ' . $this->_methodname.'</h2>';
		if (count($this->_classConstruct->methodTable[$this->_methodname][arguments]) != 0) {
			if (!isset($this->_arguments)) { 
				$this->_printForm();
			} else {
				$result = call_user_func_array(array(&$this->_classConstruct, $this->_methodname), $this->_arguments);
				$this->_printResult($result);
			}
		} else {
			$a = array(null);
			$result = call_user_func_array(array(&$this->_classConstruct, $this->_methodname), $a);
			$this->_printResult($result);
		}
		echo '</body></html>'; 
	}

	/// 'Helper functions'
	//  all the markup here needs cleaning and replacing with CSS
	//  also look at the possibilty of externalizing the markup into templates

	// Prints Method Name header
	function _printMethod($name, $methodproperties)
	{
		// header - Method name and link
		echo '<div id="methods">';
		echo '<table class="methodtable">';
		echo '<caption class="methodcaption">Method: <a href="?methodname=' . $name . '">' . $name . '</a></caption>';
		foreach($methodproperties as $property => $value) {
			if (!is_array($value)) {
				$this->_printMethodProp($property, $value);
			} else {
				$this->_printArgs($property, $value);
			}
		}
		echo '</table>';
	}
	// prints method properies (string values)
	function _printMethodProp($property, $value)
	{
		// description, access, roles, instance, alias
		echo '<tr class="methodrow"><td class="propname">' . nl2br($property) . '</td><td class="propValue">' . $value . '</td></tr>';
	}
	// prints Method arguments
	function _printArgs($property, $value)
	{
		// arguments --> these should end up having arrays aswell
		echo '<tr class="argrow"><td class="propname">' . $property . '</td><td>';
		if (count($value) == 0)	{
			echo '<span>[none]</span>';
		} else { 
			echo '<table>';
			foreach($value as $subproperty => $subvalue) {
				echo '<tr class="argrow"><td class="argname">';
				echo $subvalue['name'] . ' (' . $subvalue['type'] . ') ' . $subvalue['description'];
				echo '</td></tr>';
			}
			echo '</table>';
		}
		echo '</td></tr>';
	}

	// prints form tags to wrap the input tags of the method arguments
	function _printForm()
	{
			echo '<form action="' . $_SERVER[PHP_SELF] . '?methodname=' . $this->_methodname . '" method="POST" id="form">';
			echo '<table class="formtable">';
			echo '<caption class="formcaption">Insert required arguments for: ' . $this->_classpath . '</caption>';
			echo '<th class="formheader">Argument</th><th class="formheader">Value</th>';
			foreach($this->_classConstruct->methodTable[$this->_methodname][arguments] as $key => $name) {
				$this->_printInput($name);
			}
			echo '</table></form>';
	}
	// prints input tags for entry of method arguments
	function _printInput($name)
	{
		echo '<tr class="inputrow"><td class="inputname">' . $name . '</td><td><input class="inputbox" type="text" name="arguments[]" maxlength="65535" /></td></tr>';
	}


	// prints out final result
	function _printResult($result)
	{
		echo '<div id="results">';
		echo '<table class="resultstable">';
		echo '<caption class="resultscaption">Output of: ' . $this->_classpath . '</caption>';
		echo '<tr class="resultsrow">';
		if (is_object($result) || is_array($result) || is_resource($result)) {
			echo '<td>';
			echo '<code>';
			print_r($result);
			echo '</code>';
			echo '</td>';
		} else {
			echo '<td><code class="resultstext">' . $result . '</code></td>';
		}
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}
}
?>