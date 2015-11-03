<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\ParameterBag;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\EventListener\RestrictDisabledProductsEventListener;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

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
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier')
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

        $event = new ProductSelectDBQueryEvent($qb, new ParameterBag([]));
        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($qb, [Product::STATUS_ENABLED]);

        $this->listener->onDBQuery($event);
    }
}
