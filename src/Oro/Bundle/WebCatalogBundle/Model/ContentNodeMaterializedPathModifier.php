<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Calculates and updates materialized paths for content nodes in the web catalog tree.
 *
 * Materialized paths are denormalized representations of the tree hierarchy stored directly on each node,
 * enabling efficient tree queries without recursive joins. The path is constructed by concatenating node IDs
 * from the root to the current node, separated by underscores (e.g., "1_5_12" for a node with ID 12 whose parent is 5
 * and grandparent is 1). This service recalculates paths when nodes are moved or restructured.
 */
class ContentNodeMaterializedPathModifier
{
    public const MATERIALIZED_PATH_DELIMITER = '_';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
