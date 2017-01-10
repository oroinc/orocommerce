<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Twig\UnitVisibilityExtension;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class UnitVisibilityExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnitVisibilityInterface
     */
    protected $unitVisibility;

    /**
     * @var UnitVisibilityExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);
        $this->extension = new UnitVisibilityExtension($this->unitVisibility);
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['oro_is_unit_code_visible', [$this->unitVisibility, 'isUnitCodeVisible']],
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->extension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }
}
