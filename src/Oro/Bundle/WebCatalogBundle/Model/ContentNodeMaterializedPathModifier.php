<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeMaterializedPathModifier
{
    const MATERIALIZED_PATH_DELIMITER = '_';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ContentNode $contentNode
     * @return ContentNode[]
     */
    public function calculateChildrenMaterializedPath(ContentNode $contentNode)
    {
        $repository = $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        $children = $repository->children($contentNode);
        
        $childNodes = [];
        foreach ($children as $child) {
            $childNodes[] = $this->calculateMaterializedPath($child);
        }
        
        return $childNodes;
    }

    /**
     * @param ContentNode $contentNode
     * @return ContentNode
     */
    public function calculateMaterializedPath(ContentNode $contentNode)
    {
        $path = (string) $contentNode->getId();
        $parent = $contentNode->getParentNode();
        if ($parent && $parent->getMaterializedPath()) {
            $path = $parent->getMaterializedPath() . self::MATERIALIZED_PATH_DELIMITER . $path;
        }

        $contentNode->setMaterializedPath($path);
        
        return $contentNode;
    }
}
