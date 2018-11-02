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
use Symfony\Component\Translation\TranslatorInterface;

class LineItemSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteCurrencyProvider;

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

    protected function tearDown()
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
            ->with(LineItemSubtotalProvider::NAME . '.label')
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
        $this->assertInternalType('float', $subtotal->getAmount());
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
            ->with(LineItemSubtotalProvider::NAME . '.label')
            ->willReturn('test');

        /** @var SubtotalEntityStub|\PHPUnit_Framework_MockObject_MockObject $entityMock */
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

        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entityMock->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(123456.0, $subtotal->getAmount());
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
