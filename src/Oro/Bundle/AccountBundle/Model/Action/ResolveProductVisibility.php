<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class ResolveProductVisibility extends AbstractVisibilityRegistryAwareAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $visibilityEntity = $this->getEntity($context);

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $this->cacheBuilder->resolveVisibilitySettings($visibilityEntity);
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity($context)
    {
        $entity = parent::getEntity($context);

        if (!$entity instanceof VisibilityInterface) {
            throw new \LogicException('Resolvable entity must implement VisibilityInterface');
        }

        return $entity;
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }
}
