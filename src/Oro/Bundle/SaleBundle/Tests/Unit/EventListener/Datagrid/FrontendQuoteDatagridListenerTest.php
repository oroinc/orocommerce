<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendQuoteDatagridListener;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class FrontendQuoteDatagridListenerTest extends OrmTestCase
{
    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagrid;

    /** @var FrontendQuoteDatagridListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new FrontendQuoteDatagridListener();
    }

    public function testOnBuildAfter()
    {
        $em = $this->getTestEntityManager();

        $qb = $em->createQueryBuilder()
            ->select('q.id')
            ->from(Quote::class, 'quote');

        $countQb = $em->createQueryBuilder()
            ->select('COUNT(q)')
            ->from(Quote::class, 'quote');

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $datasource->expects($this->once())
            ->method('getCountQb')
            ->willReturn($countQb);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->onBuildAfter(new BuildAfter($this->datagrid));

        $this->assertEquals(
            sprintf(
                'SELECT q.id FROM %s quote ' .
                'WHERE quote.internal_status IS NULL OR quote.internal_status IN(:internalStatuses)',
                Quote::class
            ),
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals(
            sprintf(
                'SELECT COUNT(q) FROM %s quote ' .
                'WHERE quote.internal_status IS NULL OR quote.internal_status IN(:internalStatuses)',
                Quote::class
            ),
            $countQb->getQuery()->getDQL()
        );

        $expectedParameters = new ArrayCollection(
            [new Parameter('internalStatuses', Quote::FRONTEND_INTERNAL_STATUSES)]
        );
        $this->assertEquals($expectedParameters, $qb->getParameters());
        $this->assertEquals($expectedParameters, $countQb->getParameters());
    }
}
