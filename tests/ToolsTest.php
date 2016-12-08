<?php

use Suki\Ohara;

class OharaDummyTools extends \Suki\Ohara
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
		$t = new OharaDummyTools;
		$this->_ohara = $t['tools'];
	}

	/**
	 * @param string $type The type to check against. Accepts "numeric", "alpha" and "alphanumeric".
	 * @param string $delimiter Used for explode/imploding the string.
	 * @param string $originalString The oriignal string.
	 * @param string $expectedResult Expeted result.
	 *
	 * @dataProvider providerTestCommaSeparatedString
	 */
	public function testCommaSeparatedString($type, $originalString, $expectedResult)
	{
		$result = $this->_ohara->commaSeparated($originalString, $type);

		$this->assertEquals($expectedResult, $result);
	}

	public function providerTestCommaSeparatedString()
	{
		return array(
			array('alphanumeric', '1a,2b,3c,4d,5e,6f', '1a,2b,3c,4d,5e,6f'),
			array('numeric', '1a,2b,3c,4d,5e,6f', '1,2,3,4,5,6'),
			array('alpha', '1a,2b,3c,4d,5e,6f', 'a,b,c,d,e,f'),
		);
	}

	public function testCommaSeparatedFail()
	{
		$this->assertFalse($this->_ohara->commaSeparated(array()));
	}

	public function testScheme()
	{
		$o = new OharaDummyTools;
		$this->assertEquals('http://missallsunday.com', $this->_ohara->checkScheme('missallsunday.com'));
	}

	public function testParser()
	{
		$this->assertEquals('foo baz bar', $this->_ohara->parser('{foo} {baz} {bar}', array(
			'foo' => 'foo',
			'baz' => 'baz',
			'bar' => 'bar',
		)));
	}
}
