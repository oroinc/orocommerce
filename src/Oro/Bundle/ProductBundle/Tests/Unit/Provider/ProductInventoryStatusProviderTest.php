<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\ProductBundle\Provider\ProductInventoryStatusProvider;

class ProductInventoryStatusProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAvailableProductInventoryStatuses()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(EntityRepository::class);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($repository);

        $enumValue1 = new InventoryStatus('test_enum', 'In Stock', 'in_stock');
        $enumValue2 = new InventoryStatus('test_enum', 'Out of Stock', 'out_of_stock');
        $enumValue3 = new InventoryStatus('test_enum', 'Discontinued', 'discontinued');

        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$enumValue1, $enumValue2, $enumValue3]);

        $expected = [
            'test_enum.in_stock' => 'In Stock',
            'test_enum.out_of_stock' => 'Out of Stock',
            'test_enum.discontinued' => 'Discontinued',
        ];

        $provider = new ProductInventoryStatusProvider($doctrine);

        $this->assertEquals($expected, $provider->getAvailableProductInventoryStatuses());

        // Checks local caching.
        $this->assertEquals($expected, $provider->getAvailableProductInventoryStatuses());
    }
}
