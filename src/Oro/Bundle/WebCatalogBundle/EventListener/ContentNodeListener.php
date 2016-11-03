<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeNameFiller;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;

class ContentNodeListener
{
    const FIELD_TITLES = 'titles';

    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var ContentNodeNameFiller
     */
    protected $contentNodeNameFiller;

    /**
     * @param ContentNodeMaterializedPathModifier $modifier
     * @param ExtraActionEntityStorageInterface $storage
     * @param ContentNodeNameFiller $contentNodeNameFiller
     */
    public function __construct(
        ContentNodeMaterializedPathModifier $modifier,
        ExtraActionEntityStorageInterface $storage,
        ContentNodeNameFiller $contentNodeNameFiller
    ) {
        $this->modifier = $modifier;
        $this->storage = $storage;
        $this->contentNodeNameFiller = $contentNodeNameFiller;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function prePersist(ContentNode $contentNode)
    {
        $contentNode->setName($this->contentNodeNameFiller->fillName($contentNode));
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

            foreach ($childNodes as $childNode) {
                $this->storage->scheduleForExtraInsert($childNode);
            }
        }
    }
}
