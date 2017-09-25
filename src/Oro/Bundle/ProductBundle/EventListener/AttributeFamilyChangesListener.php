<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Watch changes of product attribute families and trigger update of search index
 */
class AttributeFamilyChangesListener
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var array */
    protected $changedAttributeFamilies = [];

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $registry,
        EventDispatcherInterface $dispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->requestStack->getMasterRequest()) {
            return;
        }

        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        $this->changedAttributeFamilies = array_merge(
            $this->analizeScheduledEntity($uow->getScheduledEntityInsertions(), true),
            $this->analizeScheduledEntity($uow->getScheduledEntityDeletions(), false)
        );
    }

    /**
     * Trigger update search index only for product with changed attribute families
     */
    public function postFlush()
    {
        if (!$this->changedAttributeFamilies) {
            return;
        }

        $repository = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

        $productIds = $repository->getProductIdsByAttributeFamilies($this->changedAttributeFamilies);
        if (!$productIds) {
            return;
        }

        $this->changedAttributeFamilies = [];

        $this->dispatcher->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [], $productIds)
        );
    }

    /**
     * @param array $scheduledEntities
     * @param bool $shouldHasRelation
     * @return array
     */
    private function analizeScheduledEntity(array $scheduledEntities, $shouldHasRelation)
    {
        $families = [];

        foreach ($scheduledEntities as $entity) {
            if (!$entity instanceof AttributeGroupRelation) {
                continue;
            }

            $attributeFamily = $entity->getAttributeGroup()->getAttributeFamily();
            if (!$attributeFamily) {
                continue;
            }

            if ($this->hasRelation($attributeFamily, $entity->getEntityConfigFieldId()) === $shouldHasRelation) {
                $families[] = $attributeFamily;
            }
        }

        return $families;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param int $attributeId
     * @return bool
     */
    private function hasRelation(AttributeFamily $attributeFamily, $attributeId)
    {
        foreach ($attributeFamily->getAttributeGroups() as $attributeGroup) {
            foreach ($attributeGroup->getAttributeRelations() as $attributeGroupRelation) {
                if ($attributeGroupRelation->getEntityConfigFieldId() === $attributeId) {
                    return true;
                }
            }
        }

        return false;
    }
}
