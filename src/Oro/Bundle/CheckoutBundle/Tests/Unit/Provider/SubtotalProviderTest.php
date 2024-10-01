<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\SubtotalProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CHECKOUT_CURRENCY = 'USD';
    private const USER_CURRENCY = 'USER_CURRENCY';
    private const DEFAULT_CURRENCY = 'DEFAULT_CURRENCY';
    private const SUBTOTAL_LABEL = 'oro.checkout.subtotals.checkout_subtotal.label (translated)';
    private const FEATURE_NAME = 'oro_price_lists_combined';

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var ProductLineItemPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productLineItemPriceProvider;

    /** @var CombinedPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTreeHandler;

    /** @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceScopeCriteriaFactory;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var SubtotalProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->currencyManager->expects(self::any())
            ->method('getDefaultCurrency')
            ->willReturn(self::DEFAULT_CURRENCY);

        $this->provider = new SubtotalProvider(
            $translator,
            $roundingService,
            $this->productLineItemPriceProvider,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->priceScopeCriteriaFactory
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature(self::FEATURE_NAME);
    }

    public function testIsSupported(): void
    {
        self::assertTrue($this->provider->isSupported(new Checkout()));
    }

    public function testIsNotSupported(): void
    {
        self::assertFalse($this->provider->isSupported(new \stdClass()));
    }

    public function testGetSubtotalWithWrongEntity(): void
    {
        self::assertNull($this->provider->getSubtotal(new \stdClass()));
    }

    /**
     * @dataProvider getSubtotalWithoutLineItemsDataProvider
     */
    public function testGetSubtotalWithoutLineItems(
        ?string $checkoutCurrency,
        ?string $userCurrency,
        ?string $websiteCurrency,
        ?string $expectedCurrency
    ): void {
        $entity = new Checkout();
        $entity->setCurrency($checkoutCurrency);

        $this->currencyManager->expects(self::any())
            ->method('getUserCurrency')
            ->willReturn($userCurrency);

        $this->websiteCurrencyProvider->expects(self::never())
            ->method('getWebsiteDefaultCurrency');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals($expectedCurrency, $subtotal->getCurrency());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function getSubtotalWithoutLineItemsDataProvider(): array
    {
        return [
            [
                'checkoutCurrency' => self::CHECKOUT_CURRENCY,
                'userCurrency' => null,
                'websiteCurrency' => null,
                'expectedCurrency' => self::CHECKOUT_CURRENCY
            ],
            [
                'checkoutCurrency' => null,
                'userCurrency' => self::USER_CURRENCY,
                'websiteCurrency' => null,
                'expectedCurrency' => self::USER_CURRENCY
            ],
            [
                'checkoutCurrency' => null,
                'userCurrency' => null,
                'websiteCurrency' => null,
                'expectedCurrency' => self::DEFAULT_CURRENCY
            ]
        ];
    }

    public function testGetSubtotalWithLineItemWhenNotFixedPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItem,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            123.46
        );
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNotFixedPriceAndFeaturesEnabled(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItem,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            123.46
        );
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNoPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNoPriceAndFeaturesEnabled(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([]);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenFixedPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $lineItem->setPriceFixed(true);
        $lineItem->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY));
        $lineItem->setQuantity(10);
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $this->priceScopeCriteriaFactory->expects(self::never())
            ->method('createByContext');

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenFixedPriceAndNoPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $lineItem->setPriceFixed(true);
        $entity = new Checkout();
        $entity->addLineItem($lineItem);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $this->priceScopeCriteriaFactory->expects(self::never())
            ->method('createByContext');

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWhenBothFixedPriceAndNot(): void
    {
        $lineItemWithNotFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice->setPriceFixed(true);
        $lineItemWithFixedPrice->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY));
        $lineItemWithFixedPrice->setQuantity(10);
        $entity = new Checkout();
        $entity->addLineItem($lineItemWithFixedPrice);
        $entity->addLineItem($lineItemWithNotFixedPrice);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItemWithNotFixedPrice,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            1234.60
        );
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithNotFixedPrice], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(1358.06, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWhenBothFixedPriceAndNotAndFeaturesEnabled(): void
    {
        $lineItemWithNotFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice->setPriceFixed(true);
        $lineItemWithFixedPrice->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY));
        $lineItemWithFixedPrice->setQuantity(10);
        $entity = new Checkout();
        $entity->addLineItem($lineItemWithFixedPrice);
        $entity->addLineItem($lineItemWithNotFixedPrice);
        $entity->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItemWithNotFixedPrice,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            1234.60
        );
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithNotFixedPrice], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(SubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertSame(1358.06, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }
}
