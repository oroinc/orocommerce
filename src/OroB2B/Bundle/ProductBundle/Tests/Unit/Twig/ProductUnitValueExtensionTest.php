<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Twig\ProductUnitValueExtension;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueExtensionTest extends UnitValueExtensionTestCase
{
    /** @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitValueExtension::NAME, $this->getExtension()->getName());
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
