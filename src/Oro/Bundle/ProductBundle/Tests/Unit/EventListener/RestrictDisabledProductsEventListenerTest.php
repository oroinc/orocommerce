<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\RestrictDisabledProductsEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\HttpFoundation\ParameterBag;

class RestrictDisabledProductsEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilityQueryBuilderModifier
     */
    private $queryBuilderModifier;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilitySearchQueryModifier
     */
    private $searchQueryModifier;

    /**
     * @var RestrictDisabledProductsEventListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->searchQueryModifier = $this
            ->getMockBuilder(ProductVisibilitySearchQueryModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilderModifier = $this
            ->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictDisabledProductsEventListener(
            $this->searchQueryModifier,
            $this->queryBuilderModifier
        );
    }

    public function testOnDBQuery()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ProductDBQueryRestrictionEvent($qb, new ParameterBag([]));
        $this->queryBuilderModifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($qb, [Product::STATUS_ENABLED]);

        $this->listener->onDBQuery($event);
    }

    public function testOnSearchQuery()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Query $query */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ProductSearchQueryRestrictionEvent($query, new ParameterBag([]));
        $this->searchQueryModifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($query, [Product::STATUS_ENABLED]);

        $this->listener->onSearchQuery($event);
    }
}
