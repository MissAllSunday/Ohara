<?php

declare(strict_types=1);

use Ohara\Ohara;

class OharaDummyData extends \Ohara\Ohara
{
	public $name = 'OharaDummyData';

	public $useConfig = false;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class DataTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$t = new OharaDummyData;
		$this->_ohara = $t['data'];
	}

	public function testSetData(): void
	{
		$lol = $_REQUEST['lol'] = 'lol';

		$this->_ohara->setData();

		$this->assertEquals($lol, $_REQUEST['lol']);
	}
}
