<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Provider\SerializedFieldProvider;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;

class VariantFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var VariantFieldProvider */
    private $variantFieldProvider;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var SerializedFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $serializedFieldProvider;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->serializedFieldProvider = $this->createMock(SerializedFieldProvider::class);

        $this->variantFieldProvider = new VariantFieldProvider($this->attributeManager, $this->serializedFieldProvider);
    }

    public function testGetVariantFields()
    {
        $attribute1 = new FieldConfigModel('other_type_field', 'text');
        $attribute2 = new FieldConfigModel('system_field', 'enum');
        $attribute3 = new FieldConfigModel('not_active_field', 'enum');
        $attribute4 = new FieldConfigModel('serialized_field', 'enum');
        $attribute5 = new FieldConfigModel('correct_variant_field', 'boolean');
        $attributeFamily = new AttributeFamily();

        $expectedResult['correct_variant_field'] = new VariantField('correct_variant_field', 'Some label');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByFamily')
            ->with($attributeFamily)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5]);

        $this->attributeManager->expects($this->exactly(4))
            ->method('isSystem')
            ->withConsecutive([$attribute2], [$attribute3], [$attribute4], [$attribute5])
            ->willReturnOnConsecutiveCalls(true, false, false, false);

        $this->attributeManager->expects($this->exactly(3))
            ->method('isActive')
            ->withConsecutive([$attribute3], [$attribute4], [$attribute5])
            ->willReturnOnConsecutiveCalls(false, true, true);

        $this->serializedFieldProvider->expects($this->exactly(2))
            ->method('isSerialized')
            ->withConsecutive([$attribute4], [$attribute5])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->attributeManager->expects($this->once())
            ->method('getAttributeLabel')
            ->with($attribute5)
            ->willReturn('Some label');

        $this->assertEquals($expectedResult, $this->variantFieldProvider->getVariantFields($attributeFamily));
    }
}
