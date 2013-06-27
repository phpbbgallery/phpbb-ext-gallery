<?php
/**
*
* @package phpBB Gallery Testing
* @copyright (c) 2013 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
require dirname(__FILE__) . '/core_ext.php';
require dirname(__FILE__) . '/sets/core.php';
require dirname(__FILE__) . '/sets/ext.php';

class phpbb_ext_gallery_tests_extconfig_extconfig_test extends phpbb_ext_gallery_database_test_case
{
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/config.xml');
	}

	protected $config;
	protected $ext_config;

	public function setUp()
	{
		parent::setUp();

		$this->config = new phpbb_config(array());
		$this->ext_config = new phpbb_ext_gallery_tests_extconfig_core_ext($this->config, $this->db, 'phpbb_config');
		$this->ext_config->install_set('core');
		$this->ext_config->install_set('ext');
	}

	static public function get_config_name_data()
	{
		return array(
			array('core_int', 'myext_core_int'),
			array(array('core', 'core_int'), 'myext_core_int'),
			array('ext_int', 'myextext_ext_int'),
			array(array('ext', 'ext_int'), 'myextext_ext_int'),
		);
	}

	/**
	* @dataProvider get_config_name_data
	*/
	public function test_get_config_name($name, $expected)
	{
		$this->assertEquals($expected, $this->ext_config->get_config_name($name));
	}

	static public function get_prefix_for_set_data()
	{
		return array(
			array('core', 'myext_'),
			array('ext', 'myextext_'),
		);
	}

	/**
	* @dataProvider get_prefix_for_set_data
	*/
	public function test_get_prefix_for_set($set, $expected)
	{
		$this->assertEquals($expected, $this->ext_config->get_prefix_for_set($set));
	}

	public function test_exists()
	{
		$this->assertEquals(true, $this->ext_config->exists('core_int'), 'Both sets installed #1');
		$this->assertEquals(true, $this->ext_config->exists(array('ext', 'ext_int')), 'Both sets installed #2');

		$this->ext_config->uninstall_set('ext');
		$this->assertEquals(true, $this->ext_config->exists('core_int'), 'Uninstalled ext set #1');
		$this->assertEquals(false, $this->ext_config->exists(array('ext', 'ext_int')), 'Uninstalled ext set #2');

		$this->ext_config->install_set('ext');
		$this->assertEquals(true, $this->ext_config->exists('core_int'), 'Both sets installed #1');
		$this->assertEquals(true, $this->ext_config->exists(array('ext', 'ext_int')), 'Both sets installed #2');
	}

	static public function get_data()
	{
		return array(
			array('core_int_zero', 0),
			array('core_int', 3),
			array('core_bool_true', true),
			array('core_bool_false', false),
			array('core_string_empty', ''),
			array('core_string', 'abcdefghijklmnopqrstuvwxyz'),
			array('core_string_zero', '0'),
			array('core_string_integer', '1'),
			array('core_string_bool', 'true'),
			array('core_does_not_exist', null),
		);
	}

	/**
	* @dataProvider get_data
	*/
	public function test_get($name, $expected)
	{
		$this->assertEquals($expected, $this->ext_config->get($name, true), 'Falling back to default value');
		$this->assertEquals($expected, $this->ext_config->get($name), 'No Fallback');
	}

	static public function set_data()
	{
		return array(
			array('core_int', 'abc', 0),
			array('core_int', '1abc', 1),
			array('core_int', 0, 0),
			array('core_int', true, 1),
			array('core_int', PHP_INT_MAX, PHP_INT_MAX),

			array('core_bool', true, true),
			array('core_bool', false, false),
			array('core_bool', 1, true),
			array('core_bool', '1', true),
			array('core_bool', 0, false),

			array('core_string', 1 ,'1'),
			array('core_string', true, '1'),
			array('core_string', 'foobar', 'foobar'),
		);
	}

	/**
	* @dataProvider set_data
	*/
	public function test_set($name, $new_value, $expected)
	{
		$this->ext_config->set($name, $new_value);
		$this->assertEquals($expected, $this->ext_config->get($name));
	}

	static public function is_dynamic_data()
	{
		return array(
			array('core_int', false),
			array('core_dynamic', true),
			array('ext_int', false),
			array('ext_dynamic', true),
			array(array('ext', 'ext_int'), false),
			array(array('ext', 'ext_dynamic'), true),
		);
	}

	/**
	* @dataProvider is_dynamic_data
	*/
	public function test_is_dynamic($name, $expected)
	{
		$this->assertEquals($expected, $this->ext_config->is_dynamic($name));
	}

	static public function decrement_increment_failed_data()
	{
		return array(
			array('core_bool', 1),
			array('ext_bool', 1),
			array(array('ext', 'ext_bool'), 1),

			array('core_string', 1),
			array('ext_string', 1),
			array(array('ext', 'ext_string'), 1),
		);
	}

	/**
	* @dataProvider decrement_increment_failed_data
	*/
	public function test_decrement_increment_failed($name, $decrement)
	{
		$this->assertEquals(false, $this->ext_config->dec($name, $decrement));
		$this->assertEquals(false, $this->ext_config->inc($name, $decrement));
	}

	static public function decrement_data()
	{
		return array(
			array('core_int', 3, 10, -7),
			array('ext_int', 3, 10, -7),
			array(array('ext', 'ext_int'), 3, 10, -7),
		);
	}

	/**
	* @dataProvider decrement_data
	*/
	public function test_decrement($name, $previous, $decrement, $expected)
	{
		$this->assertEquals($previous, $this->ext_config->get($name));
		$this->assertEquals(true, $this->ext_config->dec($name, $decrement));
		$this->assertEquals($expected, $this->ext_config->get($name));
	}

	static public function increment_data()
	{
		return array(
			array('core_int', 10, 13),
			array('ext_int', 10, 13),
			array(array('ext', 'ext_int'), 10, 13),
		);
	}

	/**
	* @dataProvider increment_data
	*/
	public function test_increment($name, $increment, $expected)
	{
		$this->assertEquals(true, $this->ext_config->inc($name, $increment));
		$this->assertEquals($expected, $this->ext_config->get($name));
	}
}
