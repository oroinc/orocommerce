<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;

class EnumVariantFieldValueHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $enumValueProvider;

    /** @var EnumVariantFieldValueHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new EnumVariantFieldValueHandler($this->doctrineHelper, $this->enumValueProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->enumValueProvider,
            $this->handler
        );
    }

    public function testGetType()
    {
        $this->assertEquals(EnumVariantFieldValueHandler::TYPE, $this->handler->getType());
    }

    public function testGetValues()
    {
        $fieldName = 'testField';
        $enumValues = ['red', 'green'];

        $enumCode = ExtendHelper::generateEnumCode(Product::class, $fieldName);
        $this->enumValueProvider->expects($this->once())
            ->method('getEnumChoicesByCode')
            ->with($enumCode)
            ->willReturn($enumValues);

        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));
    }

    public function testGetScalarValue()
    {
        $fieldValue = new EnumValue();
        $scalarValue = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($fieldValue)
            ->willReturn($scalarValue);

        $this->assertEquals($scalarValue, $this->handler->getScalarValue($fieldValue));
    }
}
