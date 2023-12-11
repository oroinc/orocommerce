<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
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
    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteCurrencyProvider;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RoundingServiceInterface
     */
    protected $roundingService;

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

    protected function tearDown(): void
    {
        unset($this->translator, $this->provider);
    }

    /**
     * @dataProvider lineItemsDataProvider
     *
     * @param array $lineItemsData
     * @param float $expectedValue
     * @param int $precision
     */
    public function testGetSubtotal(
        array $lineItemsData,
        $expectedValue,
        $precision
    ) {
        $currency = 'USD';

        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) use ($precision) {
                        return round($value, $precision, PHP_ROUND_HALF_UP);
                    }
                )
            );
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = new EntityStub();
        foreach ($lineItemsData as $item) {
            $lineItem = new LineItemStub();
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

    /**
     * @return array
     */
    public function lineItemsDataProvider()
    {
        return [
            'price with precision 2, system precision 2' => [
                'lineItems' => [
                    [
                        'price' => 0.03,
                        'quantity' => 3,
                    ],
                    [
                        'price' => 1.02,
                        'quantity' => 7,
                    ],
                ],
                'expectedValue' => 7.23,
                'precision' => 2,
            ],
            'price with precision 4, system precision 2' => [
                'lineItems' => [
                    [
                        'price' => 0.0149,
                        'quantity' => 3
                    ],
                    [
                        'price' => 1.0149,
                        'quantity' => 7
                    ],
                ],
                'expectedValue' => 7.14,
                'precision' => 2,
            ],
            'price with precision 4, system precision 4' => [
                'lineItems' => [
                    [
                        'price' => 0.0149,
                        'quantity' => 3,
                    ],
                    [
                        'price' => 1.0149,
                        'quantity' => 6,
                    ],
                ],
                'expectedValue' => 6.1341,
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

        /** @var SubtotalEntityStub|\PHPUnit\Framework\MockObject\MockObject $entityMock */
        $entityMock = $this->createMock(SubtotalEntityStub::class);

        $entityMock->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(123456.0);

        $entityMock->expects($this->any())
            ->method('getCurrency')
            ->willReturn('USD');

        $entityMock->expects($this->never())
            ->method('getLineItems');

        $subtotal = $this->provider->getCachedSubtotal($entityMock);

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entityMock->getCurrency(), $subtotal->getCurrency());
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
        $lineItem->setQuantity(1);
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
