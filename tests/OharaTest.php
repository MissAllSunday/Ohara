 <?php

$txt['months_title'] = 'Months';
$txt['MockOhara_months'] = array(
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
 );

$modSettings['stuff'] = 'thing';
$modSettings['MockOhara_fib'] = 'February';

class OharaTest extends PHPUnit_Framework_TestCase
{
    protected $o;

    protected function setUp()
    {
        $this->o = $this->getMockBuilder('Suki\Ohara')
            ->setMethods(null)
            ->getMock();
        $this->o->name = 'MockOhara';
    }

    public function testName()
    {
        $actual = $this->o->getName();
        $this->assertEquals('MockOhara', $actual);
    }

    public function testText()
    {
        $actual = $this->o->text('months');
        $this->assertSame('February', $actual[2]);
        $actual = $this->o->text('months_title');
        $this->assertFalse($actual);
        $actual = $this->o->text('days_title');
        $this->assertFalse($actual);
    }

    public function testAllText()
    {
        $this->testText();

        $actual = array_filter($this->o->getAllText());
        $this->assertTrue(is_array($actual));
        $this->assertCount(1, $actual);
    }

    public function testModSettings()
    {
        $actual = $this->o->modSetting('stuff');
        $this->assertSame('thing', $actual);
        $actual = $this->o->modSetting('fib');
        $this->assertFalse($actual);
    }

    public function testSettings()
    {
        $actual = $this->o->setting('');
        $this->assertFalse($actual);
        $actual = $this->o->setting('stuff');
        $this->assertFalse($actual);
        $actual = $this->o->setting('fib');
        $this->assertSame('February', $actual);
    }
}
