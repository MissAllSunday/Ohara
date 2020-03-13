<?php

declare(strict_types=1);

use Ohara\Ohara;

function mockGlobals(): void
{
	global $modSettings;

	$modSettings['_configOharaDummyConfig'] = '{"_availableHooks":{"memberContext":"integrate_member_context","generalSettings":"integrate_general_mod_settings","displayContext":"integrate_prepare_display_context","profile":"integrate_load_custom_profile_fields"}}';
}

class OharaDummyConfig extends \Ohara\Ohara
{
	public $name = 'OharaDummyConfig';

	public $useConfig = true;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class ConfigTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		mockGlobals();
		$t = new OharaDummyConfig;
		$this->_ohara = $t['config'];
	}

	protected function similarArrays($a, $b)
	{
		if(is_array($a) && is_array($b))
		{
			if(0 < count(array_diff(array_keys($a), array_keys($b))))
				return false;

			foreach($a as $k => $v)
				if(!$this->similarArrays($v, $b[$k]))
					return false;

			return true;
		}


			return $a === $b;
	}

	public function testgetConfig(): void
	{
		$result = $this->_ohara->getConfig();

		$this->assertTrue($this->similarArrays([
			'_availableHooks' => [
				'memberContext' => 'integrate_member_context',
				'generalSettings' => 'integrate_general_mod_settings',
				'displayContext' => 'integrate_prepare_display_context',
				'profile' => 'integrate_load_custom_profile_fields',
			],
		], $result));
	}

	public function testGet(): void
	{
		$result = $this->_ohara->get('availableHooks');

		$this->assertEquals([
			'memberContext' => 'integrate_member_context',
			'generalSettings' => 'integrate_general_mod_settings',
			'displayContext' => 'integrate_prepare_display_context',
			'profile' => 'integrate_load_custom_profile_fields',
		], $result);
	}
}
