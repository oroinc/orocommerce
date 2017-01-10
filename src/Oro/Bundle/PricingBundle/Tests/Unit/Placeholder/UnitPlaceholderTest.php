<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;

class UnitPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnitPlaceholder
     */
    private $placeholder;

    protected function setUp()
    {
        $this->placeholder = new UnitPlaceholder();
    }

    public function testGetPlaceholder()
    {
        $this->assertSame(UnitPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceValue()
    {
        $this->assertSame("test_kg", $this->placeholder->replace("test_UNIT", ["UNIT" => "kg"]));
    }

    public function testReplaceDefault()
    {
        $this->markTestIncomplete("fix in BB-4178");
    }
}
