<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;

class TaxSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxManager
     */
    protected $taxManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxFactory
     */
    protected $taxFactory;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())->method('trans')->willReturnCallback(
            function ($message) {
                return ucfirst($message);
            }
        );

        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxFactory = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\TaxFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxSubtotalProvider($this->translator, $this->taxManager, $this->taxFactory);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider, $this->taxManager);
    }

    public function testGetName()
    {
        $this->assertEquals(TaxSubtotalProvider::NAME, $this->provider->getName());
    }

    public function testGetSubtotal()
    {
        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResultWithTotal($total);

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
    }

    public function testGetCachedSubtotal()
    {
        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResultWithTotal($total);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
    }

    public function testGetCachedSubtotalEmptyIfTaxationDisabled()
    {
        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertEmpty($subtotal->getAmount());
    }

    public function testGetSubtotalWithException()
    {
        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Orob2b.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testIsSupported()
    {
        $this->taxFactory->expects($this->once())->method('supports')->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }

    /**
     * @param Subtotal $subtotal
     * @param ResultElement $total
     */
    protected function assertSubtotal(Subtotal $subtotal, ResultElement $total)
    {
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Orob2b.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @param int $amount
     * @param string $currency
     * @return ResultElement
     */
    protected function createTotalResultElement($amount, $currency)
    {
        $total = new ResultElement();
        $total
            ->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    /**
     * @param ResultElement $total
     * @return Result
     */
    protected function createTaxResultWithTotal(ResultElement $total)
    {
        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        return $tax;
    }
}
