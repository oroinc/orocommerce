<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
            ->with('Extend\Entity\EV_Prod_Inventory_Status')
            ->willReturn($repository);

        $enumValue1 = new InventoryStatus('in_stock', 'In Stock');
        $enumValue2 = new InventoryStatus('out_of_stock', 'Out of Stock');
        $enumValue3 = new InventoryStatus('discontinued', 'Discontinued');

        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$enumValue1, $enumValue2, $enumValue3]);

        $expected = [
            'in_stock' => 'In Stock',
            'out_of_stock' => 'Out of Stock',
            'discontinued' => 'Discontinued',
        ];

        $provider = new ProductInventoryStatusProvider($doctrine);

        $this->assertEquals($expected, $provider->getAvailableProductInventoryStatuses());

        // Checks local caching.
        $this->assertEquals($expected, $provider->getAvailableProductInventoryStatuses());
    }
}
