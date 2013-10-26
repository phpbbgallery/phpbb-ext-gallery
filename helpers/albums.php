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

class albums
{
	const OWNER_PUBLIC		= 0;

	const TYPE_CAT			= 0;
	const TYPE_UPLOAD		= 1;
	const TYPE_CONTEST		= 2;

	const STATUS_OPEN		= 0;
	const STATUS_LOCKED		= 1;

	public function get_owner($owner)
	{
		switch ($owner)
		{
			case 'public':
				return self::OWNER_PUBLIC;
		}
		throw new \phpbbgallery\core\exception('Album owner [' . $owner . '] not found');
	}

	public function get_type($type)
	{
		switch ($type)
		{
			case 'cat':
			case 'category':
				return self::TYPE_CAT;

			case 'upload':
				return self::TYPE_UPLOAD;

			case 'contest':
				return self::TYPE_CONTEST;
		}
		throw new \phpbbgallery\core\exception('Album type [' . $type . '] not found');
	}

	public function get_status($status)
	{
		switch ($status)
		{
			case 'open':
			case 'unlocked':
				return self::STATUS_OPEN;

			case 'locked':
				return self::STATUS_LOCKED;
		}
		throw new \phpbbgallery\core\exception('Album status [' . $status . '] not found');
	}
}
