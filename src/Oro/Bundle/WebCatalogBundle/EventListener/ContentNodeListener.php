<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

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

    /**
     * @param ContentNodeMaterializedPathModifier $modifier
     * @param ExtraActionEntityStorageInterface $storage
     * @param MessageProducerInterface $messageProducer
     * @param ResolveNodeSlugsMessageFactory $messageFactory
     */
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

    /**
     * @param ContentNode $contentNode
     */
    public function postPersist(ContentNode $contentNode)
    {
        $contentNode = $this->modifier->calculateMaterializedPath($contentNode);
        $this->storage->scheduleForExtraInsert($contentNode);
    }

    /**
     * @param ContentNode $contentNode
     * @param PreUpdateEventArgs $args
     */
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

    /**
     * @param ContentNode $contentNode
     */
    public function postRemove(ContentNode $contentNode)
    {
        $this->scheduleContentNodeRecalculation($contentNode);
    }

    /**
     * Form after flush is used to catch all content node fields update, including collections of
     * localized fallback values which are used for Titles and Slug Prototypes.
     *
     * @param AfterFormProcessEvent $event
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $this->scheduleContentNodeRecalculation($event->getData());
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function scheduleContentNodeRecalculation(ContentNode $contentNode)
    {
        $this->messageProducer->send(Topics::RESOLVE_NODE_SLUGS, $this->messageFactory->createMessage($contentNode));
    }
}
