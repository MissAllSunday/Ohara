<?php

use Suki\Ohara;

class OharaDummyData extends \Suki\Ohara
{
	public $name = 'OharaDummyTools';
	public $useConfig = false;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class ToolsTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		$t = new OharaDummyData;
		$this->_ohara = $t['data'];
	}

	public function testSetData()
	{
		$lol = $_REQUEST['lol'] = 'lol';

		$this->assertEquals($lol, $this->_ohara->get('lol'));
	}

	public functiontestPutData()
	{
		$lol = $_REQUEST['lol'] = 'lol';
		$this->_ohara->put($lol);

		$this->assertEquals($lol, $this->_ohara->get('lol'));
	}
}
