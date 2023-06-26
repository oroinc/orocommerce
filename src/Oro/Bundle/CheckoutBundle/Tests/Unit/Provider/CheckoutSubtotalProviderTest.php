<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    private const CHECKOUT_CURRENCY = 'USD';
    private const USER_CURRENCY = 'USER_CURRENCY';
    private const WEBSITE_CURRENCY = 'WEBSITE_CURRENCY';
    private const DEFAULT_CURRENCY = 'DEFAULT_CURRENCY';
    private const WEBSITE_ID = 123;
    private const SUBTOTAL_LABEL = 'test';
    private const FEATURE_NAME = 'oro_price_lists_combined';

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemPriceProvider;

    private CombinedPriceListTreeHandler|MockObject $priceListTreeHandler;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private CheckoutSubtotalProvider $provider;

    private FeatureChecker|MockObject $featureChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn(self::SUBTOTAL_LABEL);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->provider = new CheckoutSubtotalProvider(
            $translator,
            $roundingService,
            $this->productLineItemPriceProvider,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->priceScopeCriteriaFactory
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature(self::FEATURE_NAME);

        $this->currencyManager
            ->method('getDefaultCurrency')
            ->willReturn(self::DEFAULT_CURRENCY);
    }

    public function testIsSupported(): void
    {
        $entity = new Checkout();
        self::assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported(): void
    {
        $entity = new \stdClass();
        self::assertFalse($this->provider->isSupported($entity));
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
        $entity = (new Checkout())
            ->setCurrency($checkoutCurrency);

        $this->currencyManager
            ->method('getUserCurrency')
            ->willReturn($userCurrency);

        $this->websiteCurrencyProvider
            ->expects(self::never())
            ->method('getWebsiteDefaultCurrency');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals($expectedCurrency, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function getSubtotalWithoutLineItemsDataProvider(): iterable
    {
        yield [
            'checkoutCurrency' => self::CHECKOUT_CURRENCY,
            'userCurrency' => null,
            'websiteCurrency' => null,
            'expectedCurrency' => self::CHECKOUT_CURRENCY,
        ];

        yield [
            'checkoutCurrency' => null,
            'userCurrency' => self::USER_CURRENCY,
            'websiteCurrency' => null,
            'expectedCurrency' => self::USER_CURRENCY,
        ];

        yield [
            'checkoutCurrency' => null,
            'userCurrency' => null,
            'websiteCurrency' => null,
            'expectedCurrency' => self::DEFAULT_CURRENCY,
        ];
    }

    public function testGetSubtotalWithLineItemWhenNotFixedPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItem,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            123.46
        );
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNotFixedPriceAndFeaturesEnabled(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItem,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            123.46
        );
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler
            ->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNoPrice(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenNoPriceAndFeaturesEnabled(): void
    {
        $lineItem = new CheckoutLineItem();
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([]);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler
            ->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenFixedPrice(): void
    {
        $lineItem = (new CheckoutLineItem())
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY))
            ->setQuantity(10);
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method('createByContext');

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(123.46, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWithLineItemWhenFixedPriceAndNoPrice(): void
    {
        $lineItem = (new CheckoutLineItem())
            ->setPriceFixed(true);
        $entity = (new Checkout())
            ->addLineItem($lineItem)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method('createByContext');

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(0.0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWhenBothFixedPriceAndNot(): void
    {
        $lineItemWithNotFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice = (new CheckoutLineItem())
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY))
            ->setQuantity(10);
        $entity = (new Checkout())
            ->addLineItem($lineItemWithFixedPrice)
            ->addLineItem($lineItemWithNotFixedPrice)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItemWithNotFixedPrice,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            1234.60
        );
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithNotFixedPrice], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(1358.06, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertNull($subtotal->getPriceList());
    }

    public function testGetSubtotalWhenBothFixedPriceAndNotAndFeaturesEnabled(): void
    {
        $lineItemWithNotFixedPrice = new CheckoutLineItem();
        $lineItemWithFixedPrice = (new CheckoutLineItem())
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, self::CHECKOUT_CURRENCY))
            ->setQuantity(10);
        $entity = (new Checkout())
            ->addLineItem($lineItemWithFixedPrice)
            ->addLineItem($lineItemWithNotFixedPrice)
            ->setCurrency(self::CHECKOUT_CURRENCY);

        $priceScopeCriteria = new ProductPriceScopeCriteria();
        $priceScopeCriteria->setCustomer(new Customer());
        $priceScopeCriteria->setWebsite(new Website());

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice(
            $lineItemWithNotFixedPrice,
            Price::create(12.3456, self::CHECKOUT_CURRENCY),
            1234.60
        );
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithNotFixedPrice], $priceScopeCriteria, self::CHECKOUT_CURRENCY)
            ->willReturn([$productLineItemPrice]);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $priceList = new PriceList();
        $this->priceListTreeHandler
            ->expects(self::once())
            ->method('getPriceList')
            ->with($priceScopeCriteria->getCustomer(), $priceScopeCriteria->getWebsite())
            ->willReturn($priceList);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertEquals(self::CHECKOUT_CURRENCY, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(1358.06, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertSame($priceList, $subtotal->getPriceList());
    }
}
