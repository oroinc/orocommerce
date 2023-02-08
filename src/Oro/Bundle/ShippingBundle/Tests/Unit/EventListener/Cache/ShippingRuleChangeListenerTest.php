<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingRuleChangeListener;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingRuleChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ShippingPriceCache */
    private $priceCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LifecycleEventArgs */
    private $args;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    private $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var ShippingRuleChangeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->priceCache = $this->createMock(ShippingPriceCache::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->args = $this->createMock(LifecycleEventArgs::class);

        $this->listener = new ShippingRuleChangeListener($this->priceCache);
    }

    public function testMethodsWithShippingRuleInOneRequest()
    {
        $entity = $this->getEntity(Rule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 1, 1);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithShippingRuleInDifferentRequests()
    {
        $entity = $this->getEntity(Rule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 3, 3);
        $listener1 = new ShippingRuleChangeListener($this->priceCache);
        $listener2 = new ShippingRuleChangeListener($this->priceCache);
        $listener3 = new ShippingRuleChangeListener($this->priceCache);

        $listener1->postPersist($entity, $this->args);
        $listener2->postUpdate($entity, $this->args);
        $listener3->postRemove($entity, $this->args);
    }

    public function testMethodsWithNotShippingRule()
    {
        $entity = $this->getEntity(Rule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 3, 0, false);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithNotRuleInOneRequest()
    {
        $entity = $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 0, 1);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithNotRuleInDifferentRequests()
    {
        $entity = $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 0, 3);
        $listener1 = new ShippingRuleChangeListener($this->priceCache);
        $listener2 = new ShippingRuleChangeListener($this->priceCache);
        $listener3 = new ShippingRuleChangeListener($this->priceCache);

        $listener1->postPersist($entity, $this->args);
        $listener2->postUpdate($entity, $this->args);
        $listener3->postRemove($entity, $this->args);
    }

    private function mockExpectedQuantity(
        RuleInterface|ShippingMethodsConfigsRule $entity,
        int $quantity,
        int $clearCacheCnt,
        bool $repositoryResult = true
    ): void {
        $this->repository->expects(self::exactly($quantity))
            ->method('findOneBy')
            ->with(['rule' => $entity])
            ->willReturn($repositoryResult ? new ShippingMethodsConfigsRule() : null);
        $this->em->expects(self::exactly($quantity))
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);
        $this->args->expects(self::exactly($quantity))
            ->method('getObjectManager')
            ->willReturn($this->em);
        $this->priceCache
            ->expects(self::exactly($clearCacheCnt))
            ->method('deleteAllPrices');
    }

    private function triggerAllMethods(RuleInterface|ShippingMethodsConfigsRule $entity): void
    {
        $this->listener->postPersist($entity, $this->args);
        $this->listener->postUpdate($entity, $this->args);
        $this->listener->postRemove($entity, $this->args);
    }
}
