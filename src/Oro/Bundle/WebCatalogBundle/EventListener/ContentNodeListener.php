<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Resolve content node slugs on entity create, remove or fields update
 */
class ContentNodeListener
{
    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    protected $messageFactory;

    public function __construct(
        ContentNodeMaterializedPathModifier $modifier,
        ExtraActionEntityStorageInterface $storage,
        MessageProducerInterface $messageProducer,
        ResolveNodeSlugsMessageFactory $messageFactory
    ) {
        $this->modifier = $modifier;
        $this->storage = $storage;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
    }

    public function postPersist(ContentNode $contentNode)
    {
        $contentNode = $this->modifier->calculateMaterializedPath($contentNode);
        $this->storage->scheduleForExtraInsert($contentNode);
    }

    public function preUpdate(ContentNode $contentNode, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();

        if (!empty($changeSet[ContentNode::FIELD_PARENT_NODE])) {
            $this->modifier->calculateMaterializedPath($contentNode);
            $childNodes = $this->modifier->calculateChildrenMaterializedPath($contentNode);

            $this->storage->scheduleForExtraInsert($contentNode);
            foreach ($childNodes as $childNode) {
                $this->storage->scheduleForExtraInsert($childNode);
            }
        }
    }

    public function postRemove(ContentNode $contentNode, LifecycleEventArgs $args)
    {
        if ($contentNode->getParentNode() && $contentNode->getParentNode()->getId()) {
            if (!$args->getEntityManager()->getUnitOfWork()->isScheduledForDelete($contentNode->getParentNode())) {
                $this->scheduleContentNodeRecalculation($contentNode->getParentNode());
            }
        } else {
            $this->messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, [
                'webCatalogId' => $contentNode->getWebCatalog()->getId()
            ]);
        }
    }

    /**
     * Form after flush is used to catch all content node fields update, including collections of
     * localized fallback values which are used for Titles and Slug Prototypes.
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $this->scheduleContentNodeRecalculation($event->getData());
    }

    protected function scheduleContentNodeRecalculation(ContentNode $contentNode)
    {
        $this->messageProducer->send(Topics::RESOLVE_NODE_SLUGS, $this->messageFactory->createMessage($contentNode));
    }
}
