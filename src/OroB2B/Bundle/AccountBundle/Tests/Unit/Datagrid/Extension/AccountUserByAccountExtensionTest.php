<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

use OroB2B\Bundle\AccountBundle\Datagrid\Extension\AccountUserByAccountExtension;

class AccountUserByAccountExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserByAccountExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AccountUserByAccountExtension();
        $this->extension->setRequest($this->request);
    }

    /**
     * @dataProvider isApplicableDataProvider
     * @param string $name
     * @param int|null $accountId
     * @param bool $expected
     */
    public function testIsApplicable($name, $accountId, $expected)
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(AccountUserByAccountExtension::ACCOUNT_KEY)
            ->will($this->returnValue($accountId));

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $this->assertEquals($expected, $this->extension->isApplicable($config));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            ['test', null, false],
            [AccountUserByAccountExtension::SUPPORTED_GRID, null, false],
            [AccountUserByAccountExtension::SUPPORTED_GRID, '', false],
            [AccountUserByAccountExtension::SUPPORTED_GRID, 1, true],
        ];
    }

    public function testVisitDatasource()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(AccountUserByAccountExtension::SUPPORTED_GRID));
        $this->request->expects($this->any())
            ->method('get')
            ->with(AccountUserByAccountExtension::ACCOUNT_KEY)
            ->will($this->returnValue(1));

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('account', 1)
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->will($this->returnValue(['au']));
        $qb->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expr));

        /** @var \PHPUnit_Framework_MockObject_MockObject|OrmDatasource $datasource */
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $this->extension->visitDatasource($config, $datasource);
    }
}
