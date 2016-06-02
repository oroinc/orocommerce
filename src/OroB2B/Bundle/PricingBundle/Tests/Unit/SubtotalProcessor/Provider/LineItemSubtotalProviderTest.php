<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class LineItemSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    /**
     * @var LineItemSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    protected function setUp()
    {
        parent::setUp();
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->roundingService = $this->getMock('OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->provider = new LineItemSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->currencyManager
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemSubtotalProvider::NAME . '.label')
            ->willReturn('test');

        $entity = new EntityStub();
        $perUnitLineItem = new LineItemStub();
        $perUnitLineItem->setPriceType(LineItemStub::PRICE_TYPE_UNIT);
        $perUnitLineItem->setPrice(Price::create(20, 'USD'));
        $perUnitLineItem->setQuantity(2);

        $bundledUnitLineItem = new LineItemStub();
        $bundledUnitLineItem->setPriceType(LineItemStub::PRICE_TYPE_BUNDLED);
        $bundledUnitLineItem->setPrice(Price::create(2, 'USD'));
        $bundledUnitLineItem->setQuantity(10);

        $otherCurrencyLineItem = new LineItemStub();
        $otherCurrencyLineItem->setPriceType(LineItemStub::PRICE_TYPE_UNIT);
        $otherCurrencyLineItem->setPrice(Price::create(10, 'EUR'));
        $otherCurrencyLineItem->setQuantity(10);

        $emptyLineItem = new LineItemStub();

        $entity->addLineItem($perUnitLineItem);
        $entity->addLineItem($bundledUnitLineItem);
        $entity->addLineItem($emptyLineItem);
        $entity->addLineItem($otherCurrencyLineItem);

        $entity->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testGetName()
    {
        $this->assertEquals(LineItemSubtotalProvider::NAME, $this->provider->getName());
    }

    public function testIsSupported()
    {
        $entity = new EntityStub();
        $this->assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $entity = new LineItemStub();
        $this->assertFalse($this->provider->isSupported($entity));
    }
}
