<?php

namespace Oro\Bundle\CouponBundle\Tests\Functional\Controller;

use Oro\Bundle\CouponBundle\Datagrid\Extension\MassAction\CouponEditMassActionHandler;
use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Form\Type\BaseCouponType;
use Oro\Bundle\CouponBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponEditMassActionHandlerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadCouponData::class]);
    }

    public function testHandle()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $em = $doctrineHelper->getEntityManagerForClass(Coupon::class);
        /** @var CouponEditMassActionHandler $handler */
        $handler = $this->getContainer()->get('oro_datagrid.extension.coupon.mass_action.handler.edit');

        $massAction = $this->createMock(MassActionInterface::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $config = $this->createMock(DatagridConfiguration::class);

        /** @var BusinessUnit $owner */
        $owner = $doctrineHelper->getEntityRepositoryForClass(BusinessUnit::class)->findOneBy(['name' => 'Main']);

        $qb = $em->createQueryBuilder()
            ->select('c.id')
            ->from(Coupon::class, 'c');
        $datagrid->expects($this->once())->method('getDatasource')->willReturn($datasource);
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);
        $datasource->expects($this->once())->method('getQueryBuilder')->willReturn($qb);
        $config->expects($this->once())->method('isDatasourceSkipAclApply')->willReturn(false);

        $handler->handle(new MassActionHandlerArgs($massAction, $datagrid, new IterableResult($qb), [
            BaseCouponType::NAME => ['owner' => $owner->getId(), 'usesPerCoupon' => 111, 'usesPerUser' => ''],
        ]));

        /** @var Coupon $coupon1 */
        $coupon1 = $em->getRepository(Coupon::class)->findOneBy(['code' => 'test123']);
        /** @var Coupon $coupon2 */
        $coupon2 = $em->getRepository(Coupon::class)->findOneBy(['code' => 'test456']);
        $this->assertSame(111, $coupon1->getUsesPerCoupon());
        $this->assertSame(111, $coupon2->getUsesPerCoupon());
        $this->assertNull($coupon1->getUsesPerUser());
        $this->assertNull($coupon2->getUsesPerUser());
    }
}
