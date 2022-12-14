<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\AbstractRuleEntityListener;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductEntityListener;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductEntityListenerTest extends AbstractRuleEntityListenerTest
{
    /** @var ProductEntityListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function getEntityClassName(): string
    {
        return Product::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener(): AbstractRuleEntityListener
    {
        return new ProductEntityListener(
            $this->priceRuleLexemeTriggerHandler,
            $this->fieldsProvider,
            $this->registry
        );
    }

    public function testPostPersistFeatureEnabled()
    {
        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->assertFeatureChecker('feature1')
            ->postPersist($this->getProduct());
    }

    public function testPostPersistFeatureDisabled()
    {
        $this->priceRuleLexemeTriggerHandler->expects($this->never())->method('findEntityLexemes');

        $this->assertFeatureChecker('feature1', false)
            ->postPersist($this->getProduct());
    }

    public function testPreUpdateFeatureEnabled()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([42]);

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturn(true);

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn([42]);

        $this->assertFeatureChecker('feature1')
            ->preUpdate($this->getProduct(), $event);
    }

    public function testPreUpdateFeatureDisabled()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->never())
            ->method('getEntityChangeSet');

        $this->assertFeatureChecker('feature1', false)
            ->preUpdate($this->getProduct(), $event);
    }

    private function getProduct(): Product
    {
        return $this->getEntity(Product::class, ['id' => 42]);
    }
}
