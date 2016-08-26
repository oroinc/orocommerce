<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WebsiteBundle\Entity\Website;

class BuildWebsiteCacheAction extends AbstractVisibilityRegistryAwareAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $website = $this->getEntity($context);
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $this->cacheBuilder->buildCache($website);
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

        if (!$entity instanceof Website) {
            throw new \LogicException('Resolvable entity must be instance of Website');
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
