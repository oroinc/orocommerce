<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountUserRoleDatagridListener;

class DatagridListenerFrontendTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserRoleDatagridListener
     */
    protected $listener;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var AclHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->aclHelper = $this->createAclHelperMock();
        $this->listener = new AccountUserRoleDatagridListener($this->aclHelper);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->aclHelper);
    }

    public function testOnBuildAfter()
    {
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $criteria = new Criteria();
        $this->aclHelper->expects($this->once())
            ->method('applyAclToCriteria')
            ->with(
                AccountUserRole::class,
                $criteria,
                'VIEW',
                ['account' => '.account', 'organization' => '.organization']
            )
            ->willReturn($this->queryBuilder);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAclHelperMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
