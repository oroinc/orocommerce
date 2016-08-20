<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\AccountBundle\EventListener\ProductSelectDBQueryEventListener;
use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductSelectDBQueryEventListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var ProductSelectDBQueryEventListener
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
            ->getMockBuilder('Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier')
            ->disableOriginalConstructor()->getMock();
        $this->listener = new ProductSelectDBQueryEventListener(
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
     * @return ProductSelectDBQueryEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();
    }
}
