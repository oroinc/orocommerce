<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ProductManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var  ProductManager */
    protected $productManager;

    public function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->productManager = new ProductManager($this->eventDispatcher);
    }

    public function testRestrictQueryBuilder()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $params = ['some' => 'params'];

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProductDBQueryRestrictionEvent::NAME,
                new ProductDBQueryRestrictionEvent($qb, new ParameterBag($params))
            );

        $this->productManager->restrictQueryBuilder($qb, $params);
    }

    public function testRestrictSearchQuery()
    {
        $query = $this->createMock(Query::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProductSearchQueryRestrictionEvent::NAME,
                new ProductSearchQueryRestrictionEvent($query)
            );

        $this->productManager->restrictSearchQuery($query);
    }
}
