<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

class ProductManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var  ProductManager */
    protected $productManager;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->productManager = new ProductManager($this->eventDispatcher);
    }

    public function testRestrictQueryBuilder()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
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
        $query = $this->getMock(SearchQueryInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProductSearchQueryRestrictionEvent::NAME,
                new ProductSearchQueryRestrictionEvent($query)
            );

        $this->productManager->restrictSearchQuery($query);
    }
}
