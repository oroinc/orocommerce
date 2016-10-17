<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\AccountBundle\EventListener\ProductDBQueryRestrictionEventListener;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductDBQueryRestrictionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbModifier;

    /**
     * @var ProductDBQueryRestrictionEventListener
     */
    private $listener;

    /**
     * @var ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchQueryModifier;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper      = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->dbModifier          = $this
            ->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchQueryModifier = $this
            ->getMockBuilder(ProductVisibilitySearchQueryModifier::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new ProductDBQueryRestrictionEventListener(
            $this->frontendHelper,
            $this->dbModifier,
            $this->searchQueryModifier
        );
    }

    public function testOnDBQuery()
    {
        $this->setupRequest();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()->getMock();

        $event = $this->getDBEventMock();

        $event->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->dbModifier->expects($this->once())
            ->method('modify')
            ->with($queryBuilder);

        $this->listener->onDBQuery($event);
    }

    public function testOnDBQueryNotFrontend()
    {
        $this->setupRequest(false);

        $event = $this->getDBEventMock();

        $event->expects($this->never())
            ->method('getQueryBuilder');

        $this->dbModifier->expects($this->never())
            ->method('modify');

        $this->listener->onDBQuery($event);
    }

    public function testOnSearchQuery()
    {
        $this->setupRequest();

        $event = $this->getSQEventMock();

        $query = $this->getMock(Query::class);

        $event->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->searchQueryModifier->expects($this->once())
            ->method('modify')
            ->with($query);

        $this->listener->onSearchQuery($event);
    }

    /**
     * @param bool $frontend
     */
    protected function setupRequest($frontend = true)
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($frontend);
    }

    /**
     * @return ProductDBQueryRestrictionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDBEventMock()
    {
        return $this->getMockBuilder(ProductDBQueryRestrictionEvent::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @return ProductSearchQueryRestrictionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSQEventMock()
    {
        return $this->getMockBuilder(ProductSearchQueryRestrictionEvent::class)
            ->disableOriginalConstructor()->getMock();
    }
}
