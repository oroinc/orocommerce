<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceAttributeProductPriceEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class PriceAttributeProductPriceEntityListenerTest extends BaseProductPriceEntityListenerTest
{
    /**
     * @var PriceAttributeProductPriceEntityListener
     */
    protected $listener;

    /**
     * @return string
     */
    protected function getEntityClassName()
    {
        return PriceAttributeProductPrice::class;
    }

    protected function getListener()
    {
        return new PriceAttributeProductPriceEntityListener(
            $this->priceRuleLexemeTriggerHandler,
            $this->fieldsProvider,
            $this->registry
        );
    }

    public function testPostPersist()
    {
        $this->assertFeatureChecker('feature1');

        parent::testPostPersist();
    }

    public function testPostPersistFeatureDisabled()
    {
        list($baseProductPrice) = $this->getEntities();
        $this->priceRuleLexemeTriggerHandler->expects($this->never())->method('findEntityLexemes');

        $this->assertFeatureChecker('feature1', false)
            ->postPersist($baseProductPrice);
    }

    public function testPreUpdate()
    {
        list($baseProductPrice) = $this->getEntities();
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([42]);

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturn(true);

        $event->expects($this->any())
            ->method('getOldValue')
            ->willReturn(42);

        $event->expects($this->any())
            ->method('getNewValue')
            ->willReturn(42);

        $this->assertFeatureChecker('feature1')
            ->preUpdate($baseProductPrice, $event);
    }

    public function testPreUpdateFeatureEnabled()
    {
        $this->assertFeatureChecker('feature1');

        parent::testPreUpdate();
    }

    public function testPreUpdateFeatureDisabled()
    {
        list($baseProductPrice) = $this->getEntities();
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->never())->method('getEntityChangeSet');

        $this->assertFeatureChecker('feature1', false)
            ->preUpdate($baseProductPrice, $event);
    }

    public function testPreRemove()
    {
        $this->assertFeatureChecker('feature1');

        parent::testPreRemove();
    }

    public function testPreRemoveFeatureDisabled()
    {
        list($baseProductPrice) = $this->getEntities();
        $this->priceRuleLexemeTriggerHandler->expects($this->never())
            ->method('findEntityLexemes');

        $this->assertFeatureChecker('feature1', false)
            ->preRemove($baseProductPrice);
    }
}
