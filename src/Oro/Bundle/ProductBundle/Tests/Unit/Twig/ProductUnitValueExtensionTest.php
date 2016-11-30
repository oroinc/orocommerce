<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension;

class ProductUnitValueExtensionTest extends UnitValueExtensionTestCase
{
    /** @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var SingleUnitModeService|\PHPUnit_Framework_MockObject_MockObject */
    protected $unitModeProvider;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(ProductUnitValueFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitModeProvider = $this->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitValueExtension::NAME, $this->getExtension()->getName());
    }

    public function testGetFunctions()
    {
        /** @var \Twig_SimpleFunction[] $functions */
        $functions = $this->getExtension()->getFunctions();

        $this->assertCount(3, $functions);

        $this->assertInternalType('array', $functions);
        foreach ($functions as $function) {
            $this->assertInstanceOf('Twig_SimpleFunction', $function);
        }
    }

    /**
     * @return ProductUnitValueExtension
     */
    protected function getExtension()
    {
        return new ProductUnitValueExtension($this->formatter, $this->unitModeProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }
}
