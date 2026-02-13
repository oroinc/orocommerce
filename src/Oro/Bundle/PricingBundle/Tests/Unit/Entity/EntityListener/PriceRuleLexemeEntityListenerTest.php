<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceRuleLexemeEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class PriceRuleLexemeEntityListenerTest extends TestCase
{
    /** @var PriceRuleLexemeRepository|MockObject */
    protected $repository;

    /** @var EntityManager|MockObject */
    protected $entityManager;

    /** @var CacheItemPoolInterface|MockObject */
    protected $cache;

    /** @var PriceRuleLexemeEntityListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PriceRuleLexemeRepository::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($this->repository);

        $this->listener = new PriceRuleLexemeEntityListener();
        $this->listener->setCache($this->cache);
    }

    public function testPostPersist()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

        $this->cache->expects($this->once())
            ->method('clear');

        $args = $this->getEventArgs();

        $this->listener->postPersist($args->getEntity(), $args);
    }

    public function testPostUpdate()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

        $this->cache->expects($this->once())
            ->method('clear');

        $args = $this->getEventArgs();

        $this->listener->postUpdate($args->getEntity(), $args);
    }

    public function testPostRemove()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

        $this->cache->expects($this->once())
            ->method('clear');

        $args = $this->getEventArgs();

        $this->listener->postRemove($args->getEntity(), $args);
    }

    /**
     * @return LifecycleEventArgs
     */
    protected function getEventArgs()
    {
        return new LifecycleEventArgs(new PriceRuleLexeme(), $this->entityManager);
    }
}
