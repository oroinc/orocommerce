<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuantityToOrderValidatorServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $fallbackResolver;

    /** @var PreloadingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $preloadingManager;

    /** @var QuantityToOrderValidatorService */
    private $quantityToOrderValidatorService;

    private array $fieldsToPreload = [
        'product' => [
            'minimumQuantityToOrder' => [],
            'maximumQuantityToOrder' => [],
            'category' => [
                'minimumQuantityToOrder' => [],
                'maximumQuantityToOrder' => [],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->fallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($message) {
                return $message;
            });

        $this->quantityToOrderValidatorService = new QuantityToOrderValidatorService(
            $this->fallbackResolver,
            $translator,
            $this->preloadingManager
        );
    }

    public function testIsLineItemListValidReturnsTrueIfNoProduct()
    {
        $lineItems = [new LineItem()];

        $this->preloadingManager->expects($this->once())
            ->method('preloadInEntities')
            ->with($lineItems, $this->fieldsToPreload);

        $this->assertTrue($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsLineItemListValidReturnsFalseIfInvalidLimits()
    {
        $lineItem = new LineItem();
        $lineItem->setProduct(new Product());
        $lineItem->setQuantity(1);
        $lineItems = [$lineItem];

        $this->preloadingManager->expects($this->once())
            ->method('preloadInEntities')
            ->with($lineItems, $this->fieldsToPreload);

        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(3);
        $this->assertFalse($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsLineItemListValidReturnsTrueIfValidLimits()
    {
        $lineItem = new LineItem();
        $lineItem->setProduct(new Product());
        $lineItem->setQuantity(3);
        $lineItems = [$lineItem];

        $this->preloadingManager->expects($this->once())
            ->method('preloadInEntities')
            ->with($lineItems, $this->fieldsToPreload);

        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(3);
        $this->assertTrue($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsHigherThanMaxLimit()
    {
        $this->assertFalse($this->quantityToOrderValidatorService->isHigherThanMaxLimit(5, 3));
        $this->assertFalse($this->quantityToOrderValidatorService->isHigherThanMaxLimit(3, 3));
        $this->assertTrue($this->quantityToOrderValidatorService->isHigherThanMaxLimit(3, 5));
    }

    public function testIsLowerThenMinLimit()
    {
        $this->assertFalse($this->quantityToOrderValidatorService->isLowerThenMinLimit(3, 5));
        $this->assertFalse($this->quantityToOrderValidatorService->isLowerThenMinLimit(3, 3));
        $this->assertTrue($this->quantityToOrderValidatorService->isLowerThenMinLimit(5, 3));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnFalseIfNotNumeric()
    {
        $product = $this->createMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn('');

        $this->assertFalse($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnFalse()
    {
        $product = $this->createMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturnOnConsecutiveCalls(3, 5);

        $this->assertFalse($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnTrue()
    {
        $product = $this->createMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturnOnConsecutiveCalls(5, 3);

        $this->assertTrue($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testGetMaximumErrorIfInvalidOnZeroQuantity()
    {
        $product = new ProductStub();
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(0);
        $this->assertEquals(
            'oro.inventory.product.error.quantity_limit_is_zero',
            $this->quantityToOrderValidatorService->getMaximumErrorIfInvalid($product, 15)
        );
    }

    public function testGetMaximumErrorIfInvalidWithQuantityOverMaxValue()
    {
        $product = new ProductStub();
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(3);
        $this->assertEquals(
            'oro.inventory.product.error.quantity_over_max_limit',
            $this->quantityToOrderValidatorService->getMaximumErrorIfInvalid($product, 5)
        );
    }

    public function testGetMinimumErrorIfInvalidWithQuantityBelowMinValue()
    {
        $product = new ProductStub();
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(3);

        $this->assertEquals(
            'oro.inventory.product.error.quantity_below_min_limit',
            $this->quantityToOrderValidatorService->getMinimumErrorIfInvalid($product, 1)
        );
    }

    public function testGetMinimumLimit()
    {
        $product = $this->createMock(Product::class);
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(1);

        $this->assertEquals(1, $this->quantityToOrderValidatorService->getMinimumLimit($product));
    }

    public function testGetMaximumLimit()
    {
        $product = $this->createMock(Product::class);
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(1);

        $this->assertEquals(1, $this->quantityToOrderValidatorService->getMaximumLimit($product));
    }
}
