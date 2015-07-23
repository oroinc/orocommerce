<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;

class LineItemManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject|RoundingService */
    protected $roundingService;

    protected function setUp()
    {
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService
            ->expects($this->once())
            ->method('round')
            ->willReturnCallback(
                function ($quantity, $precision) {
                    return round($quantity, $precision);
                }
            );

        $this->roundingService = $roundingService;
    }

    public function testRoundProductQuantity()
    {
        $lineItemManager = new LineItemManager($this->roundingService);
        $product = $this->getProductEntityWithPrecision('kg', 3);

        $lineItemManager->roundProductQuantity($product, 'kg', round(1, 15));
    }

    /**
     * @param string  $unitCode
     * @param integer $precision
     *
     * @return Product
     */
    protected function getProductEntityWithPrecision($unitCode, $precision = 0)
    {
        $product = new Product();

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = (new ProductUnitPrecision())
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }
}
