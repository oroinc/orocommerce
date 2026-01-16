<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Condition\RfpAllowed;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class RfpAllowedTest extends TestCase
{
    private const string PROPERTY_PATH_NAME = 'lineItems';

    private ProductRFPAvailabilityProvider&MockObject $productAvailabilityProvider;
    private PropertyPathInterface&MockObject $propertyPath;
    private InvalidShoppingListLineItemsProvider&MockObject $provider;

    private RfpAllowed $rfpAllowed;

    #[\Override]
    protected function setUp(): void
    {
        $this->productAvailabilityProvider = $this->createMock(ProductRFPAvailabilityProvider::class);

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects(self::any())
            ->method('__toString')
            ->willReturn(self::PROPERTY_PATH_NAME);
        $this->propertyPath->expects(self::any())
            ->method('getElements')
            ->willReturn([self::PROPERTY_PATH_NAME]);

        $this->provider = $this->createMock(InvalidShoppingListLineItemsProvider::class);

        $this->rfpAllowed = new RfpAllowed($this->productAvailabilityProvider);
        $this->rfpAllowed->setInvalidShoppingListLineItemsProvider($this->provider);
    }

    public function testGetName(): void
    {
        self::assertEquals('rfp_allowed', $this->rfpAllowed->getName());
    }

    public function testInitialize(): void
    {
        self::assertInstanceOf(RfpAllowed::class, $this->rfpAllowed->initialize([$this->propertyPath]));
    }

    public function testToArray(): void
    {
        $result = $this->rfpAllowed->initialize([$this->propertyPath])->toArray();

        self::assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@rfp_allowed']['parameters'][0]);
    }

    public function testCompile(): void
    {
        $this->rfpAllowed->initialize([$this->propertyPath]);
        $result = $this->rfpAllowed->compile('$factoryAccessor');

        self::assertStringContainsString('$factoryAccessor->create(\'rfp_allowed\'', $result);
    }

    public function testEvaluate(): void
    {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, 1);
        $product = new Product();
        ReflectionUtil::setId($product, 2);
        $product->setSku('123');
        $lineItem->setProduct($product);
        $lineItems = [$lineItem];
        $shoppingList = new ShoppingList();

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->with(self::identicalTo($shoppingList), self::identicalTo($this->propertyPath))
            ->willReturn($lineItems);

        $this->productAvailabilityProvider->expects(self::any())
            ->method('hasProductsAllowedForRFP')
            ->with([2])
            ->willReturn(true);

        $this->rfpAllowed->initialize([$this->propertyPath]);
        $this->rfpAllowed->setContextAccessor($contextAccessor);
        self::assertTrue($this->rfpAllowed->evaluate($shoppingList));
    }

    public function testEvaluateWhenValueIsArrayCollection(): void
    {
        $shoppingList = new ShoppingList();

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->with(self::identicalTo($shoppingList), self::identicalTo($this->propertyPath))
            ->willReturn(new ArrayCollection([]));


        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Property must be a valid "Doctrine\Common\Collections\ArrayCollection",' .
            ' but got "Doctrine\Common\Collections\ArrayCollection".');

        $this->rfpAllowed->initialize([$this->propertyPath]);
        $this->rfpAllowed->setContextAccessor($contextAccessor);
        $this->rfpAllowed->evaluate($shoppingList);
    }

    public function testEvaluateWhenNoValue(): void
    {
        $shoppingList = new ShoppingList();

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->with(self::identicalTo($shoppingList), self::identicalTo($this->propertyPath))
            ->willReturn([]);

        $this->rfpAllowed->initialize([$this->propertyPath]);
        $this->rfpAllowed->setContextAccessor($contextAccessor);

        self::assertFalse($this->rfpAllowed->evaluate($shoppingList));
    }

    public function testEvaluateWhenEmptyPersistentCollection(): void
    {
        $shoppingList = new ShoppingList();

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->with(self::identicalTo($shoppingList), self::identicalTo($this->propertyPath))
            ->willReturn(new PersistentCollection(
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(ClassMetadata::class),
                new ArrayCollection([])
            ));

        $this->rfpAllowed->initialize([$this->propertyPath]);
        $this->rfpAllowed->setContextAccessor($contextAccessor);

        self::assertFalse($this->rfpAllowed->evaluate($shoppingList));
    }

    public function testEvaluateWhenInvalidShoppingList(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, 1);
        $product = new Product();
        ReflectionUtil::setId($product, 2);
        $product->setSku('123');
        $lineItem->setProduct($product);
        $lineItem->setShoppingList($shoppingList);
        $lineItems = [$lineItem];

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->with(self::identicalTo($shoppingList), self::identicalTo($this->propertyPath))
            ->willReturn($lineItems);

        $this->provider->expects(self::any())
            ->method('getInvalidLineItemsIds')
            ->with($shoppingList->getLineItems(), 'rfq')
            ->willReturn([1]);

        $this->rfpAllowed->initialize([$this->propertyPath]);
        $this->rfpAllowed->setContextAccessor($contextAccessor);

        self::assertFalse($this->rfpAllowed->evaluate($shoppingList));
    }
}
