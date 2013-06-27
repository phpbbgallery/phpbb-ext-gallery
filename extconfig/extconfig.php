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

class phpbb_ext_gallery_core_extconfig extends phpbb_ext_gallery_core_extconfig_base
{
	/**
	* Returns a list of all config sets, which should be loaded when the object is constructed
	* The "core" set can be called without specifing the short form on the operations.
	*
	* @return	array		Returns an array with the set names and their short form: "<short> => <class_name>"
	*/
	public function default_sets()
	{
		global $phpbb_dispatcher;

		$default_sets = array(
			'core',
		);

		$additional_config_sets = array();

		$vars = array('additional_config_sets');
		extract($phpbb_dispatcher->trigger_event('gallery.core.config.load_config_sets', compact($vars)));

		return array_merge($default_sets, $additional_config_sets);
	}
}
