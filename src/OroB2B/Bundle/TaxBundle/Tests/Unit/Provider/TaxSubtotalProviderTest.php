<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;

class TaxSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxManager
     */
    protected $taxManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxationSettingsProvider
     */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxSubtotalProvider($this->taxManager, $this->settingsProvider);
    }

    protected function tearDown()
    {
        unset($this->settingsProvider, $this->provider, $this->taxManager);
    }

    public function testGetName()
    {
        $this->assertEquals(TaxSubtotalProvider::NAME, $this->provider->getName());
    }

    public function testGetSubtotal()
    {
        $total = new ResultElement();
        $total
            ->setCurrency('USD')
            ->offsetSet(ResultElement::TAX_AMOUNT, '150');

        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('orob2b.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    public function testGetSubtotalWithException()
    {
        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('orob2b.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testIsSupported()
    {
        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }
}
