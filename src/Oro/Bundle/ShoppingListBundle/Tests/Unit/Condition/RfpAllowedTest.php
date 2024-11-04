<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Condition\RfpAllowed;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class RfpAllowedTest extends \PHPUnit\Framework\TestCase
{
    private const PROPERTY_PATH_NAME = 'lineItems';

    /** @var ProductRFPAvailabilityProvider */
    private $productAvailabilityProvider;

    /** @var PropertyPathInterface */
    private $propertyPath;

    /** @var RfpAllowed */
    private $rfpAllowed;

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

        $this->rfpAllowed = new RfpAllowed($this->productAvailabilityProvider);
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
}
