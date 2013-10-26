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

class display
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbbgallery\core\albums\nestedset */
	protected $nestedset;

	/** @var \phpbbgallery\core\auth\auth_interface */
	protected $gallery_auth;

	/** @var \phpbbgallery\core\helpers\albums */
	protected $albums;

	/** @var \phpbbgallery\core\helpers\tables */
	protected $tables;

	/**
	* Constructor
	*
	* @param	\phpbb\config\config			$config
	* @param	\phpbb\controller\helper		$helper
	* @param	\phpbb\db\driver\driver			$db
	* @param	\phpbb\template\template		$template
	* @param	\phpbb\user						$user
	* @param	\phpbbgallery\core\albums\nestedset		$nestedset
	* @param	\phpbbgallery\core\auth\auth_interface	$gallery_auth
	* @param	\phpbbgallery\core\helpers\albums		$albums
	* @param	\phpbbgallery\core\helpers\tables		$tables
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\db\driver\driver $db, \phpbb\template\template $template, \phpbb\user $user, \phpbbgallery\core\albums\nestedset $nestedset, \phpbbgallery\core\auth\auth_interface $gallery_auth, \phpbbgallery\core\helpers\albums $albums, \phpbbgallery\core\helpers\tables $tables)
	{
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->nestedset = $nestedset;
		$this->gallery_auth = $gallery_auth;
		$this->albums = $albums;
		$this->tables = $tables;
	}

	/**
	* Display albums
	*
	* borrowed from phpBB3
	* @author: phpBB Group
	* @function: display_forums
	*/
	public function display_albums($root_data = '', $display_moderators = true, $return_moderators = false)
	{
		$album_rows = $subalbums = $album_ids = $album_ids_moderator = $album_moderators = $active_album_ary = array();
		$parent_id = $visible_albums = 0;
		$sql_from = '';
		$mode = request_var('mode', '');

		// Mark albums read?
		$mark_read = request_var('mark', '');

		if ($mark_read == 'all')
		{
			$mark_read = '';
		}

		if (!$root_data)
		{
			if ($mark_read == 'albums')
			{
				$mark_read = 'all';
			}
			$root_data = array('album_id' => 0);
			$sql_where = 'a.album_user_id = ' . $this->albums->get_owner('public');
		}
		else if ($root_data == 'personal')
		{
			if ($mark_read == 'albums')
			{
				$mark_read = 'all';
			}
			$root_data = array('album_id' => 0);//@todo: I think this is incorrect!?
			$sql_where = 'a.album_user_id > ' . phpbb_ext_gallery_core_album::PUBLIC_ALBUM;
			$num_pegas = phpbb_gallery_config::get('num_pegas');
			$first_char = request_var('first_char', '');
			if ($first_char == 'other')
			{
				// Loop the ASCII: a-z
				for ($i = 97; $i < 123; $i++)
				{
					$sql_where .= ' AND u.username_clean NOT ' . $db->sql_like_expression(chr($i) . $db->any_char);
				}
			}
			else if ($first_char)
			{
				$sql_where .= ' AND u.username_clean ' . $db->sql_like_expression(substr($first_char, 0, 1) . $db->any_char);
			}

			if ($first_char)
			{
				// We do not view all personal albums, so we need to recount, for the pagination.
				$sql_array = array(
					'SELECT'		=> 'count(a.album_id) as pgalleries',
					'FROM'			=> array(GALLERY_ALBUMS_TABLE => 'a'),

					'LEFT_JOIN'		=> array(
						array(
							'FROM'		=> array(USERS_TABLE => 'u'),
							'ON'		=> 'u.user_id = a.album_user_id',
						),
					),

					'WHERE'			=> 'a.parent_id = 0 AND ' . $sql_where,
				);
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query($sql);
				$num_pegas = $db->sql_fetchfield('pgalleries');
				$db->sql_freeresult($result);
			}

			$mode_personal = true;
			$start = request_var('start', 0);
			$limit = phpbb_gallery_config::get('pegas_per_page');
			$template->assign_vars(array(
				'PAGINATION'				=> generate_pagination($phpbb_ext_gallery->url->append_sid('index', 'mode=' . $mode . (($first_char) ? '&amp;first_char=' . $first_char : '')), $num_pegas, $limit, $start),
				'TOTAL_PGALLERIES_SHORT'	=> $user->lang('TOTAL_PEGAS_SHORT_SPRINTF', $num_pegas),
				'PAGE_NUMBER'				=> on_page($num_pegas, $limit, $start),
			));
		}
		else
		{
			$sql_where = 'a.left_id > ' . $root_data['left_id'] . ' AND a.left_id < ' . $root_data['right_id'] . ' AND a.album_user_id = ' . $root_data['album_user_id'];
		}

		$sql_array = array(
			'SELECT'	=> 'a.*, at.mark_time',
			'FROM'		=> array($this->tables->get('albums') => 'a'),

			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array($this->tables->get('album_tracking') => 'at'),
					'ON'	=> 'at.user_id = ' . $this->user->data['user_id'] . ' AND a.album_id = at.album_id'
				)
			),

			'ORDER_BY'	=> 'a.album_user_id, a.left_id',
		);

		if (isset($mode_personal))
		{
			$sql_array['LEFT_JOIN'][] = array(
				'FROM'	=> array(USERS_TABLE => 'u'),
				'ON'	=> 'u.user_id = a.album_user_id',
			);
			$sql_array['ORDER_BY'] = 'u.username_clean, a.left_id';
		}

		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array($this->tables->get('contests') => 'c'),
			'ON'	=> 'c.contest_album_id = a.album_id',
		);
		$sql_array['SELECT'] = $sql_array['SELECT'] . ', c.contest_marked';


		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> $sql_array['SELECT'],
			'FROM'		=> $sql_array['FROM'],
			'LEFT_JOIN'	=> $sql_array['LEFT_JOIN'],
			'WHERE'		=> $sql_where,
			'ORDER_BY'	=> $sql_array['ORDER_BY'],
		));

		$result = $this->db->sql_query($sql);

		$album_tracking_info = array();
		$branch_root_id = $root_data['album_id'];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$album_id = $row['album_id'];

			// Mark albums read?
			if ($mark_read == 'albums' || $mark_read == 'all')
			{
				if ($this->gallery_auth->acl_check('a_list', $album_id, $row['album_user_id']))
				{
					$album_ids[] = $album_id;
					continue;
				}
			}

			// Category with no members
			if (!$row['album_type'] && ($row['left_id'] + 1 == $row['right_id']))
			{
				continue;
			}

			// Skip branch
			if (isset($right_id))
			{
				if ($row['left_id'] < $right_id)
				{
					continue;
				}
				unset($right_id);
			}

			if (!$this->gallery_auth->acl_check('a_list', $album_id, $row['album_user_id']))
			{
				// if the user does not have permissions to list this album, skip everything until next branch
				$right_id = $row['right_id'];
				continue;
			}

			$album_tracking_info[$album_id] = (!empty($row['mark_time'])) ? $row['mark_time'] : $phpbb_ext_gallery->user->get_data('user_lastmark');

			$row['album_images'] = $row['album_images'];
			$row['album_images_real'] = $row['album_images_real'];

			if ($row['parent_id'] == $root_data['album_id'] || $row['parent_id'] == $branch_root_id)
			{
				if ($row['album_type'])
				{
					$album_ids_moderator[] = (int) $album_id;
				}

				// Direct child of current branch
				$parent_id = $album_id;
				$album_rows[$album_id] = $row;

				if (!$row['album_type'] && $row['parent_id'] == $root_data['album_id'])
				{
					$branch_root_id = $album_id;
				}
				$album_rows[$parent_id]['album_id_last_image'] = $row['album_id'];
				$album_rows[$parent_id]['album_type_last_image'] = $row['album_type'];
				$album_rows[$parent_id]['album_contest_marked'] = $row['contest_marked'];
				$album_rows[$parent_id]['orig_album_last_image_time'] = $row['album_last_image_time'];
			}
			else if ($row['album_type'])
			{
				$subalbums[$parent_id][$album_id]['display'] = ($row['display_on_index']) ? true : false;
				$subalbums[$parent_id][$album_id]['name'] = $row['album_name'];
				$subalbums[$parent_id][$album_id]['orig_album_last_image_time'] = $row['album_last_image_time'];
				$subalbums[$parent_id][$album_id]['children'] = array();

				if (isset($subalbums[$parent_id][$row['parent_id']]) && !$row['display_on_index'])
				{
					$subalbums[$parent_id][$row['parent_id']]['children'][] = $album_id;
				}

				$album_rows[$parent_id]['album_images'] += $row['album_images'];
				$album_rows[$parent_id]['album_images_real'] += $row['album_images_real'];

				if ($row['album_last_image_time'] > $album_rows[$parent_id]['album_last_image_time'])
				{
					$album_rows[$parent_id]['album_last_image_id'] = $row['album_last_image_id'];
					$album_rows[$parent_id]['album_last_image_name'] = $row['album_last_image_name'];
					$album_rows[$parent_id]['album_last_image_time'] = $row['album_last_image_time'];
					$album_rows[$parent_id]['album_last_user_id'] = $row['album_last_user_id'];
					$album_rows[$parent_id]['album_last_username'] = $row['album_last_username'];
					$album_rows[$parent_id]['album_last_user_colour'] = $row['album_last_user_colour'];
					$album_rows[$parent_id]['album_type_last_image'] = $row['album_type'];
					$album_rows[$parent_id]['album_contest_marked'] = $row['contest_marked'];
					$album_rows[$parent_id]['album_id_last_image'] = $album_id;
				}
			}
		}
		$this->db->sql_freeresult($result);

		// Handle marking albums
		if ($mark_read == 'albums' || $mark_read == 'all')
		{
			$redirect = build_url('mark', 'hash');
			$token = request_var('hash', '');
			if (check_link_hash($token, 'global'))
			{
				if ($mark_read == 'all')
				{
					phpbb_gallery_misc::markread('all');
					$message = sprintf($user->lang['RETURN_INDEX'], '<a href="' . $redirect . '">', '</a>');
				}
				else
				{
					phpbb_gallery_misc::markread('albums', $album_ids);
					$message = sprintf($user->lang['RETURN_ALBUM'], '<a href="' . $redirect . '">', '</a>');
				}
				meta_refresh(3, $redirect);
				trigger_error($user->lang['ALBUMS_MARKED'] . '<br /><br />' . $message);
			}
			else
			{
				$message = sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>');
				meta_refresh(3, $redirect);
				trigger_error($message);
			}
		}


		// Grab moderators ... if necessary
		if ($display_moderators)
		{
			if ($return_moderators)
			{
				$album_ids_moderator[] = $root_data['album_id'];
			}
			self::get_moderators($album_moderators, $album_ids_moderator);
		}

		// Used to tell whatever we have to create a dummy category or not.
		$last_catless = true;
		foreach ($album_rows as $row)
		{
			// Empty category
			if (($row['parent_id'] == $root_data['album_id']) && ($row['album_type'] == $this->albums->get_type('category')))
			{
				$this->template->assign_block_vars('albumrow', array(
					'S_IS_CAT'				=> true,
					'ALBUM_ID'				=> $row['album_id'],
					'ALBUM_NAME'			=> $row['album_name'],
					'ALBUM_DESC'			=> generate_text_for_display($row['album_desc'], $row['album_desc_uid'], $row['album_desc_bitfield'], $row['album_desc_options']),
					'ALBUM_FOLDER_IMG'		=> '',
					'ALBUM_FOLDER_IMG_SRC'	=> '',
					'U_VIEWALBUM'			=> $this->helper->url('gallery/album/' . $row['album_id']))
				);

				continue;
			}

			$visible_albums++;
			if (($mode == 'personal') && (($visible_albums <= $start) || ($visible_albums > ($start + $limit))))
			{
				continue;
			}

			$album_id = $row['album_id'];
			$album_unread = (isset($album_tracking_info[$album_id]) && ($row['orig_album_last_image_time'] > $album_tracking_info[$album_id]) && ($user->data['user_id'] != ANONYMOUS)) ? true : false;

			$folder_image = $folder_alt = $l_subalbums = '';
			$subalbums_list = array();

			// Generate list of subalbums if we need to
			if (isset($subalbums[$album_id]))
			{
				foreach ($subalbums[$album_id] as $subalbum_id => $subalbum_row)
				{
					$subalbum_unread = (isset($album_tracking_info[$subalbum_id]) && $subalbum_row['orig_album_last_image_time'] > $album_tracking_info[$subalbum_id] && ($user->data['user_id'] != ANONYMOUS)) ? true : false;

					if (!$subalbum_unread && !empty($subalbum_row['children']) && ($user->data['user_id'] != ANONYMOUS))
					{
						foreach ($subalbum_row['children'] as $child_id)
						{
							if (isset($album_tracking_info[$child_id]) && $subalbums[$album_id][$child_id]['orig_album_last_image_time'] > $album_tracking_info[$child_id])
							{
								// Once we found an unread child album, we can drop out of this loop
								$subalbum_unread = true;
								break;
							}
						}
					}

					if ($subalbum_row['display'] && $subalbum_row['name'])
					{
						$subalbums_list[] = array(
							'link'		=> $this->helper->url('gallery/album/' . $subalbum_id),
							'name'		=> $subalbum_row['name'],
							'unread'	=> $subalbum_unread,
						);
					}
					else
					{
						unset($subalbums[$album_id][$subalbum_id]);
					}

					if ($subalbum_unread)
					{
						$album_unread = true;
					}
				}

				$l_subalbums = (sizeof($subalbums[$album_id]) == 1) ? $this->user->lang('SUBALBUM') . ': ' : $this->user->lang('SUBALBUMS') . ': ';
				$folder_image = ($album_unread) ? 'forum_unread_subforum' : 'forum_read_subforum';
			}
			else
			{
				$folder_alt = ($album_unread) ? 'NEW_IMAGES' : 'NO_NEW_IMAGES';
				$folder_image = ($album_unread) ? 'forum_unread' : 'forum_read';
			}
			if ($row['album_status'] == $this->albums->get_status('locked'))
			{
				$folder_image = ($album_unread) ? 'forum_unread_locked' : 'forum_read_locked';
				$folder_alt = 'ALBUM_LOCKED';
			}

			// Create last post link information, if appropriate
			if ($row['album_last_image_id'])
			{
				$lastimage_time = $lastimage_image_id = $lastimage_album_id = $lastimage_album_type = $lastimage_contest_marked = 0;
				$lastimage_name = $lastimage_uc_fake_thumbnail = $lastimage_uc_thumbnail = $lastimage_uc_name = $lastimage_uc_icon = '';

				$lastimage_name = $row['album_last_image_name'];
				$lastimage_time = $this->user->format_date($row['album_last_image_time']);
				$lastimage_image_id = $row['album_last_image_id'];
				$lastimage_album_id = $row['album_id_last_image'];
				$lastimage_album_type = $row['album_type_last_image'];
				$lastimage_contest_marked = $row['album_contest_marked'];
				#$lastimage_uc_fake_thumbnail = phpbb_ext_gallery_core_image::generate_link('fake_thumbnail', $phpbb_ext_gallery->config->get('link_thumbnail'), $lastimage_image_id, $lastimage_name, $lastimage_album_id);
				#$lastimage_uc_thumbnail = phpbb_ext_gallery_core_image::generate_link('thumbnail', $phpbb_ext_gallery->config->get('link_thumbnail'), $lastimage_image_id, $lastimage_name, $lastimage_album_id);
				#$lastimage_uc_name = phpbb_ext_gallery_core_image::generate_link('image_name', $phpbb_ext_gallery->config->get('link_image_name'), $lastimage_image_id, $lastimage_name, $lastimage_album_id);
				#$lastimage_uc_icon = phpbb_ext_gallery_core_image::generate_link('lastimage_icon', $phpbb_ext_gallery->config->get('link_image_icon'), $lastimage_image_id, $lastimage_name, $lastimage_album_id);
			}
			else
			{
				$lastimage_time = $lastimage_image_id = $lastimage_album_id = $lastimage_album_type = $lastimage_contest_marked = 0;
				$lastimage_name = $lastimage_uc_fake_thumbnail = $lastimage_uc_thumbnail = $lastimage_uc_name = $lastimage_uc_icon = '';
			}

			// Output moderator listing ... if applicable
			$l_moderator = $moderators_list = '';
			if ($display_moderators && !empty($album_moderators[$album_id]))
			{
				$l_moderator = (sizeof($album_moderators[$album_id]) == 1) ? $this->user->lang('MODERATOR') : $this->user->lang('MODERATORS');
				$moderators_list = implode(', ', $album_moderators[$album_id]);
			}

			$s_subalbums_list = array();
			foreach ($subalbums_list as $subalbum)
			{
				$s_subalbums_list[] = '<a href="' . $subalbum['link'] . '" class="subforum ' . (($subalbum['unread']) ? 'unread' : 'read') . '" title="' . (($subalbum['unread']) ? $this->user->lang('NEW_IMAGES') : $this->user->lang('NO_NEW_IMAGES')) . '">' . $subalbum['name'] . '</a>';
			}
			$s_subalbums_list = (string) implode(', ', $s_subalbums_list);
			$catless = ($row['parent_id'] == $root_data['album_id']) ? true : false;

			$s_username_hidden = ($lastimage_album_type == $this->albums->get_type('contest')) && $lastimage_contest_marked && !$this->gallery_auth->acl_check('m_status', $album_id, $row['album_user_id']) && ($this->user->data['user_id'] != $row['album_last_user_id'] || $row['album_last_user_id'] == ANONYMOUS);

			$this->template->assign_block_vars('albumrow', array(
				'S_IS_CAT'			=> false,
				'S_NO_CAT'			=> $catless && !$last_catless,
				'S_LOCKED_ALBUM'	=> ($row['album_status'] == $this->albums->get_status('locked')) ? true : false,
				'S_UNREAD_ALBUM'	=> ($album_unread) ? true : false,
				'S_LIST_SUBALBUMS'	=> ($row['display_subalbum_list']) ? true : false,
				'S_SUBALBUMS'		=> (sizeof($subalbums_list)) ? true : false,

				'ALBUM_ID'				=> $row['album_id'],
				'ALBUM_NAME'			=> $row['album_name'],
				'ALBUM_DESC'			=> generate_text_for_display($row['album_desc'], $row['album_desc_uid'], $row['album_desc_bitfield'], $row['album_desc_options']),
				'IMAGES'				=> $row['album_images'],
				'UNAPPROVED_IMAGES'		=> ($this->gallery_auth->acl_check('m_status', $album_id, $row['album_user_id'])) ? ($row['album_images_real'] - $row['album_images']) : 0,
				'ALBUM_IMG_STYLE'		=> $folder_image,
				'ALBUM_FOLDER_IMG'		=> $this->user->img($folder_image, $folder_alt),
				'ALBUM_FOLDER_IMG_ALT'	=> isset($this->user->lang[$folder_alt]) ? $this->user->lang[$folder_alt] : '',
				'ALBUM_IMAGE'			=> ($row['album_image']) ? $phpbb_ext_gallery->url->path('phpbb') . $row['album_image'] : '',
				'LAST_IMAGE_TIME'		=> $lastimage_time,
				'LAST_USER_FULL'		=> ($s_username_hidden) ? $this->user->lang['CONTEST_USERNAME'] : get_username_string('full', $row['album_last_user_id'], $row['album_last_username'], $row['album_last_user_colour']),
				'UC_THUMBNAIL'			=> ($this->config['phpbb_gallery_mini_thumbnail_disp']) ? $lastimage_uc_thumbnail : '',
				'UC_FAKE_THUMBNAIL'		=> ($this->config['phpbb_gallery_mini_thumbnail_disp']) ? $lastimage_uc_fake_thumbnail : '',
				'UC_IMAGE_NAME'			=> $lastimage_uc_name,
				'UC_LASTIMAGE_ICON'		=> $lastimage_uc_icon,
				'ALBUM_COLOUR'			=> get_username_string('colour', $row['album_last_user_id'], $row['album_last_username'], $row['album_last_user_colour']),
				'MODERATORS'			=> $moderators_list,
				'SUBALBUMS'				=> $s_subalbums_list,

				'L_SUBALBUM_STR'		=> $l_subalbums,
				'L_ALBUM_FOLDER_ALT'	=> $folder_alt,
				'L_MODERATOR_STR'		=> $l_moderator,

				'U_VIEWALBUM'			=> $this->helper->url('gallery/album/' . $row['album_id']),
			));

			// Assign subforums loop for style authors
			foreach ($subalbums_list as $subalbum)
			{
				$this->template->assign_block_vars('albumrow.subalbum', array(
					'U_SUBALBUM'	=> $subalbum['link'],
					'SUBALBUM_NAME'	=> $subalbum['name'],
					'S_UNREAD'		=> $subalbum['unread'],
				));
			}

			$last_catless = $catless;
		}

		$this->template->assign_vars(array(
			'U_MARK_ALBUMS'		=> ($this->user->data['is_registered']) ? $this->helper->url('gallery/album/' . $root_data['album_id'] . 'markread', 'hash=' . generate_link_hash('global')) : '',
			'S_HAS_SUBALBUM'	=> ($visible_albums) ? true : false,
			'L_SUBFORUM'		=> ($visible_albums == 1) ? $this->user->lang('SUBALBUM') : $this->user->lang('SUBALBUMS'),
			'LAST_POST_IMG'		=> $this->user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
			'FAKE_THUMB_SIZE'	=> $this->config['mini_thumbnail_size'],
		));

		if ($return_moderators)
		{
			return array($active_album_ary, $album_moderators);
		}

		return array($active_album_ary, array());
	}
}
