<?php

use Suki\Ohara;

class OharaDummyData extends \Suki\Ohara
{
	public $name = 'OharaDummyData';
	public $useConfig = false;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class DataTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		$t = new OharaDummyData;
		$this->_ohara = $t['data'];
	}

	public function testSetData()
	{
		$lol = $_REQUEST['lol'] = 'lol';

		$this->_ohara->setData();

		$this->assertEquals($lol, $_REQUEST['lol']);
	}
}
