<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\DatagridProductNameLoaderListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridProductNameLoaderListenerTest extends TestCase
{
    private DatagridProductNameLoaderListener $listener;
    private ManagerRegistry|MockObject $doctrine;
    private ProductRepository|MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ProductRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $this->listener = new DatagridProductNameLoaderListener($this->doctrine, 'productId', 'productNames');
    }

    public function testOnResultAfter(): void
    {
        $resultRecord1 = new ResultRecord([
            'id' => 1,
            'productId' => 11
        ]);
        $resultRecord2 = new ResultRecord([
            'id' => 2,
            'productId' => 22
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'productId' => 33
        ]);

        $productNamesCollections = [
            11 => new ArrayCollection(['Product 1']),
            22 => new ArrayCollection(['Product 2']),
        ];

        $this->repository
            ->expects(self::once())
            ->method('getProductNamesByProductIds')
            ->with([11, 22, 33])
            ->willReturn($productNamesCollections);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3]
        );

        $this->listener->onResultAfter($event);

        self::assertEquals(new ArrayCollection(['Product 1']), $resultRecord1->getValue('productNames'));
        self::assertEquals(new ArrayCollection(['Product 2']), $resultRecord2->getValue('productNames'));
        self::assertEquals(new ArrayCollection(), $resultRecord3->getValue('productNames'));
    }
}
