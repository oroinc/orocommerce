<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\PricingBundle\Entity\EntityListener\AbstractRuleEntityListener;
use Oro\Bundle\PricingBundle\Entity\EntityListener\CategoryEntityListener;

class CategoryEntityListenerTest extends AbstractRuleEntityListenerTest
{
    /**
     * {@inheritDoc}
     */
    protected function getEntityClassName(): string
    {
        return Category::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener(): AbstractRuleEntityListener
    {
        return new CategoryEntityListener(
            $this->priceRuleLexemeTriggerHandler,
            $this->fieldsProvider,
            $this->registry
        );
    }

    public function preUpdateData(): array
    {
        return [
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                ['key2'],
                1,
            ],
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                ['key3'],
                0,
            ],
        ];
    }

    /**
     * @dataProvider preUpdateData
     */
    public function testPreUpdate(array $changeSet, array $expectedFields, int $numberOfCalls)
    {
        $category = $this->getEntity($this->getEntityClassName(), ['id' => 1]);

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with(Category::FIELD_PARENT_CATEGORY)
            ->willReturn(false);

        $event->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);

        $this->assertRecalculateByEntityFieldsUpdate(1, $numberOfCalls, $expectedFields, $changeSet);

        $this->listener->preUpdate($category, $event);
    }

    public function testPreUpdateFeatureEnabled()
    {
        $category = $this->getEntity(Category::class, ['id' => 42]);

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with(Category::FIELD_PARENT_CATEGORY)
            ->willReturn(true);

        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->assertFeatureChecker('feature1')
            ->preUpdate($category, $event);
    }

    public function testPreUpdateFeatureDisabled()
    {
        $category = $this->getEntity(Category::class, ['id' => 42]);

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->never())->method('hasChangedField');

        $this->assertFeatureChecker('feature1', false)
            ->preUpdate($category, $event);
    }

    public function testPreRemoveFeatureEnabled()
    {
        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->assertFeatureChecker('feature1')->preRemove();
    }

    public function testPreRemoveFeatureDisabled()
    {
        $this->priceRuleLexemeTriggerHandler->expects($this->never())
            ->method('findEntityLexemes');

        $this->assertFeatureChecker('feature1', false)->preRemove();
    }

    public function testOnProductsChangeRelationFeatureEnabled()
    {
        $event = $this->createMock(ProductsChangeRelationEvent::class);
        $event->expects($this->once())
            ->method('getProducts')
            ->willReturn([]);

        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->assertFeatureChecker('feature1')
            ->onProductsChangeRelation($event);
    }

    public function testOnProductsChangeRelationFeatureDisabled()
    {
        $event = $this->createMock(ProductsChangeRelationEvent::class);
        $event->expects($this->never())->method('getProducts');

        $this->assertFeatureChecker('feature1', false)
            ->onProductsChangeRelation($event);
    }
}
