<?php

declare(strict_types=1);

use Ohara\Ohara;

class OharaDummyTools extends \Ohara\Ohara
{
	public $name = 'OharaDummyTools';

	public $useConfig = false;

	public function __construct()
	{
		$this->setRegistry();
	}
}

class ToolsTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$t = new OharaDummyTools;
		$this->_ohara = $t['tools'];
	}

	/**
	 * @param string $type The type to check against. Accepts "numeric", "alpha" and "alphanumeric".
	 * @param string $originalString The oriignal string.
	 * @param string $expectedResult Expeted result.
	 *
	 * @dataProvider providerTestCommaSeparatedString
	 */
	public function testCommaSeparatedString($type, $originalString, $expectedResult): void
	{
		$result = $this->_ohara->commaSeparated($originalString, $type);

		$this->assertEquals($expectedResult, $result);
	}

	public function providerTestCommaSeparatedString()
	{
		return [
			['alphanumeric', '1a,2b,3c,4d,5e,6f', '1a,2b,3c,4d,5e,6f'],
			['numeric', '1a,2b,3c,4d,5e,6f', '1,2,3,4,5,6'],
			['alpha', '1a,2b,3c,4d,5e,6f', 'a,b,c,d,e,f'],
		];
	}

	public function testCommaSeparatedFail(): void
	{
		$this->assertFalse($this->_ohara->commaSeparated([]));
	}

	public function testScheme(): void
	{
		$o = new OharaDummyTools;
		$this->assertEquals('http://missallsunday.com', $this->_ohara->checkScheme('missallsunday.com'));
	}

	public function testParser(): void
	{
		$this->assertEquals('foo baz bar', $this->_ohara->parser('{foo} {baz} {bar}', [
			'foo' => 'foo',
			'baz' => 'baz',
			'bar' => 'bar',
		]));
	}
}
