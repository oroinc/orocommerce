<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction\AbstractCouponMassActionHandler;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Exception\UnexpectedTypeException;

class AbstractCouponMassActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var MassActionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $handler;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->createHandler();
    }

    protected function createHandler()
    {
        $this->handler = $this->getMockBuilder(AbstractCouponMassActionHandler::class)
            ->setConstructorArgs([$this->doctrineHelper, $this->aclHelper])
            ->getMockForAbstractClass();
    }

    public function testExecuteForNonOrmDataSources()
    {
        $args = $this->createMock(MassActionHandlerArgs::class);
        $datagrid = $this->createMock(DatagridInterface::class);

        $datasource = $this->createMock(DatasourceInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $args->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->expectException(UnexpectedTypeException::class);
        $this->handler->handle($args);
    }

    /**
     * @dataProvider iterateDataProvider
     */
    public function testHandle(array $iterateData, array $coupons)
    {
        $args = $this->createMock(MassActionHandlerArgs::class);
        $datagrid = $this->createMock(DatagridInterface::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('isDatasourceSkipAclApply')
            ->willReturn(false);
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $args->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder, 'EDIT');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('iterate')
            ->with(null, AbstractQuery::HYDRATE_SCALAR)
            ->willReturn($iterateData);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Coupon::class)
            ->willReturn($em);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->any())
            ->method('find')
            ->willReturnOnConsecutiveCalls(...$coupons);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repo);

        $em->expects($this->atLeastOnce())
            ->method('flush');
        $em->expects($this->atLeastOnce())
            ->method('clear');

        $this->assertExecuteCalled($coupons, $args);
        $response = $this->assertGetResponseCalled(count($iterateData));

        $this->assertEquals($response, $this->handler->handle($args));
    }

    public function iterateDataProvider(): array
    {
        return [
            [
                [[['id' => 1]], [['id' => 3]]],
                [$this->getCoupon(1), $this->getCoupon(3)]
            ]
        ];
    }

    protected function assertExecuteCalled(
        array $coupons,
        MassActionHandlerArgs|\PHPUnit\Framework\MockObject\MockObject $args
    ): void {
        $this->assertNotEmpty($coupons);
        $this->handler->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isInstanceOf(Coupon::class), $args);
    }

    protected function assertGetResponseCalled(int $entitiesCount): MassActionResponse
    {
        $response = new MassActionResponse(true, $entitiesCount . ' processed');
        $this->handler->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        return $response;
    }

    private function getCoupon($id): Coupon
    {
        $coupon = $this->createMock(Coupon::class);
        $coupon->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $coupon;
    }
}
