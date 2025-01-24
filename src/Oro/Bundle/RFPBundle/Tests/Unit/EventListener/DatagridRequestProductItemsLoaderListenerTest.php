<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestProductItemRepository;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\EventListener\DatagridRequestProductItemsLoaderListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridRequestProductItemsLoaderListenerTest extends TestCase
{
    private DatagridRequestProductItemsLoaderListener $listener;
    private MockObject|ManagerRegistry $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->listener = new DatagridRequestProductItemsLoaderListener($this->doctrine);
    }

    public function testOnResultAfter(): void
    {
        $repository = $this->createMock(RequestProductItemRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(RequestProductItem::class)
            ->willReturn($repository);

        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord(['id' => 2]);
        $resultRecord3 = new ResultRecord(['id' => 3]);

        $productItemsCollections = [
            1 => new ArrayCollection(['Test 1']),
            2 => new ArrayCollection(['Test 2']),
        ];

        $repository
            ->expects(self::once())
            ->method('getProductItemsByRequestIds')
            ->with([1, 2, 3])
            ->willReturn($productItemsCollections);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3]
        );

        $this->listener->onResultAfter($event);

        self::assertEquals(new ArrayCollection(['Test 1']), $resultRecord1->getValue('requestProductItems'));
        self::assertEquals(new ArrayCollection(['Test 2']), $resultRecord2->getValue('requestProductItems'));
        self::assertEquals(new ArrayCollection(), $resultRecord3->getValue('requestProductItems'));
    }
}
