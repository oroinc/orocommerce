<?php

namespace OroB2B\Bundle\AccountBundle\Audit;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @link http://www.doctrine-project.org/jira/browse/DDC-2370
 */
class CommitOrderListener
{
    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ClassMetadata[]
     */
    protected $metadataCache = [];

    /**
     * @param string $className
     * @param string $dependency
     */
    public function addDependency($className, $dependency)
    {
        $this->dependencies[$className][$dependency] = $dependency;
    }

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        foreach ($this->dependencies as $from => $toClasses) {
            foreach ($toClasses as $toClass) {
                $uow->getCommitOrderCalculator()->addDependency(
                    $this->getMetadata($from),
                    $this->getMetadata($toClass)
                );
            }
        }
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    protected function getMetadata($className)
    {
        if (array_key_exists($className, $this->metadataCache)) {
            return $this->metadataCache[$className];
        }

        $this->metadataCache[$className] = $this->managerRegistry->getManagerForClass($className)
            ->getClassMetadata($className);

        return $this->metadataCache[$className];
    }
}
