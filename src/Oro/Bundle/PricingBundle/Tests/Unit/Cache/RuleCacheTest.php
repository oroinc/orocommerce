<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class RuleCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $crypter;

    /**
     * @var RuleCache
     */
    protected $ruleCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->ruleCache = new RuleCache($this->cache, $this->registry, $this->crypter);
    }

    public function testFetchCorrectHash()
    {
        $id = 'test';
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);

        $parts = [
            'select' => new Expr\Select(['test.id']),
            'from' => new Expr\From('TestEntity', 'test'),
            'where' => new Expr\Andx(['test.id = :testParam']),
        ];

        $this->crypter->expects($this->once())
            ->method('encryptData')
            ->with(serialize($parts))
            ->willReturn('encrypted');

        $storedData = [
            RuleCache::DQL_PARTS_KEY => $parts,
            RuleCache::PARAMETERS_KEY => ['testParam' => 1],
            RuleCache::HASH => md5('encrypted')
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

    public function testFetchIncorrectHash()
    {
        $id = 'test';

        $parts = [
            'select' => new Expr\Select(['test.id']),
            'from' => new Expr\From('TestEntity', 'test'),
            'where' => new Expr\Andx(['test.id = :testParam']),
        ];

        $this->crypter->expects($this->once())
            ->method('encryptData')
            ->with(serialize($parts))
            ->willReturn('encrypted');

        $storedData = [
            RuleCache::DQL_PARTS_KEY => $parts,
            RuleCache::PARAMETERS_KEY => ['testParam' => 1],
            RuleCache::HASH => md5('incorrect')
        ];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn($storedData);

        $this->registry->expects($this->never())
            ->method('getManager');
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testFetchFalse()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testFetchIncorrectData()
    {
        $id = 'test';
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
        $data->expects($this->any())
            ->method('getDQLParts')
            ->willReturn($dqlParts);
        $data->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $expectedData = [
            RuleCache::DQL_PARTS_KEY => $dqlParts,
            RuleCache::PARAMETERS_KEY => $parameters,
            RuleCache::HASH => md5('encrypted')
        ];
        $lifeTime = 0;

        $this->crypter->expects($this->once())
            ->method('encryptData')
            ->with(serialize($dqlParts))
            ->willReturn('encrypted');

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
