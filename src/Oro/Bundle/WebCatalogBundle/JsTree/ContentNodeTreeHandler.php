<?php

namespace Oro\Bundle\WebCatalogBundle\JsTree;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

/**
 * @method ContentNodeRepository getEntityRepository()
 */
class ContentNodeTreeHandler extends AbstractTreeHandler
{
    /**
     * @param ContentNode $entity
     *
     * {@inheritdoc}
     */
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentNode() ? $entity->getParentNode()->getId() : null,
            'text'   => $entity->getName(),
            'state'  => [
                'opened' => $entity->getParentNode() === null
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getNodes($root, $includeRoot)
    {
        $entities = parent::getNodes($root, $includeRoot);
        $rootNodes = [];

        /** @var ContentNode $node */
        foreach ($entities as $key => $node) {
            if (!$node->getParentNode()) {
                unset($entities[$key]);
                $rootNodes[] = $node;
            }
        }

        uasort($rootNodes, function (ContentNode $a, ContentNode $b) {
            return $a->getId() > $b->getId() ? 1 : -1;
        });

        return array_merge($rootNodes, $entities);
    }

    /**
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        /** @var ContentNodeRepository $entityRepository */
        $entityRepository = $this->getEntityRepository();

        /** @var ContentNode $node */
        $node = $entityRepository->find($entityId);
        /** @var ContentNode $parentNode */
        $parentNode = $entityRepository->find($parentId);

        if (null === $parentNode) {
            $node->setParentNode(null);
        } else {
            if ($parentNode->getChildNodes()->contains($node)) {
                $parentNode->removeChildNode($node);
            }

            $parentNode->addChildNode($node);

            if ($position) {
                $children = array_values($parentNode->getChildNodes()->toArray());
                $entityRepository->persistAsNextSiblingOf($node, $children[$position - 1]);
            } else {
                $entityRepository->persistAsFirstChildOf($node, $parentNode);
            }
        }
    }

    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode
     */
    public function getTreeRootByWebCatalog(WebCatalog $webCatalog)
    {
        return $this->getEntityRepository()->getRootNodeByWebCatalog($webCatalog);
    }
}
