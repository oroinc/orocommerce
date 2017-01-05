<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension;

class ProductUnitValueExtensionTest extends UnitValueExtensionTestCase
{
    /** @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;


    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(ProductUnitValueFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductUnitValueExtension
     */
    protected function getExtension()
    {
        return new ProductUnitValueExtension($this->formatter);
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
