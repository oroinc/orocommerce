<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemNotPricedStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemNotPricedSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var LineItemNotPricedSubtotalProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RoundingServiceInterface */
    private $roundingService;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductPriceProviderInterface */
    private $productPriceProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductPriceScopeCriteriaFactoryInterface */
    private $priceScopeCriteriaFactory;

    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->provider = new LineItemNotPricedSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->productPriceProvider,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->priceScopeCriteriaFactory
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
            ->with(LineItemNotPricedSubtotalProvider::LABEL)
            ->willReturn('test');

        $prices = [];
        $entity = new EntityNotPricedStub();
        foreach ($lineItemsData as $item) {
            $product = $this->getEntity(Product::class, ['id' => $item['product_id']]);
            $productUnit = $this->getEntity(ProductUnit::class, ['code' => $item['unit']]);

            $lineItem = new LineItemNotPricedStub();
            $lineItem->setProduct($product);
            $lineItem->setProductUnit($productUnit);
            $lineItem->setQuantity($item['quantity']);
            $entity->addLineItem($lineItem);

            $prices[$item['identifier']] = Price::create($item['price'], $currency);
        }

        $this->productPriceProvider->expects($this->any())
            ->method('getMatchedPrices')
            ->willReturn($prices);

        $websiteId = 101;
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $this->setValue($website, 'id', $websiteId);
        $entity->setWebsite($website);

        $this->websiteCurrencyProvider->expects($this->once())
            ->method('getWebsiteDefaultCurrency')
            ->with($websiteId)
            ->willReturn($currency);

        $searchScope = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('createByContext')
            ->with($entity)
            ->willReturn($searchScope);

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($currency, $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($expectedValue, (float)$subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    public function lineItemsDataProvider(): array
    {
        return [
            'price with precision 2, system precision 2' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.03,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.02,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.23,
                'precision' => 2,
            ],
            'price with precision 4, system precision 2' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.0149,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.0149,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.14,
                'precision' => 2,
            ],
            'price with precision 4, system precision 4' => [
                'lineItems' => [
                    [
                        'product_id' => 1,
                        'price' => 0.0149,
                        'identifier' => '1-kg-3-USD',
                        'quantity' => 3,
                        'unit' => 'kg'
                    ],
                    [
                        'product_id' => 2,
                        'price' => 1.0149,
                        'identifier' => '2-item-7-USD',
                        'quantity' => 7,
                        'unit' => 'item'
                    ]
                ],
                'expectedValue' => 7.149,
                'precision' => 4,
            ],
        ];
    }

    public function testGetSubtotalWithoutLineItems()
    {
        $this->roundingService->expects($this->never())
            ->method('round');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = new EntityNotPricedStub();

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals(0, $subtotal->getAmount());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testGetSubtotalWithWrongEntity()
    {
        $this->assertNull($this->provider->getSubtotal(new EntityStub()));
    }

    public function testIsSupported()
    {
        $entity = new EntityNotPricedStub();
        $this->assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $entity = new LineItemNotPricedStub();
        $this->assertFalse($this->provider->isSupported($entity));
    }
}
