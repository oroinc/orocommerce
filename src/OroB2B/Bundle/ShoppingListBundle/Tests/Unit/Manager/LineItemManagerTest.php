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

    /**
     * @var Callable
     */
    protected $roundCallback;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->roundCallback = function ($quantity, $precision) {
            return round($quantity, $precision);
        };
    }

    /**
     * Method testRoundProductQuantity
     */
    public function testRoundProductQuantity()
    {
        $this->roundingService
            ->expects($this->once())
            ->method('round')
            ->willReturnCallback($this->roundCallback);

        $lineItemManager = new LineItemManager($this->roundingService);
        $product = $this->getProductEntityWithPrecision('kg', 3);

        $lineItemManager->roundProductQuantity($product, 'kg', $this->getRandomQuantity());
    }

    /**
     * Method testNoPrecision
     */
    public function testNoPrecision()
    {
        $this->roundingService
            ->expects($this->never())
            ->method('round');

        $lineItemManager = new LineItemManager($this->roundingService);
        $product = $this->getProductEntityWithPrecision('kg', 3);

        $quantity = $this->getRandomQuantity();
        $roundedQuantity = $lineItemManager->roundProductQuantity($product, 'unit', $quantity);
        $this->assertEquals($quantity, $roundedQuantity);
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

    /*
     * @return float
     */
    protected function getRandomQuantity()
    {
        $quantity = mt_rand(1000, 15000);
        $quantity /= 1000;

        return $quantity;
    }
}
