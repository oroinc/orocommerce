<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingRuleChangeListener;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingRuleChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ShippingPriceCache
     */
    protected $priceCache;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LifecycleEventArgs
     */
    protected $args;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    protected $em;

    /**
     * @var ShippingRuleChangeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->priceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new ShippingRuleChangeListener($this->priceCache);
    }

    public function testMethodsWithShippingRuleInOneRequest()
    {
        /** @var RuleInterface $entity */
        $entity = $this->getEntity(Rule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 1, 1);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithShippingRuleInDifferentRequests()
    {
        /** @var RuleInterface $entity */
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
        /** @var RuleInterface $entity */
        $entity = $this->getEntity(Rule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 3, 0, false);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithNotRuleInOneRequest()
    {
        /** @var ShippingMethodsConfigsRule $entity */
        $entity = $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 0, 1);
        $this->triggerAllMethods($entity);
    }

    public function testMethodsWithNotRuleInDifferentRequests()
    {
        /** @var ShippingMethodsConfigsRule $entity */
        $entity = $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1]);
        $this->mockExpectedQuantity($entity, 0, 3);
        $listener1 = new ShippingRuleChangeListener($this->priceCache);
        $listener2 = new ShippingRuleChangeListener($this->priceCache);
        $listener3 = new ShippingRuleChangeListener($this->priceCache);

        $listener1->postPersist($entity, $this->args);
        $listener2->postUpdate($entity, $this->args);
        $listener3->postRemove($entity, $this->args);
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule $entity
     * @param integer $quantity
     * @param integer $clearCacheCnt
     * @param boolean $repositoryResult
     */
    protected function mockExpectedQuantity($entity, $quantity, $clearCacheCnt, $repositoryResult = true)
    {
        $this->repository->expects(static::exactly($quantity))
            ->method('findOneBy')
            ->with(['rule' => $entity])
            ->willReturn($repositoryResult ? new ShippingMethodsConfigsRule() : null);
        $this->em->expects(static::exactly($quantity))
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);
        $this->args->expects(static::exactly($quantity))
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->priceCache
            ->expects(static::exactly($clearCacheCnt))
            ->method('deleteAllPrices');
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule $entity
     */
    protected function triggerAllMethods($entity)
    {
        $this->listener->postPersist($entity, $this->args);
        $this->listener->postUpdate($entity, $this->args);
        $this->listener->postRemove($entity, $this->args);
    }
}
