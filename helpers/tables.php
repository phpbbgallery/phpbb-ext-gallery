<?php
/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbgallery\core\helpers;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class tables
{
	/**
	* Array with a map from short table name to full db table name
	* @var array
	*/
	protected $tables;

	/**
	* Constructor
	*
	* @param string $albums
	*/
	public function __construct($albums, $album_tracking, $contests, $moderators_cache)
	{
		$this->tables = array(
			'albums'			=> $albums,
			'album_tracking'	=> $album_tracking,
			'contests'			=> $contests,
			'moderators_cache'	=> $moderators_cache,
		);
	}

	public function get($table_name)
	{
		if (isset($this->tables[$table_name]))
		{
			return $this->tables[$table_name];
		}
		throw new \phpbbgallery\core\exception('Table [' . $owner . '] not found');
	}
}
