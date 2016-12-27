<?php

namespace Oro\Bundle\ShippingBundle\Bundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingRuleChangeListener;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingRuleChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShippingPriceCache
     */
    protected $priceCache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs
     */
    protected $args;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    protected $repository;

    /**
     * @var ShippingRuleChangeListener
     */
    protected $listener;

    /**
     * @var Rule
     */
    protected $rule;

    public function setUp()
    {
        $this->priceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(static::once())
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);
        $this->args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->args->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $this->rule = $this->getEntity(Rule::class, ['id' => 1]);
        $this->listener = new ShippingRuleChangeListener($this->priceCache);
    }

    public function testShippingRulePostUpdate()
    {
        $this->repository->expects(static::once())
            ->method('findOneBy')
            ->with(['rule' => $this->rule])
            ->willReturn(new ShippingMethodsConfigsRule());
        $this->priceCache
            ->expects(static::once())
            ->method('deleteAllPrices');
        $this->listener->postUpdate($this->rule, $this->args);
    }

    public function testNotShippingRulePostUpdate()
    {
        $this->repository->expects(static::once())
            ->method('findOneBy')
            ->with(['rule' => $this->rule])
            ->willReturn(null);
        $this->priceCache
            ->expects(static::never())
            ->method('deleteAllPrices');
        $this->listener->postUpdate($this->rule, $this->args);
    }
}
