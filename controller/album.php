<?php

/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbgallery\core\controller;

class album
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var \phpbbgallery\core\albums\display */
	protected $display;

	/** @var \phpbbgallery\core\auth\auth_interface */
	protected $gallery_auth;

	/** @var \phpbbgallery\core\helpers\albums */
	protected $albums;

	/** @var \phpbbgallery\core\helpers\tables */
	protected $tables;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth				$auth
	* @param \phpbb\config\config			$config
	* @param \phpbb\controller\helper		$helper
	* @param \phpbb\db\driver\driver		$db
	* @param \phpbb\request\request			$request
	* @param \phpbb\template\template		$template
	* @param \phpbb\user					$user
	* @param string			$phpbb_root_path
	* @param string			$php_ext
	* @param \phpbbgallery\core\albums\display	$display
	* @param \phpbbgallery\core\auth			$gallery_auth
	* @param \phpbbgallery\core\helpers\albums	$albums
	* @param \phpbbgallery\core\helpers\tables	$tables
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\db\driver\driver $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext, \phpbbgallery\core\albums\display $display, \phpbbgallery\core\auth\auth_interface $gallery_auth, \phpbbgallery\core\helpers\albums $albums, \phpbbgallery\core\helpers\tables $tables)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;

		$this->display = $display;
		$this->gallery_auth = $gallery_auth;
		$this->albums = $albums;
		$this->tables = $tables;
	}

	/**
	* Display an album
	*
	* Also displays the subalbums
	*
	* @return Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function display($album_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->tables->get('albums') . '
			WHERE album_id = ' . $album_id;
		$result = $this->db->sql_query($sql);
		$album_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$album_data)
		{
			trigger_error('ALBUM_NOT_EXISTS');
		}

		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->url('gallery'),
			'FORUM_NAME'		=> $this->user->lang('EXT_GALLERY'),
		));

		$this->display->display_navlinks($album_data);

		$this->display->display_albums($album_data);

		return $this->helper->render('album_body.html', $this->user->lang('EXT_GALLERY'));
	}
}
