<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\VisibilityBundle\EventListener\ProductDBQueryRestrictionEventListener;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductDBQueryRestrictionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject
     */
    private $modifier;

    /**
     * @var ProductDBQueryRestrictionEventListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->frontendHelper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()->getMock();
        $this->modifier = $this
            ->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
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
     * @return ProductDBQueryRestrictionEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEventMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent')
            ->disableOriginalConstructor()->getMock();
    }
}
