<?php
/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbgallery\core\albums;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class nestedset extends \phpbb\tree\nestedset
{
	/**
	* Construct
	*
	* @param \phpbb\db\driver\driver	$db		Database connection
	* @param \phpbb\lock\db		$lock	Lock class used to lock the table when moving forums around
	* @param string				$table_name		Table name
	*/
	public function __construct(\phpbb\db\driver\driver $db, \phpbb\lock\db $lock, $table_name)
	{
		parent::__construct(
			$db,
			$lock,
			$table_name,
			'ALBUM_NESTEDSET_',
			'',
			array(
				'album_id',
				'album_name',
				'album_type',
			),
			array(
				'item_id'		=> 'album_id',
				'item_parents'	=> 'album_parents',
			)
		);
	}
}
