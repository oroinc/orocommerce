<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\SubtotalTaxProvider;

class SubtotalTaxProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubtotalTaxProvider
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

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SubtotalTaxProvider($this->translator, $this->taxManager);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider, $this->taxManager);
    }

    public function testGetName()
    {
        $this->assertEquals(SubtotalTaxProvider::NAME, $this->provider->getName());
    }
    public function testGetSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.tax.subtotals.' . SubtotalTaxProvider::TYPE)
            ->willReturn(ucfirst(SubtotalTaxProvider::TYPE));

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

        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalTaxProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(SubtotalTaxProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    public function testGetSubtotalWithException()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.tax.subtotals.' . SubtotalTaxProvider::TYPE)
            ->willReturn(ucfirst(SubtotalTaxProvider::TYPE));

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalTaxProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(SubtotalTaxProvider::TYPE), $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }
}
