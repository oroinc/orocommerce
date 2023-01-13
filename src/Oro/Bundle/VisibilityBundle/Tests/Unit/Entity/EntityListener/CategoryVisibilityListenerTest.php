<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveCategoryVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\CategoryVisibilityListener;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryVisibilityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private CategoryVisibilityListener $visibilityListener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->visibilityListener = new CategoryVisibilityListener($this->messageProducer);
    }

    public function testPostPersist(): void
    {
        $entityId = 123;
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => $entityId]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolveCategoryVisibilityTopic::getName(),
                ['entity_class_name' => CategoryVisibility::class, 'id' => $entityId]
            );

        $this->visibilityListener->postPersist($entity);
    }

    public function testPreUpdate(): void
    {
        $entityId = 123;
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => $entityId]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolveCategoryVisibilityTopic::getName(),
                ['entity_class_name' => CategoryVisibility::class, 'id' => $entityId]
            );

        $this->visibilityListener->preUpdate($entity);
    }

    public function testPreRemove(): void
    {
        $entityId = 123;
        $targetEntityId = 234;
        $scopeId = 345;
        /** @var Category $targetEntity */
        $targetEntity = $this->getEntity(Category::class, ['id' => $targetEntityId]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => $entityId]);
        $entity->setTargetEntity($targetEntity);
        $entity->setScope($scope);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolveCategoryVisibilityTopic::getName(),
                [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => Category::class,
                    'target_id' => $targetEntityId,
                    'scope_id' => $scopeId,
                ]
            );

        $this->visibilityListener->preRemove($entity);
    }

    public function testPostPersistWhenDisabled(): void
    {
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => 123]);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->visibilityListener->setEnabled(false);
        $this->visibilityListener->postPersist($entity);
    }

    public function testPreUpdateWhenDisabled(): void
    {
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => 123]);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->visibilityListener->setEnabled(false);
        $this->visibilityListener->preUpdate($entity);
    }

    public function testPreRemoveWhenDisabled(): void
    {
        /** @var Category $targetEntity */
        $targetEntity = $this->getEntity(Category::class, ['id' => 234]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 345]);
        /** @var VisibilityInterface $entity */
        $entity = $this->getEntity(CategoryVisibility::class, ['id' => 123]);
        $entity->setTargetEntity($targetEntity);
        $entity->setScope($scope);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->visibilityListener->setEnabled(false);
        $this->visibilityListener->preRemove($entity);
    }
}
