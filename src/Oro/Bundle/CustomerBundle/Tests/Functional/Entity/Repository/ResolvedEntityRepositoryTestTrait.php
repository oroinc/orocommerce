<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;

/**
 * @method assertTrue($actual)
 * @method assertFalse($actual)
 * @method assertNotNull($actual)
 * @method assertEquals($expected, $actual)
 */
trait ResolvedEntityRepositoryTestTrait
{
    /**
     * @param EntityManager $manager
     * @param EntityRepository|BasicOperationRepositoryTrait $repository
     * @param array $where
     * @param int $visibility
     * @param int $source
     */
    protected function assertInsert(
        EntityManager $manager,
        EntityRepository $repository,
        array $where,
        $visibility,
        $source
    ) {
        $insert = [
            'sourceProductVisibility' => null,
            'visibility' => $visibility,
            'source' => $source,
            'category' => null,
        ];
        $repository->insertEntity(array_merge($where, $insert));
        $this->assertTrue($repository->hasEntity($where));
        $this->assertEntityData($manager, $repository, $where, $visibility, $source);
    }

    /**
     * @param EntityManager $manager
     * @param EntityRepository|BasicOperationRepositoryTrait $repository
     * @param array $where
     * @param int $visibility
     * @param int $source
     */
    protected function assertUpdate(
        EntityManager $manager,
        EntityRepository $repository,
        array $where,
        $visibility,
        $source
    ) {
        $update = ['visibility' => $visibility, 'source' => $source];
        $repository->updateEntity($update, $where);
        $this->assertTrue($repository->hasEntity($where));
        $this->assertEntityData($manager, $repository, $where, $visibility, $source);
    }

    /**
     * @param EntityRepository|BasicOperationRepositoryTrait $repository
     * @param array $where
     */
    protected function assertDelete(EntityRepository $repository, array $where)
    {
        $repository->deleteEntity($where);
        $this->assertFalse($repository->hasEntity($where));
    }

    /**
     * @param EntityManager $manager
     * @param EntityRepository $repository
     * @param array $where
     * @param int $visibility
     * @param int $source
     */
    protected function assertEntityData(
        EntityManager $manager,
        EntityRepository $repository,
        array $where,
        $visibility,
        $source
    ) {
        $entity = $repository->findOneBy($where);

        $this->assertNotNull($entity);
        $manager->refresh($entity);

        $this->assertEquals($visibility, $entity->getVisibility());
        $this->assertEquals($source, $entity->getSource());
    }
}
