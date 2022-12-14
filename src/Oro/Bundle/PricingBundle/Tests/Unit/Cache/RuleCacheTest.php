<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class RuleCacheTest extends \PHPUnit\Framework\TestCase
{
    private const DQL_PARTS_KEY = 'dql_parts';
    private const PARAMETERS_KEY = 'parameters';
    private const HASH = 'hash';

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var RuleCache */
    private $ruleCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->ruleCache = new RuleCache($this->cache, $this->registry, $this->crypter);
    }

    public function testFetchCorrectHash()
    {
        $id = 'test';
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
            self::DQL_PARTS_KEY => $parts,
            self::PARAMETERS_KEY => ['testParam' => 1],
            self::HASH => md5('encrypted')
        ];
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($id)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
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
            self::DQL_PARTS_KEY => $parts,
            self::PARAMETERS_KEY => ['testParam' => 1],
            self::HASH => md5('incorrect')
        ];

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($id)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($storedData);

        $this->registry->expects($this->never())
            ->method('getManager');
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testFetchFalse()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($id)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testFetchIncorrectData()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($id)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn(['unknown' => 'data']);
        $this->assertFalse($this->ruleCache->fetch($id));
    }

    public function testContains()
    {
        $id = 'test';
        $this->cache->expects($this->once())
            ->method('hasItem')
            ->with($id)
            ->willReturn(true);
        $this->assertTrue($this->ruleCache->contains($id));
    }

    public function testSave()
    {
        $id = 'test';
        $data = $this->createMock(QueryBuilder::class);
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
            self::DQL_PARTS_KEY => $dqlParts,
            self::PARAMETERS_KEY => $parameters,
            self::HASH => md5('encrypted')
        ];
        $lifeTime = 0;

        $this->crypter->expects($this->once())
            ->method('encryptData')
            ->with(serialize($dqlParts))
            ->willReturn('encrypted');

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($id)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($expectedData)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with($lifeTime)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem)
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
            ->method('deleteItem')
            ->with($id)
            ->willReturn(true);
        $this->assertTrue($this->ruleCache->delete($id));
    }
}
