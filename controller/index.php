<?php

/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbgallery\core\controller;

class index
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
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\db\driver\driver $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext, \phpbbgallery\core\albums\display $display, \phpbbgallery\core\auth\auth_interface $gallery_auth, \phpbbgallery\core\helpers\albums $albums)
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
	}

	/**
	* Display basic gallery index
	*
	* Also displays Birthdays, Who is Online, Loginbox, Statistics
	*
	* @return Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function gallery()
	{
		$this->common_index();

		$this->display->display_albums('');

		return $this->helper->render('gallery_body.html', $this->user->lang('EXT_GALLERY'));
	}

	/**
	* Displays Birthdays, Who is Online, Loginbox, Statistics
	*
	* @return null
	*/
	protected function common_index()
	{
		$this->user->add_lang_ext('phpbbgallery/core', 'index');

		if ($this->config['phpbb_gallery_disp_birthdays'])
		{
			$this->generate_birthday_list();
		}

		if ($this->config['phpbb_gallery_disp_whoisonline'])
		{
			$this->generate_group_legend();
		}

		$this->template->assign_vars(array(
			'S_DISP_LOGIN'			=> $this->config['phpbb_gallery_disp_login'],
			'S_DISP_WHOISONLINE'	=> $this->config['phpbb_gallery_disp_whoisonline'],

			'TOTAL_IMAGES'			=> ($this->config['phpbb_gallery_disp_statistic']) ? $this->user->lang('TOTAL_IMAGES', (int) $this->config['phpbb_gallery_num_images']) : '',
			'TOTAL_COMMENTS'		=> ($this->config['phpbb_gallery_allow_comments']) ? $this->user->lang('TOTAL_COMMENTS', (int) $this->config['phpbb_gallery_num_comments']) : '',
			'TOTAL_PGALLERIES'		=> ($this->gallery_auth->acl_check('a_list', $this->albums->get_owner('personal'))) ? $this->user->lang('TOTAL_PEGAS_SPRINTF', $this->config['phpbb_gallery_num_pegas']) : '',
			'NEWEST_PGALLERIES'		=> ($this->gallery_auth->acl_check('a_list', $this->albums->get_owner('personal')) || $this->config['phpbb_gallery_num_pegas']) ? $this->user->lang('NEWEST_PGALLERY', get_username_string('full', $this->config['phpbb_gallery_newest_pega_user_id'], $this->config['phpbb_gallery_newest_pega_username'], $this->config['phpbb_gallery_newest_pega_user_colour'], '', $this->helper->url('gallery/album/' . $this->config['phpbb_gallery_newest_pega_album_id']))) : '',
		));
	}

	/**
	* Generates the birthday list for the index
	*
	* @return null
	*/
	protected function generate_birthday_list()
	{
		$birthday_list = array();
		if ($this->config['phpbb_gallery_disp_birthdays'] && $this->config['allow_birthdays'] && $this->auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel'))
		{
			$time = $this->user->create_datetime();
			$now = phpbb_gmgetdate($time->getTimestamp() + $time->getOffset());

			// Display birthdays of 29th february on 28th february in non-leap-years
			$leap_year_birthdays = '';
			if ($now['mday'] == 28 && $now['mon'] == 2 && !$time->format('L'))
			{
				$leap_year_birthdays = " OR u.user_birthday LIKE '" . $this->db->sql_escape(sprintf('%2d-%2d-', 29, 2)) . "%'";
			}

			$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_birthday
				FROM ' . USERS_TABLE . ' u
				LEFT JOIN ' . BANLIST_TABLE . " b ON (u.user_id = b.ban_userid)
				WHERE (b.ban_id IS NULL
					OR b.ban_exclude = 1)
					AND (u.user_birthday LIKE '" . $this->db->sql_escape(sprintf('%2d-%2d-', $now['mday'], $now['mon'])) . "%' $leap_year_birthdays)
					AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$birthday_username	= get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				$birthday_year		= (int) substr($row['user_birthday'], -4);
				$birthday_age		= ($birthday_year) ? max(0, $now['year'] - $birthday_year) : '';

				$this->template->assign_block_vars('birthdays', array(
					'USERNAME'	=> $birthday_username,
					'AGE'		=> $birthday_age,
				));

				// For 3.0 compatibility
				if ($age = (int) substr($row['user_birthday'], -4))
				{
					$birthday_list[] = $birthday_username . (($birthday_year) ? ' (' . $birthday_age . ')' : '');
				}
			}
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars(array(
			'BIRTHDAY_LIST'	=> (empty($birthday_list)) ? '' : implode($user->lang['COMMA_SEPARATOR'], $birthday_list),
		));
	}

	/**
	* Generates the group legend list for the index
	*
	* @return null
	*/
	protected function generate_group_legend()
	{
		$order_legend = ($this->config['legend_sort_groupname']) ? 'group_name' : 'group_legend';
		// Grab group details for legend display
		if ($this->auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'))
		{
			$sql = 'SELECT group_id, group_name, group_colour, group_type, group_legend
				FROM ' . GROUPS_TABLE . '
				WHERE group_legend > 0
				ORDER BY ' . $order_legend . ' ASC';
		}
		else
		{
			$sql = 'SELECT g.group_id, g.group_name, g.group_colour, g.group_type, g.group_legend
				FROM ' . GROUPS_TABLE . ' g
				LEFT JOIN ' . USER_GROUP_TABLE . ' ug
					ON (
						g.group_id = ug.group_id
						AND ug.user_id = ' . $this->user->data['user_id'] . '
						AND ug.user_pending = 0
					)
				WHERE g.group_legend > 0
					AND (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . $this->user->data['user_id'] . ')
				ORDER BY g.' . $order_legend . ' ASC';
		}
		$result = $this->db->sql_query($sql);

		$legend = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$colour_text = ($row['group_colour']) ? ' style="color:#' . $row['group_colour'] . '"' : '';
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];

			if ($row['group_name'] == 'BOTS' || ($this->user->data['user_id'] != ANONYMOUS && !$this->auth->acl_get('u_viewprofile')))
			{
				$legend[] = '<span' . $colour_text . '>' . $group_name . '</span>';
			}
			else
			{
				$legend[] = '<a' . $colour_text . ' href="' . append_sid("{$this->root_path}memberlist.{$this->php_ext}", 'mode=group&amp;g=' . $row['group_id']) . '">' . $group_name . '</a>';
			}
		}
		$this->db->sql_freeresult($result);


		$this->template->assign_vars(array(
			'LEGEND'	=> implode($this->user->lang['COMMA_SEPARATOR'], $legend),
		));
	}
}
