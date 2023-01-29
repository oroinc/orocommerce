<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceRuleLexemeEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;

class PriceRuleLexemeEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceRuleLexemeRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var PriceRuleLexemeEntityListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PriceRuleLexemeRepository::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($this->repository);

        $this->listener = new PriceRuleLexemeEntityListener();
    }

    public function testPostPersist()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

        $args = $this->getEventArgs();

        $this->listener->postPersist($args->getEntity(), $args);
    }

    public function testPostUpdate()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

        $args = $this->getEventArgs();

        $this->listener->postUpdate($args->getEntity(), $args);
    }

    public function testPostRemove()
    {
        $this->repository->expects($this->once())
            ->method('invalidateCache');

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
