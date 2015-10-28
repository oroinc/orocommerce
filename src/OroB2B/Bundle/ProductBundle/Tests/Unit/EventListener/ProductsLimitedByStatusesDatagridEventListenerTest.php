<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectQueryEvent;
use OroB2B\Bundle\ProductBundle\EventListener\ProductsLimitedByStatusesDatagridEventListener;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class ProductsLimitedByStatusesDatagridEventListenerTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
    protected $qb;

    /** @var  ProductsLimitedByStatusesDatagridEventListener */
    protected $listener;

    public function testOnBuildAfter()
    {
        $this->request = new Request();
        $params = ['some' => 'data'];
        $this->request->request->set(ProductSelectType::DATA_PARAMETERS, $params);
        $this->setListener();
        $event = $this->getEvent();
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            ProductSelectQueryEvent::NAME,
            new ProductSelectQueryEvent($this->qb, $params)
        );
        $this->listener->onBuildAfter($event);
    }

    /**
     * @dataProvider onBuildAfterWithoutRequestOrParamsDataProvider
     */
    public function testOnBuildAfterWithoutRequestOrParams($request)
    {
        $this->request = $request;
        $this->setListener();
        $event = $this->getEvent();
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->listener->onBuildAfter($event);
    }

    public function onBuildAfterWithoutRequestOrParamsDataProvider()
    {
        $emptyParamsRequest = new Request();
        $emptyParamsRequest->request->set(ProductSelectType::DATA_PARAMETERS, []);

        return
            [
                'withoutRequest' => ['request' => null],
                'withoutParams' => ['request' => new Request()],
                'withEmptyParams' => ['request' => $emptyParamsRequest],
            ];
    }

    /**
     * @return BuildAfter
     */
    protected function getEvent()
    {
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        /** @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->once())->method('getQueryBuilder')->willReturn($this->qb);
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->once())->method('getAcceptedDatasource')->willReturn($dataSource);

        return new BuildAfter($dataGrid);
    }

    protected function setListener()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->listener = new ProductsLimitedByStatusesDatagridEventListener($requestStack, $this->eventDispatcher);
    }
}
