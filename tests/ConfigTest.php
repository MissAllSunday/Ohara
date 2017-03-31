<?php

use Suki\Ohara;

function mockGlobals()
{
	global $modSettings;

	$modSettings['_configOharaDummyConfig'] = '{"_availableHooks":{"memberContext":"integrate_member_context","generalSettings":"integrate_general_mod_settings","displayContext":"integrate_prepare_display_context","profile":"integrate_load_custom_profile_fields"}}';
}

class OharaDummyConfig extends \Suki\Ohara
{
	public $name = 'OharaDummyConfig';
	public $useConfig = true;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class ConfigTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		mockGlobals();
		$t = new OharaDummyConfig;
		$this->_ohara = $t['config'];
	}

	protected function similarArrays($a, $b)
	{
		if(is_array($a) && is_array($b))
		{
			if(count(array_diff(array_keys($a), array_keys($b))) > 0)
				return false;

			foreach($a as $k => $v)
				if(!$this->similarArrays($v, $b[$k]))
					return false;

			return true;
		}

		else
			return $a === $b;
	}

	public function testgetConfig()
	{
		$result = $this->_ohara->getConfig();

		$this->assertTrue($this->similarArrays(array(
			'_availableHooks' => array(
				'memberContext' => 'integrate_member_context',
				'generalSettings' => 'integrate_general_mod_settings',
				'displayContext' => 'integrate_prepare_display_context',
				'profile' => 'integrate_load_custom_profile_fields',
			),
			), $result));
	}

	public function testGet()
	{
		$result = $this->_ohara->get('availableHooks');

		$this->assertEquals(array(
			'memberContext' => 'integrate_member_context',
			'generalSettings' => 'integrate_general_mod_settings',
			'displayContext' => 'integrate_prepare_display_context',
			'profile' => 'integrate_load_custom_profile_fields',
		), $result);
	}
}
