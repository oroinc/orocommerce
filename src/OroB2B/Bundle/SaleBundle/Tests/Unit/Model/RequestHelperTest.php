<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;

use OroB2B\Bundle\SaleBundle\Model\RequestHelper;

class RequestHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder */
    protected $qb;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery */
    protected $query;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrmExpressionBuilder */
    protected $expressionBuilder;

    /** @var RequestHelper */
    protected $helper;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();


        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')->disableOriginalConstructor()
            ->setMethods(['getResult'])->getMockForAbstractClass();

        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->expressionBuilder = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->helper = new RequestHelper($this->registry, 'OroB2BSaleBundle:Quote', 'OroB2BRFPBundle:Request');
    }

    protected function tearDown()
    {
        unset(
            $this->repository,
            $this->manager,
            $this->query,
            $this->qb,
            $this->expressionBuilder,
            $this->registry,
            $this->helper
        );
    }

    public function testGetRequestsWoQuote()
    {
        $result = [new \stdClass()];

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->expressionBuilder->expects($this->once())
            ->method('not')
            ->withAnyParameters()
            ->will($this->returnSelf());

        $this->qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
        $this->qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $this->qb->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $this->qb->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $this->repository->expects($this->exactly(2))->method('createQueryBuilder')
            ->withAnyParameters()->willReturn($this->qb);
        $this->manager->expects($this->exactly(2))
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->manager)
        ;

        $this->assertEquals($result, $this->helper->getRequestsWoQuote());
    }
}
