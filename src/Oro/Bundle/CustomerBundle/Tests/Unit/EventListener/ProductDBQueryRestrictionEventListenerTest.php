<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CustomerBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\CustomerBundle\EventListener\ProductDBQueryRestrictionEventListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

class ProductDBQueryRestrictionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductDBQueryRestrictionEventListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()->getMock();
        $this->modifier = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Model\ProductVisibilityQueryBuilderModifier')
            ->disableOriginalConstructor()->getMock();
        $this->listener = new ProductDBQueryRestrictionEventListener(
            $this->frontendHelper,
            $this->modifier
        );
    }

    public function testOnDBQuery()
    {
        $this->setupRequest();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $event = $this->getEventMock();

        $event->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with($queryBuilder);

        $this->listener->onDBQuery($event);
    }

    public function testOnDBQueryNotFrontend()
    {
        $this->setupRequest(false);

        $event = $this->getEventMock();

        $event->expects($this->never())
            ->method('getQueryBuilder');

        $this->modifier->expects($this->never())
            ->method('modify');

        $this->listener->onDBQuery($event);
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
    protected function getEventMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent')
            ->disableOriginalConstructor()->getMock();
    }
}
