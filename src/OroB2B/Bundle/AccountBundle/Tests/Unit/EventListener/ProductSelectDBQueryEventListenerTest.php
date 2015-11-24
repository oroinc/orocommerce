<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\AccountBundle\EventListener\ProductSelectDBQueryEventListener;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductSelectDBQueryEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

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
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->frontendHelper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()->getMock();
        $this->modifier = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier')
            ->disableOriginalConstructor()->getMock();
        $this->listener = new ProductSelectDBQueryEventListener(
            $this->requestStack,
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
        /** @var Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($frontend);
    }

    /**
     * @return ProductSelectDBQueryEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventMock()
    {
        return $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();
    }
}
