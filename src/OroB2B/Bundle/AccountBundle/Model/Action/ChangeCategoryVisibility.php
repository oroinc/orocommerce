<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class ChangeCategoryVisibility extends AbstractVisibilityRegistryAwareAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $categoryVisibility = $this->getEntity($context);
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->transactional(
            function () use ($categoryVisibility) {
                $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
            }
        );
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
