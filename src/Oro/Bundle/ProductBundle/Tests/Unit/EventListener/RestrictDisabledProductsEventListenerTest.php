<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\RestrictDisabledProductsEventListener;
use Oro\Bundle\SearchBundle\Query\Query;

class RestrictDisabledProductsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductVisibilityQueryBuilderModifier
     */
    private $queryBuilderModifier;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductVisibilitySearchQueryModifier
     */
    private $searchQueryModifier;

    /**
     * @var RestrictDisabledProductsEventListener
     */
    private $listener;

    protected function setUp()
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder $qb */
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|Query $query */
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
