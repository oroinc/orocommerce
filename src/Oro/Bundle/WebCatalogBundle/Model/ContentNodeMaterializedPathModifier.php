<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeMaterializedPathModifier
{
    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;
    
    /**
     * @param ExtraActionEntityStorageInterface $storage
     */
    public function __construct(ExtraActionEntityStorageInterface $storage)
    {
        $this->storage = $storage;
    }
    
    /**
     * @param ContentNode $contentNode
     * @param array $children
     */
    public function updateMaterializedPathNested(ContentNode $contentNode, array $children = [])
    {
        $this->calculateMaterializedPath($contentNode);
        
        foreach ($children as $child) {
            $this->calculateMaterializedPath($child, true);
        }
    }

    /**
     * @param ContentNode $contentNode
     * @param bool $scheduleForInsert
     */
    public function calculateMaterializedPath(ContentNode $contentNode, $scheduleForInsert = false)
    {
        $path = (string) $contentNode->getId();
        $parent = $contentNode->getParentNode();
        if ($parent && $parent->getMaterializedPath()) {
            $path = $parent->getMaterializedPath() . ContentNode::MATERIALIZED_PATH_DELIMITER . $path;
        }

        $contentNode->setMaterializedPath($path);
        if ($scheduleForInsert) {
            $this->storage->scheduleForExtraInsert($contentNode);
        }
    }
}
