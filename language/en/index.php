<?php
/**
*
* @package phpBB Gallery Extension
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'TOTAL_COMMENTS'	=> array(
		0	=> 'Total comments <strong>0</strong>',
		1	=> 'Total comments <strong>%d</strong>',
		2	=> 'Total comments <strong>%d</strong>',
	),
	'TOTAL_IMAGES'		=> array(
		0	=> 'Total images <strong>0</strong>',
		1	=> 'Total images <strong>%d</strong>',
		2	=> 'Total images <strong>%d</strong>',
	),
));
