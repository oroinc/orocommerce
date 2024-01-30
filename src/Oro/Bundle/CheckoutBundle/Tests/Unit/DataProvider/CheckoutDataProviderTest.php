<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\DataProvider\CheckoutDataProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private const VALIDATION_GROUPS = [['Default', 'checkout_line_items_data']];

    /** @var ProductLineItemPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productLineItemPriceProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ResolvedProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resolvedProductVisibilityProvider;

    /** @var CheckoutValidationGroupsBySourceEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $validationGroupsProvider;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var CheckoutDataProvider */
    private $provider;

    private array $processedValidationGroups = [];

    protected function setUp(): void
    {
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);
        $this->validationGroupsProvider = $this->createMock(CheckoutValidationGroupsBySourceEntityProvider::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $productAvailabilityCache = $this->createMock(CacheInterface::class);
        $productAvailabilityCache->expects(self::any())
            ->method('get')
            ->willReturnCallback(fn (string $key, callable $callback) => $callback());

        $this->provider = new CheckoutDataProvider(
            $this->productLineItemPriceProvider,
            $this->authorizationChecker,
            $productAvailabilityCache,
            $this->resolvedProductVisibilityProvider,
            $this->validationGroupsProvider,
            $this->validator,
            $this->memoryCacheProvider
        );

        $this->processedValidationGroups = [new GroupSequence(self::VALIDATION_GROUPS)];
    }

    /**
     * @dataProvider isEntitySupportedProvider
     */
    public function testIsEntitySupported(bool $expected, object $entity): void
    {
        self::assertEquals($expected, $this->provider->isEntitySupported($entity));
    }

    public function isEntitySupportedProvider(): array
    {
        return [
            ['expected' => false, 'data' => new \stdClass()],
            ['expected' => true, 'entity' => new Checkout()],
        ];
    }

    public function testGetDataWhenDataAlreadyCached(): void
    {
        $checkout = new Checkout();

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::never())
            ->method(self::anything());

        $this->validationGroupsProvider->expects(self::never())
            ->method(self::anything());

        $this->validator->expects(self::never())
            ->method(self::anything());

        $cachedData = [['productSku' => 'TEST']];

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function () use ($cachedData) {
                return $cachedData;
            });

        self::assertEquals($cachedData, $this->provider->getData($checkout));
    }

    public function testGetDataWhenNoLineItems(): void
    {
        $checkout = new Checkout();

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::never())
            ->method(self::anything());

        $this->validationGroupsProvider->expects(self::never())
            ->method(self::anything());

        $this->validator->expects(self::never())
            ->method(self::anything());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenEmptyLineItem(): void
    {
        $checkout = new Checkout();
        $emptyLineItem = new CheckoutLineItem();
        $checkout->addLineItem($emptyLineItem);

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::never())
            ->method(self::anything());

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => null,
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenFreeFormProductWithoutPrice(): void
    {
        $checkout = new Checkout();
        $lineItemWithFreeFormProduct = (new CheckoutLineItem())
            ->setFreeFormProduct('Sample free form product');
        $checkout->addLineItem($lineItemWithFreeFormProduct);

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::never())
            ->method(self::anything());

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithFreeFormProduct->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenFreeFormProductWithPrice(): void
    {
        $checkout = new Checkout();
        $lineItemWithFreeFormProduct = (new CheckoutLineItem())
            ->setFreeFormProduct('Sample free form product')
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, 'USD'))
            ->setQuantity(3);
        $checkout->addLineItem($lineItemWithFreeFormProduct);

        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::never())
            ->method(self::anything());

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => 3,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithFreeFormProduct->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemWithFreeFormProduct->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithFixedPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, 'USD'))
            ->setQuantity(3);
        $lineItemWithRegularProductWithFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemWithRegularProductWithFixedPrice->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithFixedPriceButWithoutPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setPriceFixed(true)
            ->setQuantity(3);
        $lineItemWithRegularProductWithFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithNotFixedPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        // Just to make sure keys are preserved when passed to getProductLineItemsPrices.
        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);
        $checkout->removeLineItem($lineItemWithRegularProductWithNotFixedPrice);
        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $lineItemPrice = new ProductLineItemPrice(
            $lineItemWithRegularProductWithNotFixedPrice,
            Price::create(123.4567, 'USD'),
            123.4567 * 3
        );
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([1 => $lineItemWithRegularProductWithNotFixedPrice])
            ->willReturn([1 => $lineItemPrice]);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithNotFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithNotFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemPrice->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithNotFixedPriceWhenNoPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithRegularProductWithNotFixedPrice])
            ->willReturn([]);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithNotFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithNotFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenNotValid(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $shoppingList)
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            'sample error',
                            null,
                            [],
                            $lineItemWithRegularProductWithNotFixedPrice,
                            '[0].quantity',
                            42
                        ),
                    ]
                )
            );

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenHasViolationWithoutPropertyPath(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(false);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $shoppingList)
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            'sample error',
                            null,
                            [],
                            $lineItemWithRegularProductWithNotFixedPrice,
                            null,
                            42
                        ),
                    ]
                )
            );

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenHasViolationForNotCollectionElement(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(false);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $shoppingList)
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            'sample error',
                            null,
                            [],
                            $lineItemWithRegularProductWithNotFixedPrice,
                            'sampleProperty',
                            42
                        ),
                    ]
                )
            );

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenNotGranted(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1');
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(false);

        $this->validationGroupsProvider->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, $checkout->getSourceEntity())
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout->getLineItems(), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList());

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals([], $this->provider->getData($checkout));
    }
}
