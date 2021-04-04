<?php
/*
	Authenticate class - Superclass for any FlashCredentials class to provide support for setCredentials()
*/
class Authenticate
{

	function isAuthenticated ()
	{
		if (isset($_SESSION['amfphp_username'])) {
			return true;
		} else {
			return false;
		}
	}
    /**
     * Returns true if the client is authenticated
     *
     * @ param    roles    comma delimited list of the methods roles
     */
	function isUserInRole($roles)
	{
		// split the method roles into an array
		$methodRoles = explode(",", $roles);
		foreach($methodRoles as $key => $role) {
			$methodRoles[$key] = strtolower(trim($role));
		}
		// split the users session roles into an array
		$userRoles = explode(",", $_SESSION['amfphp_roles']);
		foreach($userRoles as $key => $role) {
			$userRoles[$key] = strtolower(trim($role));
			if(in_array($userRoles[$key], $methodRoles)) {
				return true;
			}
		}
		return false;
	}

	function sendError()
	{
		trigger_error("User does not have access to this method", E_USER_ERROR);
	}

	function login($name, $roles)
	{
		session_start();
		$_SESSION['amfphp_username'] = $name;
		$_SESSION['amfphp_roles'] = $roles;
	}
	function logout()
	{
		unset($_SESSION['amfphp_username']);
		unset($_SESSION['amfphp_roles']);
	}
}
?>