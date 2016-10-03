<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\EventListener\RestrictedProductsDatagridEventListener;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;

class RestrictedProductsDatagridEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ProductManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $productManager;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
    protected $qb;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
    protected $requestStack;

    /** @var RestrictedProductsDatagridEventListener */
    protected $listener;

    protected function setUp()
    {
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productManager = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Manager\ProductManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictedProductsDatagridEventListener($this->requestStack, $this->productManager);
    }

    /**
     * @dataProvider testOnBuildAfterDataProvider
     * @param Request|null $request
     * @param array $expectedParamsResult
     */
    public function testOnBuildAfter($request, array $expectedParamsResult)
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $event = $this->createEvent();
        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with(
                $this->qb,
                $expectedParamsResult
            );
        $this->listener->onBuildAfter($event);
    }

    /**
     * @return array
     */
    public function testOnBuildAfterDataProvider()
    {
        $emptyParamsRequest = new Request();
        $emptyParamsRequest->request->set(ProductSelectType::DATA_PARAMETERS, []);
        $params = ['some' => 'param'];
        $notEmptyParamsRequest = new Request();
        $notEmptyParamsRequest->request->set(ProductSelectType::DATA_PARAMETERS, $params);

        return
            [
                'withoutRequest' => ['request' => null, 'expectedParamsResult' => []],
                'withoutParams' => ['request' => new Request(), 'expectedParamsResult' => []],
                'withEmptyParams' => ['request' => $emptyParamsRequest, 'expectedParamsResult' => []],
                'withNotEmptyParams' => ['request' => $notEmptyParamsRequest, 'expectedParamsResult' => $params],
            ];
    }

    /**
     * @return BuildAfter
     */
    protected function createEvent()
    {
        /** @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->once())->method('getQueryBuilder')->willReturn($this->qb);

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->once())->method('getDatasource')->willReturn($dataSource);

        return new BuildAfter($dataGrid);
    }
}
