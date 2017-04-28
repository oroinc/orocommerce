<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendQuoteDatagridListener;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class FrontendQuoteDatagridListenerTest extends OrmTestCase
{
    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    /** @var FrontendQuoteDatagridListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new FrontendQuoteDatagridListener();
    }

    public function testOnResultBeforeQuery()
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('q.id')
            ->from(Quote::class, 'quote');

        $this->listener->onResultBeforeQuery(new OrmResultBeforeQuery($this->datagrid, $qb));

        $this->assertEquals(
            sprintf(
                'SELECT q.id FROM %s quote '.
                'WHERE quote.internal_status IS NULL OR quote.internal_status IN(:internalStatuses)',
                Quote::class
            ),
            $qb->getQuery()->getDQL()
        );

        $this->assertEquals(
            new ArrayCollection([
                new Parameter('internalStatuses', Quote::FRONTEND_INTERNAL_STATUSES),
            ]),
            $qb->getParameters()
        );
    }
}
