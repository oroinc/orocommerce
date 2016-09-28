<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\RestrictDisabledProductsEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictDisabledProductsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var RestrictDisabledProductsEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->modifier = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictDisabledProductsEventListener($this->modifier);
    }

    public function testOnDBQuery()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ProductDBQueryRestrictionEvent($qb, new ParameterBag([]));
        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($qb, [Product::STATUS_ENABLED]);

        $this->listener->onDBQuery($event);
    }
}
