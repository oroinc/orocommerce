<?php

namespace Oro\Component\Tree\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Component\Tree\Entity\Repository\NestedTreeRepository;

abstract class AbstractTreeHandler
{
    const ROOT_PARENT_VALUE = '#';
    const SUCCESS_STATUS = true;
    const ERROR_STATUS = false;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param string $entityClass
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct($entityClass, ManagerRegistry $managerRegistry)
    {
        $this->entityClass = $entityClass;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param object|null $root
     * @param bool $includeRoot
     * @return array
     */
    public function createTree($root = null, $includeRoot = true)
    {
        $root = $this->getRootNode($root);
        $tree = $this->getNodes($root, $includeRoot);
        return $this->formatTree($tree, $root, $includeRoot);
    }

    /**
     * Move a entity to another parent entity
     *
     * @param int $entityId
     * @param int $parentId
     * @param int $position
     * @return array
     */
    public function moveNode($entityId, $parentId, $position)
    {
        $status = ['status' => self::SUCCESS_STATUS];

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass($this->entityClass);
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            $entity = $this->moveProcessing($entityId, $parentId, $position);

            $em->flush($entity);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $status['status'] = self::ERROR_STATUS;
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    /**
     * @param object|null $root
     * @param bool        $includeRoot
     * @return TreeItem[]
     */
    public function getTreeItemList($root = null, $includeRoot = true)
    {
        $nodes = $this->createTree($root, $includeRoot);

        $items = [];

        foreach ($nodes as $node) {
            $items[$node['id']] = new TreeItem($node['id'], $node['text']);
        }

        foreach ($nodes as $node) {
            if (array_key_exists($node['parent'], $items)) {
                /** @var TreeItem $treeItem */
                $treeItem = $items[$node['id']];
                $treeItem->setParent($items[$node['parent']]);
            }
        }

        return $items;
    }

    /**
     * @param TreeItem[] $sourceData
     * @param array      $treeData
     */
    public function disableTreeItems(array $sourceData, array &$treeData)
    {
        foreach ($treeData as &$treeItem) {
            foreach ($sourceData as $sourceItem) {
                if ($sourceItem->getKey() === $treeItem['id'] || $sourceItem->hasChildRecursive($treeItem['id'])) {
                    $treeItem['state']['disabled'] = true;
                }
            }
        }
    }

    /**
     * @param null $root
     * @param bool $includeRoot
     * @return array
     */
    protected function getNodes($root, $includeRoot)
    {
        return $this->getEntityRepository()->getChildren($root, false, 'left', 'ASC', $includeRoot);
    }

    /**
     * @param object $root
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getRootNode($root)
    {
        if ($root && !$root instanceof $this->entityClass) {
            return $this->getEntityManager()->getReference($this->entityClass, $root);
        }
        return $root;
    }

    /**
     * @param array $entities
     * @param object|null $root
     * @param bool $includeRoot
     * @return array
     */
    protected function formatTree(array $entities, $root, $includeRoot)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $node = $this->formatEntity($entity);
            $rootId = $root ? $root->getId() : null;
            $node['parent'] = $this->getParent($node['id'], $node['parent'], $rootId, $includeRoot);
            $formattedTree[] = $node;
        }

        return $formattedTree;
    }

    /**
     * @param int $entityId
     * @param int $parentId
     * @param int $rootId
     * @param bool $includeRoot
     * @return string
     */
    protected function getParent($entityId, $parentId, $rootId, $includeRoot)
    {
        $parent = self::ROOT_PARENT_VALUE;
        if ($rootId && $entityId === $rootId) {
            return $parent;
        }
        if ($parentId && !($rootId && $parentId === $rootId && !$includeRoot)) {
            $parent = $parentId;
        }

        return $parent;
    }

    /**
     * Move node processing
     *
     * @param int $entityId
     * @param int $parentId
     * @param int $position
     * @return object $entity
     */
    abstract protected function moveProcessing($entityId, $parentId, $position);

    /**
     * Returns an array formatted as:
     * array(
     *     'id'     => int,    // tree item id
     *     'parent' => int,    // tree item parent id
     *     'text'   => string  // tree item label
     * )
     *
     * @param object $entity
     * @return array
     */
    abstract protected function formatEntity($entity);

    /**
     * @return NestedTreeRepository
     */
    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityClass);
    }

    /**
     * @return EntityManager|null
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->entityClass);
    }
}
