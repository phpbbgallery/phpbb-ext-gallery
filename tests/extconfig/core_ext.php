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

class phpbb_ext_gallery_tests_extconfig_core_ext extends phpbb_ext_gallery_core_extconfig_base
{
	/**
	* Returns a list of all config sets, which should be loaded when the object is constructed
	* The "core" set can be called without specifing the short form on the operations.
	*
	* @return	array		Returns an array with the set names and their short form: "<short> => <class_name>"
	*/
	public function default_sets()
	{
		return array(
			'core' => 'phpbb_ext_gallery_core_tests_extconfig_sets_core',
			'ext' => 'phpbb_ext_gallery_core_tests_extconfig_sets_ext',
		);
	}
}
