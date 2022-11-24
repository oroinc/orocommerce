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
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilityQueryBuilderModifier */
    private $queryBuilderModifier;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilitySearchQueryModifier */
    private $searchQueryModifier;

    /** @var RestrictDisabledProductsEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->searchQueryModifier = $this->createMock(ProductVisibilitySearchQueryModifier::class);
        $this->queryBuilderModifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);

        $this->listener = new RestrictDisabledProductsEventListener(
            $this->searchQueryModifier,
            $this->queryBuilderModifier
        );
    }

    public function testOnDBQuery()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $event = new ProductDBQueryRestrictionEvent($qb, new ParameterBag([]));
        $this->queryBuilderModifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($qb, [Product::STATUS_ENABLED]);

        $this->listener->onDBQuery($event);
    }

    public function testOnSearchQuery()
    {
        $query = $this->createMock(Query::class);

        $event = new ProductSearchQueryRestrictionEvent($query, new ParameterBag([]));
        $this->searchQueryModifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($query, [Product::STATUS_ENABLED]);

        $this->listener->onSearchQuery($event);
    }
}
