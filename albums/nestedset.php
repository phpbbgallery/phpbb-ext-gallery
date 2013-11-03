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

	/**
	* Set additional sql where restrictions
	*
	* @param string		$operator		SQL operator that needs to be prepended to sql_where,
	*									if it is not empty.
	* @param string		$column_prefix	Prefix that needs to be prepended to column names
	* @return string		Returns additional where statements to narrow down the tree,
	*						prefixed with operator and prepended column_prefix to column names
	*/
	public function set_sql_where($user_id = 0)
	{
		$this->sql_where = '%salbum_user_id = ' . (int) $user_id;

		return $this;
	}
}
