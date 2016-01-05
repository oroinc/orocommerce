<?php

namespace OroB2B\Component\Tree\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Component\Tree\Entity\Repository\NestedTreeRepository;

abstract class AbstractTreeHandler
{
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
     * @param mixed|null $root
     * @param bool $includeRoot
     * @return array
     */
    public function createTree($root = null, $includeRoot = true)
    {
        $tree = $this->getNodes($this->getRootNode($root), $includeRoot);
        return $this->formatTree($tree, $root);
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
            $this->moveProcessing($entityId, $parentId, $position);

            $em->flush();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $status['status'] = self::ERROR_STATUS;
            $status['error'] = $e->getMessage();
        }

        return $status;
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
     * @param mixed $root
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
     * @return array
     */
    protected function formatTree(array $entities, $root)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $formattedTree[] = $this->formatEntity($entity, $root);
        }

        return $formattedTree;
    }

    /**
     * Move node processing
     *
     * @param int $entityId
     * @param int $parentId
     * @param int $position
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
     * @param object|null $root
     * @return array
     */
    abstract protected function formatEntity($entity, $root);

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
