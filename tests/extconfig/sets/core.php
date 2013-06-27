<?php
/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

class phpbb_ext_gallery_core_tests_extconfig_sets_core implements phpbb_ext_gallery_core_extconfig_sets_interface
{
	/**
	* Returns the prefix that should be used for the set.
	* All config names will be prefixed with this prefix:
	* Example:	prefix:			sampleprefix_
	*			config_name:	myconfig
	*			stored in db:	sampleprefix_myconfig
	* Please remember the length limit of 255 characters for config names
	*
	* @return	string		The set's prefix
	*/
	static public function get_prefix()
	{
		return 'myext_';
	}

	/**
	* Returns the array with all configs and their default values.
	* NOTE:	The values on set() and get() will be casted to the same type as the default value.
	*		The functions inc() and dec() are only available for type integer.
	*
	* @return	array		The array of the configs
	*/
	static public function get_configs()
	{
		return array(
			'core_int_zero'			=> 0,
			'core_int'				=> 3,
			'core_bool'				=> true,
			'core_bool_true'		=> true,
			'core_bool_false'		=> false,
			'core_string_empty'		=> '',
			'core_string'			=> 'abcdefghijklmnopqrstuvwxyz',
			'core_string_zero'		=> '0',
			'core_string_integer'	=> '1',
			'core_string_bool'		=> 'true',

			'core_dynamic'			=> 1,
		);
	}

	/**
	* Returns an array of all config names, that are dynamic.
	* Dynamic values are not cached, but always pulled from the database.
	*
	* @return	array		The array of dynamic configs
	*/
	static public function get_dynamics()
	{
		return array(
			'core_dynamic',
		);
	}
}
