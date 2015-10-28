<?php

namespace OroB2B\Bundle\AccountBundle\Audit;

use Doctrine\ORM\Event\OnFlushEventArgs;

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
     * @param string $className
     * @param string $dependency
     */
    public function addDependency($className, $dependency)
    {
        $this->dependencies[$className][$dependency] = $dependency;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->dependencies) {
            return;
        }

        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $commitOrderCalculator = $uow->getCommitOrderCalculator();

        foreach ($this->dependencies as $from => $toClasses) {
            $fromMetadata = $em->getClassMetadata($from);
            foreach ($toClasses as $toClass) {
                $commitOrderCalculator->addDependency($fromMetadata, $em->getClassMetadata($toClass));
            }
        }
    }
}
