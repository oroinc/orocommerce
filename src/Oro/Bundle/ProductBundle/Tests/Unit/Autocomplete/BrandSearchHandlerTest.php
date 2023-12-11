<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Autocomplete\BrandSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Component\Testing\Unit\EntityTrait;

class BrandSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected function testConvertItem(): void
    {
        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $searchHandler = new BrandSearchHandler(Brand::class, ['name', 'status'], $entityNameResolver);
        $searchHandler->setPropertyAccessor(PropertyAccess::createPropertyAccessor());

        $entity = $this->getEntity(Brand::class, ['id' => 23, 'status' => 'test_status']);

        $entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($entity)
            ->willReturn('Brand Name');

        self::assertEquals(
            [
                'id' => 23,
                'name' => 'Brand Name',
                'status' => 'test_status'
            ],
            $searchHandler->convertItem($entity)
        );
    }
}
