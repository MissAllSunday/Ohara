<?php

use Suki\Ohara;

class OharaDummy extends Suki\Ohara
{
	public $name = 'Dummy';
}

class OharaTest extends \PHPUnit_Framework_TestCase
{
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
		$o = new OharaDummy;

		$result = $o['tools']->commaSeparated($originalString, $type);

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
}
