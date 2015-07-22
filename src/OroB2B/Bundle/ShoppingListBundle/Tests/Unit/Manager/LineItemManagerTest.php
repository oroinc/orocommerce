<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

class LineItemManagerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
