<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionDatagridParametersListener;

class ProductCollectionDatagridParametersListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductCollectionDatagridParametersListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductCollectionDatagridParametersListener();
    }

    public function testOnBuildAfterWhenWrongDatasource()
    {
        $datasource = $this->createMock(DatasourceInterface::class);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->onBuildAfter(new BuildAfter($datagrid));
    }

    public function testOnBuildAfterWhenNoSuchParameter()
    {
        $parameterName = 'nonexistentParameter';
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getParameter')
            ->with($parameterName)
            ->willReturn(null);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->setParameterName($parameterName);
        $this->listener->onBuildAfter(new BuildAfter($datagrid));
    }

    /**
     * @dataProvider onBuildAfterProvider
     * @param string $parameterName
     * @param mixed $parameterValue
     * @param mixed $expectedParameterValue
     */
    public function testOnBuildAfter($parameterName, $parameterValue, $expectedParameterValue)
    {
        $parameter = new Parameter($parameterName, $parameterValue);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getParameter')
            ->with($parameterName)
            ->willReturn($parameter);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->setParameterName($parameterName);
        $this->listener->onBuildAfter(new BuildAfter($datagrid));

        $this->assertEquals($expectedParameterValue, $parameter->getValue());
    }

    public function onBuildAfterProvider(): array
    {
        return [
            'when parameter value is array' => [
                'parameterName' => 'someParameterKey',
                'parameterValue' => ['someValue', 'someAnotherValue'],
                'expectedParameterValue' => ['someValue', 'someAnotherValue'],
            ],
            'when parameter value is not array' => [
                'parameterName' => 'someParameterKey',
                'parameterValue' => '1,2,3',
                'expectedParameterValue' => [1, 2, 3],
            ],
        ];
    }
}
