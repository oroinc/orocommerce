<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Visibility;

use Oro\Bundle\ProductBundle\Visibility\BasicUnitVisibility;

class BasicUnitVisibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicUnitVisibility
     */
    private $visibility;

    protected function setUp()
    {
        $this->visibility = new BasicUnitVisibility();
    }

    /**
     * @dataProvider isUnitCodeVisible
     * @param string $code
     */
    public function testIsUnitCodeVisible($code)
    {
        $this->assertTrue($this->visibility->isUnitCodeVisible($code));
    }

    /**
     * @return array
     */
    public function isUnitCodeVisible()
    {
        return [
            ['each'],
            ['wrong_unit'],
        ];
    }
}
