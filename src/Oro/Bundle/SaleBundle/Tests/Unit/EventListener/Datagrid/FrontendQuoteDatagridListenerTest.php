<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter;
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

    public function testOnResultBefore()
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('q.id')
            ->from(Quote::class, 'qoute');

        $this->listener->onResultBeforeQuery(new OrmResultBeforeQuery($this->datagrid, $qb));

        $this->assertEquals(
            'SELECT q.id FROM Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid\Quote qoute '.
            'WHERE qoute.internal_status IS NULL OR qoute.internal_status IN(:internalStatuses)',
            $qb->getQuery()->getDQL()
        );

        $this->assertEquals(
            new ArrayCollection([
                new Parameter('internalStatuses', FrontendQuotePermissionVoter::FRONTEND_INTERNAL_STATUSES),
            ]),
            $qb->getParameters()
        );
    }
}
