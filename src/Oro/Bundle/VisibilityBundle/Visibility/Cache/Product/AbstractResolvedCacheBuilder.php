<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;

/**
 * The base class for visibility cache builders.
 */
abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    protected function executeDbQuery(
        EntityRepository $repository,
        bool $insert,
        bool $delete,
        array $update,
        array $where
    ): void {
        /** @var BasicOperationRepositoryTrait $repository */
        if ($insert) {
            $repository->insertEntity(array_merge($update, $where));
        } elseif ($delete) {
            $repository->deleteEntity($where);
        } elseif ($update) {
            $repository->updateEntity($update, $where);
        }
    }

    protected function refreshEntity(object $entity): ?object
    {
        $entityClass = ClassUtils::getClass($entity);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        if ($entityManager->getUnitOfWork()->getEntityState($entity) !== UnitOfWork::STATE_MANAGED) {
            $identifier = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($entity);
            if ($identifier) {
                $entity = $entityManager->getRepository($entityClass)->find($identifier);
            } else {
                $entity = null;
            }
        } else {
            $entityManager->refresh($entity);
        }

        return $entity;
    }
}
