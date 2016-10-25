<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\RestrictDisabledProductsEventListener;

class RestrictDisabledProductsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductVisibilitySearchQueryModifier
     */
    protected $modifier;

    /**
     * @var RestrictDisabledProductsEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->modifier = $this
            ->getMockBuilder(ProductVisibilitySearchQueryModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictDisabledProductsEventListener($this->modifier);
    }

    public function testOnDBQuery()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Query $query */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ProductSearchQueryRestrictionEvent($query, new ParameterBag([]));
        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($query, [Product::STATUS_ENABLED]);

        $this->listener->onSearchQuery($event);
    }
}
