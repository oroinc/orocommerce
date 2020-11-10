<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ProductBundle\Cache\EnumProductVariantFieldValuesCacheWarmer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;

class EnumProductVariantFieldValuesCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityWithFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityWithFieldsProvider;

    /** @var EnumVariantFieldValueHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $enumVariantFieldValueHandler;

    /** @var EnumProductVariantFieldValuesCacheWarmer */
    private $warmer;

    protected function setUp(): void
    {
        $this->entityWithFieldsProvider = $this->createMock(EntityWithFieldsProvider::class);
        $this->enumVariantFieldValueHandler = $this->createMock(EnumVariantFieldValueHandler::class);

        $this->warmer = new EnumProductVariantFieldValuesCacheWarmer(
            $this->entityWithFieldsProvider,
            $this->enumVariantFieldValueHandler
        );
    }

    public function testWarmUp(): void
    {
        $this->entityWithFieldsProvider->expects($this->once())
            ->method('getFieldsForEntity')
            ->with(Product::class)
            ->willReturn(
                [
                    ['name' => 'field1', 'related_entity_name' => AbstractEnumValue::class],
                    ['name' => 'field2'],
                    ['name' => 'field3', 'related_entity_name' => Product::class],
                ]
            );

        $this->enumVariantFieldValueHandler->expects($this->once())
            ->method('getPossibleValues')
            ->with('field1');

        $this->warmer->warmUp('cache/dir');
    }

    public function testIsOptional(): void
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
