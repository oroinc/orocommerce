<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\PricingBundle\Cache\RuleCache;

class RuleCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var RuleCache
     */
    protected $ruleCache;

    protected function setUp()
    {
        $this->cache = $this->getMock(Cache::class);
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->ruleCache = new RuleCache($this->cache, $this->registry);
    }

    public function testFetch()
    {
        $id = 'test';
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $this->cache->expects($this->once())
            ->method('contains')
            ->with($id)
            ->willReturn(true);
        $storedData = [
            RuleCache::DQL_PARTS_KEY => [
                'select' => new Expr\Select(['test.id']),
                'from' => new Expr\From('TestEntity', 'test'),
                'where' => new Expr\Andx(['test.id = :testParam']),
            ],
            RuleCache::PARAMETERS_KEY => ['testParam' => 1],
        ];
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn($storedData);

        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $this->assertEquals($qb, $this->ruleCache->fetch($id));
        $this->assertEquals('SELECT test.id FROM TestEntity test WHERE test.id = :testParam', $qb->getDQL());
        $this->assertEquals(new ArrayCollection([new Parameter('testParam', 1)]), $qb->getParameters());
    }

    public function testFetchFalse()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('contains')
            ->with($id)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testFetchIncorrectData()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('contains')
            ->with($id)
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(['unknown' => 'data']);
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testContains()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('contains')
            ->with($id)
            ->willReturn(true);
        $this->assertTrue($this->ruleCache->contains($id));
    }

    public function testSave()
    {
        $id = 'test';
        $data = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dqlParts = [
            'select' => new Expr\Select(['test.id']),
            'from' => new Expr\From('TestEntity', 'test')
        ];
        $parameters = new ArrayCollection([new Parameter('testParam', 1)]);
        $data->expects($this->once())
            ->method('getDQLParts')
            ->willReturn($dqlParts);
        $data->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $expectedData = [
            RuleCache::DQL_PARTS_KEY => $dqlParts,
            RuleCache::PARAMETERS_KEY => $parameters
        ];
        $lifeTime = 0;
        $this->cache->expects($this->once())
            ->method('save')
            ->with($id, $expectedData, $lifeTime)
            ->willReturn(true);
        $this->assertTrue($this->ruleCache->save($id, $data, $lifeTime));
    }

    public function testSaveFalse()
    {
        $id = 'test';
        $data = null;
        $lifeTime = 0;
        $this->cache->expects($this->never())
            ->method('save');
        $this->assertFalse($this->ruleCache->save($id, $data, $lifeTime));
    }

    public function testDelete()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);
        $this->assertTrue($this->ruleCache->delete($id));
    }

    public function testGetStats()
    {
        $stats = [];
        $this->cache->expects($this->once())
            ->method('getStats')
            ->willReturn($stats);
        $this->assertSame($stats, $this->ruleCache->getStats());
    }
}
