<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\ShoppingListBundle\Condition\RfpAllowed;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class RfpAllowedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PROPERTY_PATH_NAME = 'testPropertyPath';

    /** @var PropertyPathInterface */
    private $propertyPath;

    /** @var RfpAllowed */
    private $rfpAllowed;

    protected function setUp(): void
    {
        $requestDataStorageExtension = $this->createMock(RequestDataStorageExtension::class);
        $requestDataStorageExtension->expects($this->any())
            ->method('isAllowedRFPByProductsIds')
            ->willReturn(true);

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->willReturn(self::PROPERTY_PATH_NAME);
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->willReturn([self::PROPERTY_PATH_NAME]);

        $this->rfpAllowed = new RfpAllowed($requestDataStorageExtension);
    }

    public function testGetName()
    {
        $this->assertEquals('rfp_allowed', $this->rfpAllowed->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(RfpAllowed::class, $this->rfpAllowed->initialize([$this->propertyPath]));
    }

    public function testToArray()
    {
        $result = $this->rfpAllowed->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@rfp_allowed']['parameters'][0]);
    }

    public function testCompile()
    {
        $result = $this->rfpAllowed->compile('$factoryAccessor');

        self::assertStringContainsString('$factoryAccessor->create(\'rfp_allowed\'', $result);
    }

    public function testSetContextAccessor()
    {
        $contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->rfpAllowed->setContextAccessor($contextAccessor);

        $this->assertInstanceOf(
            get_class($contextAccessor),
            ReflectionUtil::getPropertyValue($this->rfpAllowed, 'contextAccessor')
        );
    }

    public function testEvaluates()
    {
        $lineItem = $this->getEntity(LineItem::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 2, 'sku' => '123']);
        $lineItem->setProduct($product);
        $lineItems = [$lineItem];

        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturn($lineItems);

        $this->rfpAllowed->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 2]);

        $this->assertTrue($this->rfpAllowed->evaluate($shoppingList->getLineItems()));
    }
}
