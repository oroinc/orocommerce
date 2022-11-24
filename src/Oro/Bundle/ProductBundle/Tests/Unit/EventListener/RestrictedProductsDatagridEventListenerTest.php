<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\EventListener\RestrictedProductsDatagridEventListener;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RestrictedProductsDatagridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $qb;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var RestrictedProductsDatagridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->productManager = $this->createMock(ProductManager::class);

        $this->listener = new RestrictedProductsDatagridEventListener($this->requestStack, $this->productManager);
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     */
    public function testOnBuildAfter(?Request $request, array $expectedParamsResult)
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $event = $this->createEvent();
        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($this->qb, $expectedParamsResult);
        $this->listener->onBuildAfter($event);
    }

    public function onBuildAfterDataProvider(): array
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

    private function createEvent(): BuildAfter
    {
        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->qb);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        return new BuildAfter($dataGrid);
    }
}
