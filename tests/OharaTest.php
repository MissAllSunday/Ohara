 <?php

$txt['months_title'] = 'Months';
$txt['MockOhara_months'] = [
	1 => 'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December',
];

$modSettings['stuff'] = 'thing';
$modSettings['MockOhara_fib'] = 'February';

class OharaTest extends PHPUnit\Framework\TestCase
{
	protected $o;

	protected function setUp(): void
	{
		$this->o = $this->getMockBuilder('Suki\Ohara')
			->setMethods(null)
			->getMock();
		$this->o->name = 'MockOhara';
	}

	public function testName(): void
	{
		$actual = $this->o->getName();
		$this->assertEquals('MockOhara', $actual);
	}

	public function testText(): void
	{
		$actual = $this->o->text('months');
		$this->assertSame('February', $actual[2]);
		$actual = $this->o->text('months_title');
		$this->assertFalse($actual);
		$actual = $this->o->text('days_title');
		$this->assertFalse($actual);
	}

	public function testModSettings(): void
	{
		$actual = $this->o->modSetting('stuff');
		$this->assertSame('thing', $actual);
		$actual = $this->o->modSetting('fib');
		$this->assertFalse($actual);
	}

	public function testSettings(): void
	{
		$actual = $this->o->setting('');
		$this->assertFalse($actual);
		$actual = $this->o->setting('stuff');
		$this->assertFalse($actual);
		$actual = $this->o->setting('fib');
		$this->assertSame('February', $actual);
	}
}
