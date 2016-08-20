<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class ChangeCategoryVisibility extends AbstractVisibilityRegistryAwareAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $categoryVisibility = $this->getEntity($context);
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->beginTransaction();
        try {
            $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
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
}
