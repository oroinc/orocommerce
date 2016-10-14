<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\ShoppingListBundle\Condition\RfpAllowed;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class RfpAllowedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var RequestDataStorageExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestDataStorageExtension;

    /**
     * @var RfpAllowed
     */
    protected $rfpAllowed;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    protected function setUp()
    {
        $this->requestDataStorageExtension = $this->getMockBuilder(RequestDataStorageExtension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestDataStorageExtension->expects($this->any())
            ->method('isAllowedRFP')
            ->willReturn(true);

        $this->propertyPath = $this->getMockBuilder(PropertyPathInterface::class)
            ->getMock();
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->rfpAllowed = new RfpAllowed($this->requestDataStorageExtension);
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

        $this->assertContains('$factoryAccessor->create(\'rfp_allowed\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rfpAllowed->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(get_class($this->rfpAllowed), 'contextAccessor');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(get_class($contextAccessor), $reflection->getValue($this->rfpAllowed));
    }

    public function testEvaluates()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2, 'sku' => '123']);
        $lineItem->setProduct($product);
        /** @var LineItem[] $lineItems */
        $lineItems = [$lineItem];

        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($lineItems));

        $this->rfpAllowed->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 2]);

        $this->assertTrue($this->rfpAllowed->evaluate($shoppingList->getLineItems()));
    }
}
