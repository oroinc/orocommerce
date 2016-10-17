<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;

class ContentNodeListener
{
    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;
    
    /**
     * @param ContentNodeMaterializedPathModifier $modifier
     */
    public function __construct(ContentNodeMaterializedPathModifier $modifier)
    {
        $this->modifier = $modifier;
    }
    
    /**
     * @param ContentNode $contentNode
     */
    public function postPersist(ContentNode $contentNode)
    {
        $this->modifier->calculateMaterializedPath($contentNode, true);
    }
    
    /**
     * @param ContentNode $contentNode
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(ContentNode $contentNode, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();
        
        $children = [];
        if (!empty($changeSet[ContentNode::FIELD_PARENT_NODE])) {
            /** @var ContentNodeRepository $repository */
            $repository = $args->getEntityManager()->getRepository(ContentNode::class);
            $children = $repository->children($contentNode);
        }
        
        $this->modifier->updateMaterializedPathNested($contentNode, $children);
    }
}
