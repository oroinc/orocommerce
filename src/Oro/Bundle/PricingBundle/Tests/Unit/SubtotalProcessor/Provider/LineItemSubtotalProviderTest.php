<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\SubtotalEntityStub;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var LineItemSubtotalProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RoundingServiceInterface */
    private $roundingService;

    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);

        $this->provider = new LineItemSubtotalProvider(
            $this->translator,
            $this->roundingService,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    /**
     * @dataProvider lineItemsDataProvider
     */
    public function testGetSubtotal(
        array $lineItemsData,
        float $expectedValue,
        int $precision
    ) {
        $currency = 'USD';

        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($value) use ($precision) {
                return round($value, $precision, PHP_ROUND_HALF_UP);
            });
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = new EntityStub();
        foreach ($lineItemsData as $item) {
            $lineItem = new LineItemStub();
            $lineItem->setPriceType($item['price_type']);
            $lineItem->setPrice(Price::create($item['price'], $currency));
            $lineItem->setQuantity($item['quantity']);
            $entity->addLineItem($lineItem);
        }

        $emptyLineItem = new LineItemStub();
        $entity->addLineItem($emptyLineItem);

        $entity->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($expectedValue, $subtotal->getAmount());
    }

    public function lineItemsDataProvider(): array
    {
        return [
            'price with precision 2, system precision 2' => [
                'lineItems' => [
                    [
                        'price' => 0.03,
                        'quantity' => 3,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.02,
                        'quantity' => 7,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.11,
                        'quantity' => 10,
                        'price_type' => LineItemStub::PRICE_TYPE_BUNDLED
                    ]
                ],
                'expectedValue' => 8.34,
                'precision' => 2,
            ],
            'price with precision 4, system precision 2' => [
                'lineItems' => [
                    [
                        'price' => 0.0149,
                        'quantity' => 3,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.0149,
                        'quantity' => 7,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.1149,
                        'quantity' => 10,
                        'price_type' => LineItemStub::PRICE_TYPE_BUNDLED
                    ]
                ],
                'expectedValue' => 8.25,
                'precision' => 2,
            ],
            'price with precision 4, system precision 4' => [
                'lineItems' => [
                    [
                        'price' => 0.0149,
                        'quantity' => 3,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.0149,
                        'quantity' => 7,
                        'price_type' => LineItemStub::PRICE_TYPE_UNIT
                    ],
                    [
                        'price' => 1.1149,
                        'quantity' => 10,
                        'price_type' => LineItemStub::PRICE_TYPE_BUNDLED
                    ]
                ],
                'expectedValue' => 8.2639,
                'precision' => 4,
            ],
        ];
    }

    public function testGetCachedSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = $this->createMock(SubtotalEntityStub::class);
        $entity->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(123456.0);
        $entity->expects($this->any())
            ->method('getCurrency')
            ->willReturn('USD');
        $entity->expects($this->never())
            ->method('getLineItems');

        $subtotal = $this->provider->getCachedSubtotal($entity);

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals(123456.0, $subtotal->getAmount());
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

    public function testGetRowTotalWhenNoPrice()
    {
        $lineItem = new LineItemStub();
        self::assertEquals(0, $this->provider->getRowTotal($lineItem, 'USD'));
    }

    public function testGetRowTotalWithSameCurrency()
    {
        $lineItem = new LineItemStub();
        $lineItem->setPrice(Price::create(10.499, 'USD'));
        $lineItem->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED);
        $this->roundingService->expects($this->once())
            ->method('round')
            ->with(10.499)
            ->willReturn(10.50);

        self::assertEquals(10.50, $this->provider->getRowTotal($lineItem, 'USD'));
    }

    public function testGetRowTotal()
    {
        $lineItem = new LineItemStub();
        $lineItem->setPrice(Price::create(10.499, 'USD'));
        $lineItem->setQuantity(2);
        $this->roundingService->expects($this->once())
            ->method('round')
            ->with(20.998)
            ->willReturn(21.00);

        self::assertEquals(21.00, $this->provider->getRowTotal($lineItem, 'USD'));
    }
}
