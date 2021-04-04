<?php
$RemotingSubClass = "
class RemotingService_$SuperClass extends $SuperClass
{
	var  \$__classPath;
	function RemotingService_$SuperClass()
	{
		\$arguments = func_get_args();
		call_user_func_array(array(&\$this, \"__construct\"), \$arguments);
	}

	function __construct(\$ParentClassName)
	{
		if (method_exists(\$this, \$ParentClassName)) {
			\$this->\$ParentClassName();
		}
		
		if (!is_array(\$this->methodTable))	{
			\$this->methodTable = array();
		}
		
		\$this->methodTable[\"__describeService\"] = array(
			\"access\" => \"remote\",
			\"description\" => \"This is the main method that returns the descriptors for the service class.\"
		);
	}
	
	function setClassPath(\$cp)
	{
		\$this->__classPath = \$cp;
	}
	
	function __describeService()
	{
		\$description = array();
		\$description[\"version\"] = \"1.0\";
		\$description[\"address\"] = \"\$this->__classPath\";
		\$description[\"functions\"] = array();
		
		\$count = 0;
		foreach (\$this->methodTable as \$key => \$value) {
			if (\$value[\"access\"] = \"remote\" && \$key != \"__describeService\")	{
				\$description[\"functions\"][\$count] = array(
					\"description\" => \$value[\"description\"],
					\"name\" => \$key,
					\"version\" => \"1.0\",
					\"returns\" => \$value[\"returns\"],
					\"arguments\" => \$value[\"arguments\"]
				);
			}
			\$count++;
		}
		return \$description;		
	}
}";
?>