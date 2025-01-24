<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteProductOfferRepository;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\DatagridProductOffersLoaderListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridProductOffersLoaderListenerTest extends TestCase
{
    private ManagerRegistry $doctrine;
    private DatagridProductOffersLoaderListener $listener;
    private QuoteProductOfferRepository|MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(QuoteProductOfferRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(QuoteProductOffer::class)
            ->willReturn($this->repository);

        $this->listener = new DatagridProductOffersLoaderListener($this->doctrine);
    }

    public function testOnResultAfter(): void
    {
        $record1 = new ResultRecord(['id' => 1]);
        $record2 = new ResultRecord(['id' => 2]);
        $record3 = new ResultRecord(['id' => 3]);

        $quoteProductOffers = [
            1 => new ArrayCollection(['Offer 1']),
            2 => new ArrayCollection(['Offer 2']),
        ];

        $this->repository->expects(self::once())
            ->method('getProductOffersByQuoteIds')
            ->with([1, 2, 3])
            ->willReturn($quoteProductOffers);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$record1, $record2, $record3]
        );

        $this->listener->onResultAfter($event);

        self::assertEquals(new ArrayCollection(['Offer 1']), $record1->getValue('quoteProductOffers'));
        self::assertEquals(new ArrayCollection(['Offer 2']), $record2->getValue('quoteProductOffers'));
        self::assertEquals(new ArrayCollection(), $record3->getValue('quoteProductOffers'));
    }
}
