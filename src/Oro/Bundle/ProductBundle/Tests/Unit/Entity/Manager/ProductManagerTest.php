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
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ProductManager */
    private $productManager;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->productManager = new ProductManager($this->eventDispatcher);
    }

    public function testRestrictQueryBuilder()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $params = ['some' => 'params'];

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ProductDBQueryRestrictionEvent($qb, new ParameterBag($params)),
                ProductDBQueryRestrictionEvent::NAME
            );

        $this->productManager->restrictQueryBuilder($qb, $params);
    }

    public function testRestrictSearchQuery()
    {
        $query = $this->createMock(Query::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ProductSearchQueryRestrictionEvent($query),
                ProductSearchQueryRestrictionEvent::NAME
            );

        $this->productManager->restrictSearchQuery($query);
    }
}
