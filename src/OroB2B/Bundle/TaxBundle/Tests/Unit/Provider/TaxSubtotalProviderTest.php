<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
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

    /**
     * @param bool $editMode
     * @dataProvider getSubtotalProvider
     */
    public function testGetSubtotal($editMode)
    {
        $total = new ResultElement();
        $total
            ->setCurrency('USD')
            ->offsetSet(ResultElement::TAX_AMOUNT, '150');

        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        $this->taxManager->expects($this->once())
            ->method($editMode ? 'getTax' : 'loadTax')
            ->willReturn($tax);

        $this->provider->setEditMode($editMode);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Orob2b.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @return array
     */
    public function getSubtotalProvider()
    {
        return [
            [
                'editMode' => false,
            ],
            [
                'editMode' => true,
            ],
        ];
    }

    public function testGetSubtotalWithException()
    {
        $this->taxManager->expects($this->once())
            ->method('loadTax')
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

    public function testEditMode()
    {
        $this->assertFalse($this->provider->isEditMode());
        $this->provider->setEditMode(true);
        $this->assertTrue($this->provider->isEditMode());
        $this->provider->setEditMode(false);
        $this->assertFalse($this->provider->isEditMode());
    }
}
